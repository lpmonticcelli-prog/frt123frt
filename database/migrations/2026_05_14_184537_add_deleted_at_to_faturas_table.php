<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fatura;
use App\Models\Embarcador;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class FaturamentoController extends Controller
{
    /**
     * Motor de agregação do Radar de Faturamento B2B.
     * O(1) N+1 query prevention com adaptação de contrato para o Front-end.
     */
    public function radar(): JsonResponse
    {
        // Delegação ao motor de banco de dados. 
        // CORREÇÃO ARQUITETURAL: O status de bloqueio pertence à tabela de autenticação (users).
        $metrics = [
            'receita_prevista' => (float) Fatura::where('status', 'pendente')->sum('valor_total'),
            'receita_realizada' => (float) Fatura::where('status', 'paga')->sum('valor_total'),
            'total_inadimplencia' => (float) Fatura::inadimplentes()->sum('valor_total'),
            'contas_congeladas' => Embarcador::whereHas('user', function ($query) {
                $query->where('status', 'congelado'); // Adapte para 'inativo' se for o seu padrão no User
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
                // para que o Front-end Vue (fatura.embarcador.status) não quebre.
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
                $user->status = 'congelado'; // Adapte para 'inativo' se necessário
                $user->save();
            });

            return response()->json(['message' => 'Intervenção executada. Conta do Embarcador suspensa por inadimplência.']);
            
        } catch (Exception $e) {
            return response()->json(['error' => 'Falha no lock transacional: ' . $e->getMessage()], 409);
        }
    }
}