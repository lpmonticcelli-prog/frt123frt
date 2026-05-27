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

class PefWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Coerção rigorosa previne Type Juggling e TypeErrors
        $expectedToken = (string) config('services.pef.webhook_secret');
        $providedToken = (string) ($request->header('X-PEF-Signature') ?? $request->query('token', ''));

        // Validação O(1) contra Timing Attacks e proteção contra tokens vazios
        if (empty($expectedToken) || empty($providedToken) || !hash_equals($expectedToken, $providedToken)) {
            Log::alert("[Segurança] Tentativa de injeção em Webhook PEF bloqueada. IP: " . $request->ip());
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $idempotencyKey = $payload['idempotency_key'] ?? null;
        $statusGateway = $payload['status'] ?? null;

        if (!$idempotencyKey) {
            return response()->json(['error' => 'Idempotency key is missing'], 400);
        }

        DB::transaction(function () use ($idempotencyKey, $statusGateway, $payload) {
            // Isolamento de transação mantido
            $ciot = Ciot::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();

            if (!$ciot || $ciot->status === 'emitido') {
                return;
            }

            if ($statusGateway === 'EMITIDO_ANTT') {
                $ciot->update([
                    'status' => 'emitido',
                    'webhook_payload' => $payload
                ]);

                // CIRURGIA APLICADA: Status corrigido para destravar botão no App do Motorista
                Carga::where('id', $ciot->carga_id)->update(['status' => 'aguardando_coleta']);
                Log::info("[Webhook] CIOT {$ciot->codigo_ciot} consolidado na ANTT. Viagem liberada.");
            }
        });

        return response()->json(['received' => true]);
    }
}