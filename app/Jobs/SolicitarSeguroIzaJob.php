<?php

namespace App\Jobs;

use App\Models\Motorista;
use App\Models\MotoristaSeguro;
use App\Services\Partners\IzaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SolicitarSeguroIzaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Motorista $motorista;
    public string $plano;

    /**
     * Configuração de retentativas (Fail-safe)
     * Tenta novamente após 1 minuto, depois 2 minutos, e por fim 5 minutos.
     */
    public int $tries = 3;
    public array $backoff = [60, 120, 300]; 

    public function __construct(Motorista $motorista, string $plano)
    {
        $this->motorista = $motorista;
        $this->plano = $plano;
    }

    /**
     * Execute the job.
     * O Laravel injeta a dependência do IzaService automaticamente.
     */
    public function handle(IzaService $izaService): void
    {
        Log::info('[IZA_JOB] Iniciando solicitação de seguro', [
            'motorista_id' => $this->motorista->id,
            'plano' => $this->plano
        ]);

        try {
            // 1. Prepara o registro no banco informando que o processo começou
            $seguro = MotoristaSeguro::updateOrCreate(
                ['motorista_id' => $this->motorista->id],
                [
                    'plano' => $this->plano,
                    'status' => 'pendente_emissao',
                ]
            );

            // 2. Aciona a API da Iza Seguradora
            $resposta = $izaService->emitirApolice($this->motorista, $this->plano);

            // 3. Atualiza com o ID externo (Protocolo/Policy ID) retornado pela Iza.
            // Conforme a cotação, a emissão final pode levar até 24h, então o Webhook 
            // fará a virada do status para 'ativo' posteriormente[cite: 1].
            $seguro->update([
                'iza_policy_id' => $resposta['policy_id'] ?? $resposta['protocolo'] ?? null,
                'payload_retorno' => $resposta,
            ]);

            Log::info('[IZA_JOB] Solicitação enviada com sucesso', [
                'motorista_id' => $this->motorista->id,
                'iza_policy_id' => $seguro->iza_policy_id
            ]);

        } catch (Exception $e) {
            Log::error('[IZA_JOB] Falha ao solicitar seguro', [
                'motorista_id' => $this->motorista->id,
                'erro' => $e->getMessage()
            ]);

            // Dispara a exceção novamente para forçar o Redis/Worker a tentar de novo
            throw $e;
        }
    }
}