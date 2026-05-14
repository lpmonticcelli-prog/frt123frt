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
     * Motor de agregação do Radar de Faturamento.
     * O(1) N+1 query prevention com mapeamento DTO para front-end.
     */
    public function radar(): JsonResponse
    {
        // Delegação de cálculos matemáticos ao motor do banco de dados (evita OOM de RAM no PHP)
        // Correção: O status pertence à tabela `users`, acessada via relacionamento `user`.
        $metrics = [
            'receita_prevista' => (float) Fatura::where('status', 'pendente')->sum('valor_total'),
            'receita_realizada' => (float) Fatura::where('status', 'paga')->sum('valor_total'),
            'total_inadimplencia' => (float) Fatura::inadimplentes()->sum('valor_total'),
            'contas_congeladas' => Embarcador::whereHas('user', function ($query) {
                $query->where('status', 'congelado');
            })->count(),
        ];

        // Fetch seletivo com Eager Loading blindado
        $faturasRisco = Fatura::with([
                'embarcador:id,user_id,razao_social,cnpj',
                'embarcador.user:id,status' // Carrega a entidade pai para acessar o status
            ])
            ->inadimplentes()
            ->orderBy('data_vencimento', 'asc')
            ->limit(100) // Trava de segurança para impedir saturação de payload
            ->get()
            ->map(function ($fatura) {
                // Adaptação DTO: O Frontend espera `fatura.embarcador.status`.
                // Injetamos o valor em tempo de execução e ocultamos o node `user`.
                if ($fatura->embarcador && $fatura->embarcador->user) {
                    $fatura->embarcador->setAttribute('status', $fatura->embarcador->user->status);
                    $fatura->embarcador->makeHidden('user');
                } else {
                    $fatura->embarcador->setAttribute('status', 'ativo'); // Fallback de segurança
                }
                return $fatura;
            });

        return response()->json([
            'metrics' => $metrics,
            'faturas_risco' => $faturasRisco
        ]);
    }

    /**
     * Aplica bloqueio severo em contas com títulos podres.
     * Requer lock de linha (Row-level locking) para evitar race conditions em múltiplas requisições simultâneas.
     */
    public function congelar(int $embarcadorId): JsonResponse
    {
        try {
            DB::transaction(function () use ($embarcadorId) {
                // Carrega o Embarcador e sua entidade Root (User)
                $embarcador = Embarcador::with('user')->findOrFail($embarcadorId);
                
                if (!$embarcador->user) {
                    throw new Exception('Identidade de autenticação isolada. Corrupção de dados estrutural.');
                }

                // LOCK FOR UPDATE deve ocorrer na entidade que será mutada (User)
                $user = $embarcador->user()->lockForUpdate()->first();
                
                if ($user->status === 'congelado') {
                    throw new Exception('O status desta conta já se encontra congelado na malha primária.');
                }

                $user->status = 'congelado';
                $user->save();

                // NOTA ARQUITETURAL: Aqui deve ser disparado um Evento (Ex: EmbarcadorCongeladoEvent)
                // que tenha um Listener responsável por revogar todos os Personal Access Tokens (Sanctum) ativos deste usuário.
            });

            return response()->json(['message' => 'Intervenção executada. Conta isolada com sucesso.']);
            
        } catch (Exception $e) {
            return response()->json(['error' => 'Falha no lock transacional: ' . $e->getMessage()], 409);
        }
    }
}