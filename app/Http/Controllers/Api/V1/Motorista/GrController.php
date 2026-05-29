<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Partners\TransSatService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class GrController extends Controller
{
    /**
     * Inicia o processo de Background Check e Biometria na Gerenciadora de Risco.
     */
    public function solicitarAnalise(Request $request, TransSatService $transSatService): JsonResponse
    {
        $user = $request->user();
        $motorista = $user->motorista;

        if (!$motorista) {
            return response()->json(['error' => 'Perfil logístico não identificado.'], 404);
        }

        if (in_array($motorista->gr_status, ['pendente', 'aprovado', 'aguardando_biometria'])) {
            return response()->json(['error' => 'Já existe uma auditoria em processamento ou finalizada.'], 400);
        }

        try {
            // ==========================================
            // ZT-DEFENSE: Delegação de Responsabilidade
            // ==========================================
            // Não montamos arrays aqui. Passamos o objeto Motorista para o Service,
            // que extrai os dados diretamente do banco, garantindo que o payload não foi adulterado.
            $referencia = $transSatService->enviarParaAnalise($motorista);

            // Persistência Atômica do Estado
            $motorista->update([
                'gr_status'        => 'pendente', // Inicialmente pendente, será atualizado via Webhook
                'gr_referencia'    => $referencia,
                // A URL de biometria pode ser atualizada posteriormente via Webhook da Trans Sat, 
                // caso o link seja gerado do lado deles de forma assíncrona.
            ]);

            Log::info("[TRANSAT] Background Check iniciado. Motorista ID: {$motorista->id} | Ref: {$referencia}");

            return response()->json([
                'message'   => 'Dossiê enviado com sucesso à Gerenciadora de Risco.',
                'gr_status' => 'pendente'
            ], 200);

        } catch (Exception $e) {
            // O catch agora captura perfeitamente as mensagens de erro limpas do nosso Service
            Log::error("[TRANSAT ORCHESTRATION ERROR] Motorista ID: {$motorista->id}. Motivo: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}