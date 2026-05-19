<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fatura;
use App\Models\Carga;
use App\Models\Embarcador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class FaturamentoController extends Controller
{
    /**
     * Motor de agregação do Radar de Faturamento B2B.
     * O(1) N+1 query prevention com adaptação de contrato para o Front-end.
     */
    public function radar(): JsonResponse
    {
        // Delegação ao motor de banco de dados.
        $metrics = [
            'receita_prevista' => (float) Fatura::where('status', 'pendente')->sum('valor_total'),
            'receita_realizada' => (float) Fatura::where('status', 'paga')->sum('valor_total'),
            'total_inadimplencia' => (float) Fatura::inadimplentes()->sum('valor_total'),
            'contas_congeladas' => Embarcador::whereHas('user', function ($query) {
                $query->where('status', 'congelado'); 
            })->count(),
        ];

        // Fetch seletivo com Eager Loading na sub-relação "user"
        $faturasRisco = Fatura::with([
                'embarcador:id,user_id,razao_social,cnpj', 
                'embarcador.user:id,status'
            ])
            ->inadimplentes()
            ->orderBy('data_vencimento', 'asc')
            ->limit(100)
            ->get()
            ->map(function ($fatura) {
                // ADAPTAÇÃO DE CONTRATO (DTO): Injeta o status do User diretamente no Embarcador 
                if ($fatura->embarcador && $fatura->embarcador->user) {
                    $fatura->embarcador->setAttribute('status', $fatura->embarcador->user->status);
                    $fatura->embarcador->makeHidden('user'); // Expurga o aninhamento pesado para economizar banda
                } else {
                    $fatura->embarcador->setAttribute('status', 'ativo');
                }
                
                return $fatura;
            });

        return response()->json([
            'metrics' => $metrics,
            'faturas_risco' => $faturasRisco
        ]);
    }

    /**
     * Aplica bloqueio severo em contas com títulos podres via Row-Level Locking.
     */
    public function congelar(int $embarcadorId): JsonResponse
    {
        try {
            DB::transaction(function () use ($embarcadorId) {
                // Identifica a malha do Embarcador e sua entidade Root (User)
                $embarcador = Embarcador::with('user')->findOrFail($embarcadorId);
                
                if (!$embarcador->user) {
                    throw new Exception('Identidade de autenticação isolada. Corrupção de dados detectada.');
                }

                // LOCK FOR UPDATE na tabela de Users
                $user = $embarcador->user()->lockForUpdate()->first();
                
                if ($user->status === 'congelado') {
                    throw new Exception('O status desta conta já se encontra congelado na malha primária.');
                }

                // Efetua a suspensão lógica da conta
                $user->status = 'congelado'; 
                $user->save();
                
                Log::warning("[Compliance] Embarcador {$embarcadorId} suspenso por alto risco de inadimplência.");
            });

            return response()->json(['message' => 'Intervenção executada. Conta do Embarcador suspensa por inadimplência.']);
            
        } catch (Exception $e) {
            return response()->json(['error' => 'Falha no lock transacional: ' . $e->getMessage()], 409);
        }
    }

    /**
     * Lista o histórico consolidado de faturas geradas (Ciclos Financeiros).
     */
    public function listarCiclos(Request $request): JsonResponse
    {
        $faturas = Fatura::with('embarcador.user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($faturas, 200);
    }

    /**
     * OPERAÇÃO CRÍTICA (ACID): Varre cargas finalizadas sem fatura e as consolida em novos títulos financeiros.
     */
    public function gerarFaturasManuais(): JsonResponse
    {
        try {
            $faturasGeradas = DB::transaction(function () {
                // 1. Lock pessimista em todas as cargas prontas para faturamento
                $cargasElegiveis = Carga::whereNull('fatura_id')
                    ->whereIn('status', ['entregue', 'finalizada', 'concluida'])
                    ->lockForUpdate()
                    ->get();

                if ($cargasElegiveis->isEmpty()) {
                    return 0;
                }

                // 2. Agrupamento em memória O(N) por Embarcador
                $cargasPorEmbarcador = $cargasElegiveis->groupBy('embarcador_id');
                $count = 0;

                // 3. Geração Atômica de Títulos
                foreach ($cargasPorEmbarcador as $embarcadorId => $cargas) {
                    $valorTotalFrete = $cargas->sum('valor_frete');
                    
                    $fatura = Fatura::create([
                        'embarcador_id' => $embarcadorId,
                        'valor_total' => $valorTotalFrete,
                        'data_vencimento' => Carbon::now()->addDays(15), // D+15 Padrão de repasse
                        'status' => 'pendente'
                    ]);

                    // Vincula as cargas à fatura gerada (Impede dupla faturação no futuro)
                    Carga::whereIn('id', $cargas->pluck('id'))->update(['fatura_id' => $fatura->id]);
                    $count++;
                }

                return $count;
            });

            if ($faturasGeradas === 0) {
                return response()->json(['message' => 'Nenhuma carga pendente de faturamento encontrada no sistema.'], 200);
            }

            return response()->json(['message' => "Fechamento de ciclo concluído. {$faturasGeradas} nova(s) fatura(s) gerada(s) com sucesso."], 201);

        } catch (Exception $e) {
            Log::error("[Faturamento] Falha catastrófica ao gerar faturas em lote: " . $e->getMessage());
            return response()->json(['error' => 'Falha transacional ao tentar fechar o ciclo de faturamento.'], 500);
        }
    }

    /**
     * Extração analítica detalhada da receita bruta da Plataforma (Spread/Taxa).
     */
    public function extratoTaxasPlataforma(Request $request): JsonResponse
    {
        $extrato = Carga::with(['embarcador', 'motorista'])
            ->whereNotNull('taxa_plataforma')
            ->where('taxa_plataforma', '>', 0)
            ->whereIn('status', ['entregue', 'finalizada', 'concluida'])
            ->orderBy('created_at', 'desc')
            ->select('id', 'embarcador_id', 'motorista_id', 'valor_frete', 'taxa_plataforma', 'status', 'created_at')
            ->paginate(50);

        return response()->json($extrato, 200);
    }

    /**
     * Retorna os KPIs consolidados da monetização da plataforma.
     */
    public function taxasAgregadas(): JsonResponse
    {
        $taxaRealizada = Carga::whereIn('status', ['entregue', 'finalizada', 'concluida'])->sum('taxa_plataforma');
        $taxaEmTransito = Carga::whereIn('status', ['alocada', 'em_transito', 'em_auditoria'])->sum('taxa_plataforma');

        return response()->json([
            'lucro_realizado' => (float) $taxaRealizada,
            'lucro_em_pipeline' => (float) $taxaEmTransito,
            'potencial_total' => (float) ($taxaRealizada + $taxaEmTransito)
        ], 200);
    }
}