<?php

namespace App\Http\Controllers\Api\V1\Partners;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrIntegrationController extends Controller
{
    /**
     * Endpoint UNIVERSAL para qualquer GR (Gerenciadora de Risco) ou Seguradora
     * devolver o resultado da análise de perfil do Motorista.
     */
    public function registrarAnalise(Request $request)
    {
        // 1. O Parceiro só entra se enviar exatamente os dados que exigimos
        $validated = $request->validate([
            'protocolo_interno' => 'required|string|exists:cargas,id',
            'status_analise' => 'required|in:aprovado,reprovado,pendente_documento',
            'codigo_liberacao' => 'required_if:status_analise,aprovado|string|max:100',
            'motivo_reprovacao' => 'required_if:status_analise,reprovado|string|max:255',
            'cnpj_gerenciadora' => 'required|string|max:18'
        ]);

        $cargaId = $validated['protocolo_interno'];
        $statusGr = $validated['status_analise'];

        DB::beginTransaction();
        try {
            // Travamos a linha da carga para evitar concorrência (Lock For Update)
            $carga = Carga::where('id', $cargaId)
                ->where('status', 'em_analise_gr')
                ->lockForUpdate()
                ->firstOrFail();

            if ($statusGr === 'aprovado') {
                // Motorista aprovado: A carga está pronta para gerar o CIOT e iniciar viagem
                $carga->status = 'aceita';
                $carga->save();

                // Registamos na Auditoria 360 (Invisível para a Seguradora)
                Log::channel('auditoria')->info("GR Aprovada: Carga #{$carga->id} liberada pela GR {$validated['cnpj_gerenciadora']}. Código: {$validated['codigo_liberacao']}");
            } 
            else if ($statusGr === 'reprovado') {
                // Motorista reprovado: Retiramos o motorista e devolvemos a carga ao mural
                $motoristaReprovado = $carga->motorista_id;
                
                $carga->motorista_id = null;
                $carga->status = 'publicada'; 
                $carga->save();

                Log::channel('auditoria')->warning("GR Reprovada: Motorista ID {$motoristaReprovado} bloqueado na Carga #{$carga->id} pela GR {$validated['cnpj_gerenciadora']}. Motivo: {$validated['motivo_reprovacao']}");
            }

            DB::commit();

            // 2. A resposta que a seguradora recebe é minúscula e não revela nada do sistema
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Análise de risco processada e averbada com sucesso.',
                'timestamp' => now()->toIso8601String()
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Falha Crítica na API GR: " . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro interno ao processar o protocolo. Contacte o NOC 123fretei.'
            ], 500);
        }
    }
}