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
    public function handleCallback(Request $request): JsonResponse
    {
        // 1. BLINDAGEM ZERO-TRUST: Verificação de Assinatura do Gateway (HMAC)
        $signature = (string) ($request->header('X-Gateway-Signature') ?? $request->header('x-gateway-signature'));
        $secret = (string) config('services.gateway.webhook_secret', 'mock_gateway_secret');
        
        $payloadRaw = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payloadRaw, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::alert('[WEBHOOK HACK] Assinatura do Gateway de Pagamento inválida.', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $txId = $request->input('data.tx_id') ?? $request->input('tx_id');
        $statusGateway = $request->input('data.status') ?? $request->input('status');

        if (!$txId) {
            return response()->json(['error' => 'Bad Request'], 400);
        }

        try {
            DB::beginTransaction();

            // 2. ROW-LEVEL LOCKING: Impede duplicação em caso de retry do gateway
            $pagamento = PagamentoEscrow::where('gateway_tx_id', $txId)->lockForUpdate()->first();

            if (!$pagamento) {
                DB::rollBack();
                return response()->json(['status' => 'Ignored - TX not found'], 200);
            }

            if ($pagamento->status === 'liquidado') {
                DB::rollBack();
                return response()->json(['status' => 'Already processed'], 200); 
            }

            if ($statusGateway === 'paid') {
                $pagamento->update(['status' => 'liquidado']);
                
                $carga = $pagamento->carga()->lockForUpdate()->first();
                
                if ($carga && $carga->motorista_id) {
                    $carga->update(['status' => 'processando_aceite']);
                    
                    // O motorista possui dinheiro locado no Escrow. Aciona a CIOT em background.
                    ProcessarAceiteCarga::dispatch(
                        $carga->id,
                        $carga->motorista->user_id,
                        '127.0.0.1',
                        'Gateway Webhook Autorizado'
                    )->onQueue('financeiro');
                    
                    Log::info("[Escrow Webhook] Pagamento {$txId} confirmado. Transição Logística ativada para Motorista ID: {$carga->motorista->id}.");
                } else if ($carga) {
                    // Carga foi paga mas ainda está livre no mural
                    $carga->update(['status' => 'publicada']);
                    Log::info("[Escrow Webhook] Pagamento {$txId} confirmado. Carga publicada (sem motorista atrelado).");
                }
            } elseif ($statusGateway === 'failed' || $statusGateway === 'refunded') {
                $pagamento->update(['status' => $statusGateway === 'failed' ? 'falhou' : 'estornado']);
            }

            DB::commit();

            return response()->json(['status' => 'Webhook processado com sucesso.'], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::critical('[Escrow Webhook] Falha ao processar callback de pagamento.', [
                'tx_id' => $txId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Internal Error'], 500);
        }
    }
}