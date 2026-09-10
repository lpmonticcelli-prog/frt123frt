<?php

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use App\Services\IzaIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SeguroController extends Controller
{
    /**
     * Retorna o status atual do seguro do motorista logado e o catálogo de opções.
     */
    public function meuSeguro(Request $request): JsonResponse
    {
        $motorista = $request->user()->motorista;

        // Injeção do catálogo de seguros para manter o painel de opções sempre visível
        $planosDisponiveis = [
            [
                'id' => 'basico',
                'nome' => 'IZA Básico',
                'descricao' => 'Cobertura para Acidentes Pessoais durante as viagens.',
                'valor_mensal' => 'R$ 34,90'
            ],
            [
                'id' => 'intermediario',
                'nome' => 'IZA Intermediário',
                'descricao' => 'Maior cobertura para você e cuidados com a saúde.',
                'valor_mensal' => 'R$ 59,90'
            ],
            [
                'id' => 'superior',
                'nome' => 'IZA Superior',
                'descricao' => 'Proteção máxima extensiva para sua família.',
                'valor_mensal' => 'R$ 99,90'
            ]
        ];

        if (!$motorista || $motorista->seguro_iza_status !== 'ativo') {
            return response()->json([
                'possui_seguro'      => false,
                'status'             => $motorista ? $motorista->seguro_iza_status : 'inativo',
                'planos_disponiveis' => $planosDisponiveis
            ]);
        }

        return response()->json([
            'possui_seguro'      => true,
            'plano'              => $motorista->seguro_iza_plano,
            'status'             => $motorista->seguro_iza_status,
            'is_ativo'           => true,
            'vigencia_fim'       => $motorista->seguro_iza_vencimento,
            'planos_disponiveis' => $planosDisponiveis
        ]);
    }

    /**
     * Recebe o gatilho de contratação direto do modal (SeguroBloqueioModal).
     */
    public function contratar(Request $request, IzaIntegrationService $izaService): JsonResponse
    {
        $request->validate([
            'plano' => ['required', 'string'] // Ex: 'iza_basico'
        ]);

        $motorista = $request->user()->motorista;

        // Regra de Negócio: Impede contratação duplicada
        if ($motorista->seguro_iza_status === 'ativo' && $motorista->seguro_iza_vencimento > now()) {
            return response()->json([
                'error' => 'Você já possui uma apólice de seguro ativa.'
            ], 422);
        }

        try {
            // Chama o serviço (Modo Simulação/Integração)
            $policyId = $izaService->contratarPlano($motorista, $request->plano);

            // Grava no banco de dados com save() para evitar bloqueio de fillable
            $motorista->seguro_iza_id = $policyId;
            $motorista->seguro_iza_status = 'ativo';
            $motorista->seguro_iza_plano = $request->plano;
            $motorista->seguro_iza_vencimento = now()->addDays(30);
            $motorista->save();

            Log::info('[SEGURO] Nova apólice IZA contratada via Modal.', [
                'motorista_id' => $motorista->id, 
                'policy_id'    => $policyId,
                'ip'           => $request->ip()
            ]);

            // Resposta síncrona (200 OK) para destravar o frontend e liberar o aceite do frete
            return response()->json([
                'message'   => 'Seguro ativado com sucesso! Cobertura iniciada.',
                'status'    => 'ativo',
                'policy_id' => $policyId
            ], 200);

        } catch (\Exception $e) {
            Log::error('[IZA INTEGRATION ERROR] Falha na emissão de seguro: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Falha ao processar o seguro com a IZA. Tente novamente em instantes.'
            ], 500);
        }
    }

    /**
     * Cancela a apólice de seguro ativa do motorista.
     */
    public function cancelar(Request $request, IzaIntegrationService $izaService): JsonResponse
    {
        $motorista = $request->user()->motorista;

        // Verifica se realmente existe um seguro ativo para cancelar
        if (!$motorista || $motorista->seguro_iza_status !== 'ativo') {
            return response()->json([
                'error' => 'Você não possui uma apólice ativa no momento.'
            ], 422);
        }

        try {
            $policyId = $motorista->seguro_iza_id ?? '';

            // Dispara o cancelamento no endpoint da IZA Seguradora
            $izaService->cancelarPlano($policyId);

            // CORREÇÃO: Atribuição direta garante que o banco de dados será atualizado
            $motorista->seguro_iza_status = 'cancelado';
            $motorista->seguro_iza_plano = null;
            $motorista->seguro_iza_vencimento = null;
            $motorista->save();

            Log::info('[SEGURO] Apólice IZA cancelada pelo motorista.', [
                'motorista_id' => $motorista->id, 
                'policy_id'    => $policyId
            ]);

            return response()->json([
                'message' => 'Seguro cancelado com sucesso. A cobertura foi encerrada e não haverá novos descontos.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('[IZA INTEGRATION ERROR] Falha ao cancelar seguro: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Falha ao processar o cancelamento com a IZA. Tente novamente em instantes.'
            ], 500);
        }
    }
}