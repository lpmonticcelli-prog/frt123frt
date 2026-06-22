<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Ciot;
use App\Models\Carga;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class PefWebhookController extends Controller
{
    public function __construct()
    {
        // ZT-DEFENSE: Delegação de Segurança B2B (HMAC-SHA256).
        // Bloqueia qualquer requisição que não possua a assinatura criptográfica exata da PEF.
        $this->middleware('b2b.hmac:pef');
    }

    // CORREÇÃO: Método renomeado de 'handle' para 'handleCallback' para respeitar o routes/api.php
    public function handleCallback(Request $request): JsonResponse
    {
        $payload = $request->all();
        $idempotencyKey = $payload['idempotency_key'] ?? null;
        $statusGateway = $payload['status'] ?? null;

        if (!$idempotencyKey) {
            return response()->json(['error' => 'Bad Request: Missing Idempotency Key'], 400);
        }

        try {
            // Isolamento Transacional ACID
            DB::beginTransaction();

            // ROW-LEVEL LOCKING: Previne que dois webhooks concorrentes alterem o mesmo CIOT
            $ciot = Ciot::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();

            if (!$ciot) {
                DB::rollBack();
                Log::warning("[PEF Webhook] CIOT não encontrado para a chave de idempotência: {$idempotencyKey}");
                return response()->json(['status' => 'Ignored - CIOT not found'], 200);
            }

            if ($ciot->status === 'emitido' || $ciot->status === 'cancelado' || $ciot->status === 'falhou') {
                DB::rollBack();
                return response()->json(['status' => 'Already processed'], 200);
            }

            if ($statusGateway === 'EMITIDO_ANTT') {
                $ciot->update([
                    'status' => 'emitido',
                    'webhook_payload' => $payload
                ]);

                // Propagação atômica do estado para a Carga atrelada
                $carga = Carga::where('id', $ciot->carga_id)->lockForUpdate()->first();
                
                // ZT-DEFENSE: Garantimos que a carga só vai para "aguardando_coleta" se estiver no estado exato de espera
                if ($carga && $carga->status === 'processando_aceite') {
                    $carga->update(['status' => 'aguardando_coleta']);
                    Log::info("[PEF Webhook] CIOT {$ciot->id} consolidado na ANTT. Carga ID {$carga->id} liberada para viagem.");
                }
            } elseif ($statusGateway === 'FALHA_ANTT' || $statusGateway === 'REJEITADO') {
                $ciot->update([
                    'status' => 'falhou',
                    'webhook_payload' => $payload
                ]);
                
                // Em caso de falha burocrática, o motorista é desatrelado e a carga volta ao mercado
                $carga = Carga::where('id', $ciot->carga_id)->lockForUpdate()->first();
                if ($carga && $carga->status === 'processando_aceite') {
                    $carga->update(['status' => 'publicada', 'motorista_id' => null]); 
                    Log::error("[PEF Webhook] Emissão do CIOT falhou. Carga ID {$carga->id} devolvida ao mural.");
                }
            }

            DB::commit();

            return response()->json(['received' => true, 'status' => 'processed'], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::critical('[PEF Webhook] Colapso atômico ao processar callback da ANTT.', [
                'idempotency_key' => $idempotencyKey,
                'error' => $e->getMessage()
            ]);
            
            // Retorna 500 para forçar a PEF a colocar o webhook em fila de Retry
            return response()->json(['error' => 'Internal Processing Error'], 500);
        }
    }
}