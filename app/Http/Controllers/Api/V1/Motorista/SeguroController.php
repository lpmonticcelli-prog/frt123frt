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
     * Retorna o status atual do seguro do motorista logado.
     */
    public function meuSeguro(Request $request): JsonResponse
    {
        $motorista = $request->user()->motorista;

        if (!$motorista || $motorista->seguro_iza_status !== 'ativo') {
            return response()->json([
                'possui_seguro' => false,
                'status'        => $motorista ? $motorista->seguro_iza_status : 'inativo'
            ]);
        }

        return response()->json([
            'possui_seguro' => true,
            'plano'         => $motorista->seguro_iza_plano,
            'status'        => $motorista->seguro_iza_status,
            'is_ativo'      => true,
            'vigencia_fim'  => $motorista->seguro_iza_vencimento,
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

            // Grava no banco de dados e vincula a apólice instantaneamente
            $motorista->update([
                'seguro_iza_id'         => $policyId,
                'seguro_iza_status'     => 'ativo',
                'seguro_iza_plano'      => $request->plano,
                'seguro_iza_vencimento' => now()->addDays(30), // Cobertura padrão de 30 dias
            ]);

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
}