<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Ciot;
use App\Contracts\PefGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class SolicitarEmissaoCiotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Resiliência Governamental: Exponential Backoff para absorver quedas da ANTT
    public int $tries = 5;
    public array $backoff = [5, 15, 45, 120, 300];

    public function __construct(protected int $ciotId) {}

    public function handle(PefGatewayInterface $pefGateway): void
    {
        $ciot = Ciot::with('carga')->find($this->ciotId);

        // Idempotência
        if (!$ciot || $ciot->status !== 'processando') {
            Log::warning("[Worker/PEF] Emissão abortada: CIOT {$this->ciotId} não encontrado ou já processado.");
            return;
        }

        try {
            // ZT-DEFENSE: A requisição de rede opera fora da transaction SQL
            $response = $pefGateway->emitirCiot($ciot->carga);

            if (!$response->sucesso || empty($response->codigoCiot)) {
                throw new \Exception("A Integradora PEF recusou a geração do CIOT.");
            }

            DB::transaction(function () use ($ciot, $response) {
                $ciotLock = Ciot::where('id', $this->ciotId)->lockForUpdate()->first();
                if ($ciotLock->status === 'emitido') return;

                $ciotLock->update([
                    'codigo_ciot' => $response->codigoCiot,
                    'imposto_inss' => $response->inss,
                    'imposto_sest_senat' => $response->sestSenat,
                    'imposto_irrf' => $response->irrf,
                    'valor_vale_pedagio' => $response->valePedagio,
                    'taxa_123fretei' => $response->taxa123fretei,
                    'valor_frete_liquido' => $response->liquidoMotorista,
                    'pef_payload_response' => $response->payloadOriginal,
                    'status' => 'emitido' 
                ]);

                $ciotLock->carga->update(['status' => 'aguardando_coleta']);
            });

            Log::info("[Worker/ANTT] Sucesso: CIOT {$response->codigoCiot} homologado.");

        } catch (Throwable $e) {
            Log::error("[Worker/PEF] Falha na emissão do CIOT {$this->ciotId}: " . $e->getMessage());
            
            // Circuit Breaker
            if ($this->attempts() >= $this->tries) {
                DB::transaction(function() use ($ciot) {
                    $ciotLock = Ciot::where('id', $this->ciotId)->lockForUpdate()->first();
                    if ($ciotLock) {
                        $ciotLock->update(['status' => 'cancelado']);
                        $ciotLock->carga->update(['status' => 'publicada', 'motorista_id' => null]);
                    }
                });
                Log::critical("[Circuit Breaker] Falha total da Pamcard/Repom. Carga {$ciot->carga_id} recuada.");
            }
            throw $e; 
        }
    }
}