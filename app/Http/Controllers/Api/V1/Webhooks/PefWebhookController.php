<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Ciot;
use App\Models\Carga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PefWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Zero Trust: Validação do Token de Segurança do Webhook
        $token = $request->header('X-PEF-Signature') ?? $request->query('token');
        if ($token !== config('services.pef.webhook_secret')) {
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
            $ciot = Ciot::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();

            if (!$ciot || $ciot->status === 'emitido') {
                return; // Já processado ou não existe
            }

            if ($statusGateway === 'EMITIDO_ANTT') {
                $ciot->update([
                    'status' => 'emitido',
                    'webhook_payload' => $payload
                ]);

                // Libera a carga para viagem na máquina de estados
                Carga::where('id', $ciot->carga_id)->update(['status' => 'em_viagem']);
                Log::info("[Webhook] CIOT {$ciot->codigo_ciot} consolidado na ANTT. Viagem liberada.");
            }
        });

        return response()->json(['received' => true]);
    }
}
