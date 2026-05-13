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
use Illuminate\Support\Facades\DB;

class SolicitarEmissaoCiotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    protected int $ciotId;

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
            // O Gateway injetado aqui é o MockPefGateway (definido no PefServiceProvider)
            $response = $pefGateway->emitirCiot($ciot->carga);

            if (!$response->sucesso) {
                throw new \Exception("Gateway de Pagamento recusou a emissão do CIOT.");
            }

            DB::transaction(function () use ($ciot, $response) {
                $ciot->update([
                    'codigo_ciot' => $response->codigoCiot,
                    'imposto_inss' => $response->inss,
                    'imposto_sest_senat' => $response->sestSenat,
                    'imposto_irrf' => $response->irrf,
                    'valor_vale_pedagio' => $response->valePedagio,
                    'taxa_123fretei' => $response->taxa123fretei,
                    'valor_frete_liquido' => $response->liquidoMotorista,
                    'pef_payload_response' => $response->payloadOriginal,
                    // AUTO-APROVAÇÃO MOCK: Ignoramos a espera pelo webhook da ANTT
                    'status' => 'emitido' 
                ]);

                // DESTRAVA A CARGA NO FRONTEND: O Motorista já pode ver o botão "Iniciar Viagem"
                $ciot->carga->update(['status' => 'aguardando_coleta']);
            });

            Log::info("[Worker] MOCK CIOT {$response->codigoCiot} gerado com sucesso. Carga liberada para transporte.");

        } catch (\Exception $e) {
            Log::error("[Worker] Falha na emissão do CIOT: " . $e->getMessage());
            
            // Se falhar todas as tentativas, aborta o contrato e devolve a carga ao mural
            if ($this->attempts() >= $this->tries) {
                DB::transaction(function() use ($ciot) {
                    $ciot->update(['status' => 'cancelado']);
                    $ciot->carga->update(['status' => 'publicada', 'motorista_id' => null]);
                });
                Log::critical("[Worker] Carga {$ciot->carga_id} devolvida ao mural após falha catastrófica no PEF.");
            }
            throw $e; 
        }
    }
}