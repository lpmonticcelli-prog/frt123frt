<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Events\CargaAtualizada;
use App\Http\Controllers\Controller;
use App\Models\Carga;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PodController extends Controller
{
    /**
     * Gera URL assinada (Pre-Signed URL) para upload direto ao Object Storage (S3/Spaces).
     * Olerância Zero de I/O de disco nos workers da aplicação.
     */
    public function gerarUrlUpload(Request $request, Carga $carga): JsonResponse
    {
        $motoristaId = $request->user()->motorista->id ?? null;

        if (!$motoristaId || $carga->motorista_id !== $motoristaId) {
            throw new AuthorizationException('Carga não pertence ao motorista autenticado.');
        }

        // Validação da máquina de estado: Apenas cargas em trânsito podem receber POD.
        if ($carga->status !== 'em_viagem') {
            return response()->json([
                'message' => 'Status atual da carga não permite envio de comprovantes.',
                'current_status' => $carga->status
            ], 422);
        }

        $validated = $request->validate([
            'extensao' => ['required', 'string', 'in:jpg,jpeg,png,pdf']
        ]);

        $uuid = Str::uuid()->toString();
        $path = "pods/cargas/{$carga->id}/{$uuid}.{$validated['extensao']}";

        // Gera URL temporária válida por 15 minutos utilizando o disco cloud configurado
        $uploadUrl = Storage::disk('s3')->temporaryUploadUrl(
            $path,
            now()->addMinutes(15)
        );

        return response()->json([
            'upload_url' => $uploadUrl,
            'file_path'  => $path,
            'expires_in' => 900 // segundos
        ], 200);
    }

    /**
     * Confirma a entrega e transaciona o status da carga.
     * Utiliza Row-Level Locking (Pessimistic Lock) para evitar Race Conditions.
     */
    public function confirmarEntrega(Request $request, Carga $carga): JsonResponse
    {
        $validated = $request->validate([
            'foto_canhoto_path' => ['required', 'string', 'max:255'],
            'foto_carga_path'   => ['nullable', 'string', 'max:255'],
            'latitude_entrega'  => ['required', 'numeric', 'between:-90,90'],
            'longitude_entrega' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $motoristaId = $request->user()->motorista->id ?? null;

        if (!$motoristaId || $carga->motorista_id !== $motoristaId) {
            throw new AuthorizationException('Carga não pertence ao motorista autenticado.');
        }

        try {
            DB::transaction(function () use ($carga, $validated) {
                // LOCK CONTENTION DEFENSE: Trava o registro na tabela durante a transação
                $cargaLocked = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

                // Double-check da máquina de estado após adquirir o lock
                if ($cargaLocked->status !== 'em_viagem') {
                    throw new DomainException('A carga não se encontra em estado válido para finalização.');
                }

                $cargaLocked->status = 'em_auditoria';
                $cargaLocked->foto_canhoto = $validated['foto_canhoto_path'];
                
                if (isset($validated['foto_carga_path'])) {
                    $cargaLocked->foto_carga = $validated['foto_carga_path'];
                }

                // Preservar telemetria do ponto exato de baixa
                $cargaLocked->latitude_entrega = $validated['latitude_entrega'];
                $cargaLocked->longitude_entrega = $validated['longitude_entrega'];
                $cargaLocked->data_entrega = now();

                $cargaLocked->save();

                // EVENT-DRIVEN: Notifica o barramento de eventos (acionando possivelmente o Escrow/Financeiro)
                event(new CargaAtualizada($cargaLocked));
            });

            return response()->json([
                'message' => 'Comprovantes processados com sucesso. A carga foi enviada para auditoria.'
            ], 200);

        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
            
        } catch (Throwable $e) {
            Log::error('Erro transacional na baixa de POD da Carga.', [
                'carga_id' => $carga->id,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Falha crítica ao processar a finalização da carga. Operação abortada.'
            ], 500);
        }
    }
}