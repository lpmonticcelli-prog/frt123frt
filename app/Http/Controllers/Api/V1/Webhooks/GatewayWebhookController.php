<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PagamentoEscrow;
use App\Jobs\ProcessarAceiteCarga;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GatewayWebhookController extends Controller
{
    public function __construct()
    {
        // ZT-DEFENSE: Delegação de Segurança B2B.
        // Garante que TODOS os métodos deste controller exijam validação criptográfica (HMAC).
        // Se a assinatura for inválida, o código nem sequer instanciará o Request no escopo abaixo.
        $this->middleware('b2b.hmac:gateway');
    }

    public function handleCallback(Request $request): JsonResponse
    {
        // Se a requisição chegou até esta linha, o Middleware já confirmou que a origem
        // possui a chave secreta e o payload não sofreu adulteração (Man-in-the-Middle).
        
        $txId = $request->input('data.tx_id') ?? $request->input('tx_id');
        $statusGateway = $request->input('data.status') ?? $request->input('status');

        if (!$txId) {
            return response()->json(['error' => 'Bad Request: Missing Transaction ID'], 400);
        }

        try {
            // Isolamento Transacional ACID
            DB::beginTransaction();

            // ROW-LEVEL LOCKING: Impede duplicação de saldo em caso de double-delivery (retry) do gateway
            $pagamento = PagamentoEscrow::where('gateway_tx_id', $txId)->lockForUpdate()->first();

            if (!$pagamento) {
                DB::rollBack();
                Log::warning("[Escrow Webhook] Pagamento fantasma ou não registrado no banco local. TX: {$txId}");
                return response()->json(['status' => 'Ignored - TX not found'], 200);
            }

            if ($pagamento->status === 'liquidado') {
                DB::rollBack();
                return response()->json(['status' => 'Already processed'], 200); 
            }

            if ($statusGateway === 'paid') {
                $pagamento->update(['status' => 'liquidado']);
                
                // Propagação do Lock para a Entidade Dominante
                $carga = $pagamento->carga()->lockForUpdate()->first();
                
                if ($carga && $carga->motorista_id) {
                    $carga->update(['status' => 'processando_aceite']);
                    
                    // O motorista possui dinheiro locado no Escrow. Aciona a CIOT em background via Filas (RabbitMQ/SQS).
                    ProcessarAceiteCarga::dispatch(
                        $carga->id,
                        $carga->motorista->user_id,
                        $request->ip(), // Passando o IP do Gateway para auditoria
                        'Gateway Webhook Autorizado (B2B)'
                    )->onQueue('financeiro');
                    
                    Log::info("[Escrow Webhook] Pagamento {$txId} liquidado. Máquina de Estados ativada para Motorista ID: {$carga->motorista->id}.");
                } else if ($carga) {
                    // Carga foi paga pelo embarcador, mas ainda está livre no mural (sem lance vencedor)
                    $carga->update(['status' => 'publicada']);
                    Log::info("[Escrow Webhook] Pagamento {$txId} confirmado. Carga ID {$carga->id} ativada no mural público.");
                }
            } elseif ($statusGateway === 'failed' || $statusGateway === 'refunded') {
                $pagamento->update(['status' => $statusGateway === 'failed' ? 'falhou' : 'estornado']);
                Log::notice("[Escrow Webhook] Pagamento {$txId} atualizado para: {$statusGateway}");
            }

            DB::commit();

            return response()->json(['status' => 'Webhook processado com sucesso.'], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::critical('[Escrow Webhook] Colapso atômico ao processar callback transacional.', [
                'tx_id' => $txId,
                'error' => $e->getMessage()
            ]);
            
            // Retorna 500 para forçar o Gateway a colocar o webhook em fila de Retry.
            return response()->json(['error' => 'Internal Processing Error'], 500);
        }
    }
}