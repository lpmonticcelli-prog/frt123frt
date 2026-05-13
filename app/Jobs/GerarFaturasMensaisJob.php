<?php

namespace App\Jobs;

use App\Models\Embarcador;
use App\Models\Fatura;
use App\Models\Carga;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GerarFaturasMensaisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Opcional: Permitir forçar o mês/ano de processamento. 
     * Se nulo, processa o mês anterior ao atual.
     */
    protected ?string $mesReferencia;

    public function __construct(?string $mesReferencia = null)
    {
        $this->mesReferencia = $mesReferencia;
    }

    public function handle(): void
    {
        // Define o mês e ano a processar (Ex: se estamos em Maio, processa as cargas de Abril)
        if ($this->mesReferencia) {
            $dataReferencia = Carbon::createFromFormat('m/Y', $this->mesReferencia)->startOfMonth();
        } else {
            $dataReferencia = now()->subMonth()->startOfMonth();
        }

        $mesFormatado = $dataReferencia->format('m/Y'); // "04/2026"
        $inicioMes = $dataReferencia->copy()->startOfMonth();
        $fimMes = $dataReferencia->copy()->endOfMonth();

        Log::info("[Faturamento] Iniciando consolidação de faturas para o período: {$mesFormatado}");

        // Busca todos os embarcadores ativos
        $embarcadores = Embarcador::whereHas('user', function($q) {
            $q->where('status', 'active');
        })->get();

        foreach ($embarcadores as $embarcador) {
            // Garante que não geramos fatura duplicada
            $faturaExistente = Fatura::where('embarcador_id', $embarcador->id)
                                     ->where('mes_referencia', $mesFormatado)
                                     ->exists();

            if ($faturaExistente) {
                Log::info("[Faturamento] Fatura já existe para o Embarcador {$embarcador->id} em {$mesFormatado}. Pulando.");
                continue;
            }

            // Busca as cargas ENTREGUES neste mês específico
            $cargasFaturaveis = Carga::where('embarcador_id', $embarcador->id)
                ->whereIn('status', ['entregue', 'finalizada', 'concluida'])
                ->whereBetween('updated_at', [$inicioMes, $fimMes])
                ->get();

            // Se o Embarcador não teve operações E não tem mensalidade fixa, pula
            if ($cargasFaturaveis->isEmpty() && !$embarcador->mensalidade_fixa) {
                continue;
            }

            DB::transaction(function () use ($embarcador, $cargasFaturaveis, $mesFormatado) {
                
                // 1. Soma das taxas das cargas (Comissões)
                $somaTaxasVariaveis = $cargasFaturaveis->sum('taxa_plataforma');
                
                // 2. Adiciona a mensalidade SaaS (se existir no contrato)
                $mensalidade = $embarcador->mensalidade_fixa ?? 0;
                
                $valorTotalFatura = $somaTaxasVariaveis + $mensalidade;

                // Extrai apenas os IDs e os valores para o JSON de auditoria
                $detalhesCargas = $cargasFaturaveis->map(function ($carga) {
                    return [
                        'id' => $carga->id,
                        'rota' => "{$carga->cidade_origem}/{$carga->uf_origem} -> {$carga->cidade_destino}/{$carga->uf_destino}",
                        'taxa_cobrada' => (float) $carga->taxa_plataforma
                    ];
                })->toArray();

                Fatura::create([
                    'embarcador_id' => $embarcador->id,
                    'mes_referencia' => $mesFormatado,
                    'valor_total' => $valorTotalFatura,
                    'status' => 'pendente',
                    'data_vencimento' => now()->startOfMonth()->addDays(4), // Vence dia 5
                    'detalhes_cargas' => [
                        'cargas' => $detalhesCargas,
                        'resumo' => [
                            'total_taxas_variaveis' => $somaTaxasVariaveis,
                            'mensalidade_fixa' => $mensalidade
                        ]
                    ]
                ]);

                Log::info("[Faturamento] Fatura gerada para Embarcador {$embarcador->id} | Valor: R$ {$valorTotalFatura}");
            });
        }

        Log::info("[Faturamento] Processo de consolidação concluído para: {$mesFormatado}");
    }
}