<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Jobs\Billing\ProcessarPagamentoSaaSJob;

class GatewayWebhookController extends Controller
{
    /**
     * Entrypoint do Webhook Financeiro.
     * Padrão Zero-Trust aplicado via Middleware de Rota (HMAC).
     * Arquitetura Offloading: Nenhuma transação ACID é aberta nesta thread.
     */
    public function handleCallback(Request $request): JsonResponse
    {
        // Extração estrita para prevenir a serialização de payloads excessivos na fila
        $payload = $request->only(['data', 'tx_id', 'status', 'event']);

        // QUEUE OFFLOADING: Descarrega a requisição para o pool de workers.
        // Impede que o Gateway de Pagamentos derrube a API por esgotamento do PHP-FPM.
        ProcessarPagamentoSaaSJob::dispatch($payload)->onQueue('financeiro');

        // HTTP 202 Accepted: Confirma o recebimento imediatamente ao Gateway.
        return response()->json([
            'message' => 'Payload reconhecido e enfileirado para processamento seguro.'
        ], 202);
    }
}