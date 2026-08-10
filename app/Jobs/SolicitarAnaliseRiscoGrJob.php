<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Carga;
use App\Contracts\RiskManagementInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SolicitarAnaliseRiscoGrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $cargaId;

    /**
     * O número de vezes que o job pode tentar executar caso haja falha de rede na API real.
     */
    public int $tries = 3;

    public function __construct(int $cargaId)
    {
        $this->cargaId = $cargaId;
    }

    /**
     * ZT-DEFENSE: Injeção de Dependência via Contrato (Padrão Strategy).
     * O Service Container do Laravel injetará automaticamente o BypassRiskManager 
     * ou o motor real de GR com base na configuração do AppServiceProvider.
     */
    public function handle(RiskManagementInterface $riskManager): void
    {
        $carga = Carga::with(['embarcador', 'motorista'])->find($this->cargaId);

        if (!$carga || $carga->status !== 'em_analise_gr') {
            Log::warning("Job GR: Carga {$this->cargaId} ignorada. Status inválido ou deletada.");
            return;
        }

        try {
            // Delegação atômica para o contrato de domínio. Oculta I/O de rede do Job.
            $response = $riskManager->consultarRisco(
                $this->mapearDadosMotorista($carga),
                $this->mapearDadosVeiculo($carga)
            );

            // Resolução Síncrona / Bypass: 
            // Se o motor fantasma (ou a API real) retornar 'aprovado' de imediato, avançamos a máquina de estados.
            if (($response['status'] ?? '') === 'aprovado') {
                $this->atualizarStatus($carga, 'disponivel');
            } else {
                Log::info("Job GR: Carga {$carga->id} aguardando retorno assíncrono (Webhook) do parceiro.");
            }

        } catch (\Exception $e) {
            Log::error("Job GR Colapso [Carga {$this->cargaId}]: " . $e->getMessage());
            // Força a falha para entrar no mecanismo de retentativa do Queue Worker
            $this->fail($e);
        }
    }

    private function atualizarStatus(Carga $carga, string $status): void
    {
        $carga->status = $status;
        $carga->save();
        Log::info("Job GR: Carga {$carga->id} transicionada para {$status}.");
    }

    private function mapearDadosMotorista(Carga $carga): array
    {
        return [
            'cpf'               => $carga->motorista->cpf ?? 'N/A',
            'cnh'               => $carga->motorista->cnh ?? 'N/A',
            'embarcador_cnpj'   => $carga->embarcador->cnpj ?? 'N/A',
            'origem_uf'         => $carga->uf_origem,
            'destino_uf'        => $carga->uf_destino,
            'valor_mercadoria'  => $carga->valor_mercadoria ?? 0.00,
            'protocolo_interno' => $carga->id,
        ];
    }

    private function mapearDadosVeiculo(Carga $carga): array
    {
        return [
            'placa_cavalo'  => $carga->motorista->placa_veiculo ?? 'N/A',
            'placa_carreta' => $carga->motorista->placa_carreta ?? 'N/A'
        ];
    }
}