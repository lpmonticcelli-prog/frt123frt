<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Models\Fatura;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessarPagamentoSaaSJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Resiliência: Tentativas máximas antes de mover para DLQ.
     */
    public int $tries = 3;

    /**
     * Resiliência: Exponential Backoff para evitar estrangulamento do Banco de Dados.
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly array $payload
    ) {}

    /**
     * Idempotência de Fila: O worker recusa enfileiramento duplo do mesmo webhook.
     */
    public function uniqueId(): string
    {
        return (string) ($this->payload['data']['tx_id'] ?? $this->payload['tx_id'] ?? 'invalid_tx');
    }

    public function handle(): void
    {
        $txId = $this->payload['data']['tx_id'] ?? $this->payload['tx_id'] ?? null;
        $statusGateway = $this->payload['data']['status'] ?? $this->payload['status'] ?? null;

        if (!$txId || !$statusGateway) {
            Log::warning('[Billing Webhook] Payload anômalo ou malformado descartado na fila.', [
                'payload' => $this->payload
            ]);
            return;
        }

        try {
            DB::transaction(function () use ($txId, $statusGateway) {
                // ROW-LEVEL LOCK: Atua estritamente sobre a Fatura de Uso do SaaS (Novo Domínio).
                $fatura = Fatura::where('gateway_tx_id', $txId)->lockForUpdate()->first();

                if (!$fatura) {
                    Log::warning("[Billing Webhook] Fatura SaaS não localizada para a Transação: {$txId}");
                    return;
                }

                // Idempotência Transacional
                if ($fatura->status === 'paga') {
                    Log::info("[Billing Webhook] Fatura ID {$fatura->id} já consta como liquidada. Descarte limpo.");
                    return;
                }

                if ($statusGateway === 'paid') {
                    $fatura->update([
                        'status'         => 'paga',
                        'data_pagamento' => now()
                    ]);
                    
                    // Aqui você dispara Eventos de ativação de conta, envio de NFSe, etc.
                    // Não alteramos status de "Carga" aqui.

                    Log::info("[Billing Webhook] Fatura de Uso da Plataforma ID {$fatura->id} liquidada com sucesso.");
                } elseif (in_array($statusGateway, ['failed', 'refunded'], true)) {
                    $fatura->update([
                        'status' => $statusGateway === 'failed' ? 'falhada' : 'estornada'
                    ]);
                    
                    Log::notice("[Billing Webhook] O status da Fatura ID {$fatura->id} foi degradado para {$statusGateway}.");
                }
            });
        } catch (Throwable $e) {
            Log::critical('[Billing Webhook] Colapso I/O ao processar conciliação financeira do SaaS.', [
                'tx_id'     => $txId,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}