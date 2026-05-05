<?php

namespace App\Jobs;

use App\Models\Ciot;
use App\Contracts\PefGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SolicitarEmissaoCiotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // Exponential backoff se a API externa falhar

    protected $ciotId;

    public function __construct(int $ciotId)
    {
        $this->ciotId = $ciotId;
    }

    public function handle(PefGatewayInterface $pefGateway): void
    {
        $ciot = Ciot::with('carga')->find($this->ciotId);

        if (!$ciot || $ciot->status !== 'processando') {
            Log::warning("[Worker] CIOT {$this->ciotId} não encontrado ou já processado.");
            return;
        }

        try {
            // A API deve aceitar a idempotency_key para não duplicar fretes
            $response = $pefGateway->emitirCiot($ciot->carga);

            $ciot->update([
                'codigo_ciot' => $response['codigo_ciot'],
                'pef_payload_response' => $response,
                'status' => 'aguardando_webhook' // Espera o callback oficial
            ]);

            Log::info("[Worker] Pedido de emissão do CIOT {$this->ciotId} enviado com sucesso.");
        } catch (\Exception $e) {
            Log::error("[Worker] Falha na emissão do CIOT: " . $e->getMessage());
            throw $e; // Força o retry na fila
        }
    }
}
