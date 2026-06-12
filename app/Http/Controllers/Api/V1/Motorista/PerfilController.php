<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\PolyglotShieldService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class PerfilController extends Controller
{
    private const ROLE_MOTORISTA = 'motorista';
    private const STATUS_EM_ANALISE = 'em_analise';
    private const STATUS_PENDENTE = 'pendente';
    private const STATUS_REJEITADO = 'rejeitado';
    private const GR_NAO_SOLICITADO = 'nao_solicitado';

    private const DOCUMENTOS_PERMITIDOS = [
        'doc_cnh',
        'doc_selfie_cnh',
        'doc_rntrc',
        'doc_comprovante_endereco'
    ];

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['role', 'motorista']);

        if (!$user->role || $user->role->slug !== self::ROLE_MOTORISTA || !$user->motorista) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $motorista = $user->motorista;

        return response()->json([
            'id' => $motorista->id,
            'nome' => $user->name,
            'email' => $user->email,
            'telefone' => $user->phone,
            'cpf' => $motorista->cpf,
            'cnh' => $motorista->cnh,
            'validade_cnh' => $motorista->validade_cnh ? $motorista->validade_cnh->format('Y-m-d') : null,
            'rntrc' => $motorista->rntrc,
            'is_disponivel' => $motorista->is_disponivel,
            'status_conta' => $user->status,
            'status_verificacao' => $motorista->status_verificacao,
            'gr_status' => $motorista->gr_status ?? self::GR_NAO_SOLICITADO,
            'gr_referencia' => $motorista->gr_referencia,
            'gr_biometria_url' => $motorista->gr_biometria_url,
            
            'doc_cnh_url' => $motorista->doc_cnh ? url("/api/v1/motorista/perfil/documento/doc_cnh") : null,
            'doc_selfie_cnh_url' => $motorista->doc_selfie_cnh ? url("/api/v1/motorista/perfil/documento/doc_selfie_cnh") : null,
            'doc_rntrc_url' => $motorista->doc_rntrc ? url("/api/v1/motorista/perfil/documento/doc_rntrc") : null,
            'doc_comprovante_endereco_url' => $motorista->doc_comprovante_endereco ? url("/api/v1/motorista/perfil/documento/doc_comprovante_endereco") : null,
        ], 200);
    }

    public function update(Request $request, PolyglotShieldService $shield): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['role', 'motorista']);

        if (!$user->role || $user->role->slug !== self::ROLE_MOTORISTA || !$user->motorista) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $motorista = $user->motorista;

        $request->validate([
            'doc_cnh' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'doc_selfie_cnh' => 'nullable|file|mimes:jpeg,png,jpg|max:10240',
            'doc_rntrc' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'doc_comprovante_endereco' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ]);

        $updates = [];
        $filesUploaded = []; 
        $arquivosAntigos = [];
        $pathPrefix = 'kyc/motorista_' . $motorista->id;

        // ZT-DEFENSE: I/O Extraído. Executamos as gravações de disco ANTES da transação SQL.
        try {
            foreach (self::DOCUMENTOS_PERMITIDOS as $doc) {
                if ($request->hasFile($doc)) {
                    $path = $shield->sanitizeAndStore($request->file($doc), $pathPrefix);
                    $updates[$doc] = $path;
                    $filesUploaded[] = $path;
                    if ($motorista->$doc) {
                        $arquivosAntigos[] = $motorista->$doc;
                    }
                }
            }
        } catch (Throwable $e) {
            foreach ($filesUploaded as $file) { Storage::delete($file); }
            Log::alert('[Anti-Polyglot Block] Arquivo interceptado.', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }

        DB::beginTransaction();

        try {
            if (!empty($updates)) {
                $motorista->update($updates);

                if (in_array($user->status, ['pending', 'rejected'], true)) {
                    $user->update(['status' => self::STATUS_EM_ANALISE]);
                }
                
                if (in_array($motorista->status_verificacao, [self::STATUS_PENDENTE, self::STATUS_REJEITADO, null], true)) {
                    $motorista->update(['status_verificacao' => self::STATUS_EM_ANALISE]);
                }
            }

            DB::commit();

            if (!empty($arquivosAntigos)) {
                foreach ($arquivosAntigos as $antigo) {
                    if (Storage::exists($antigo)) Storage::delete($antigo);
                }
            }

            return response()->json([
                'message' => 'Pipeline de KYC atualizado com sucesso.',
                'status_conta' => $user->fresh()->status
            ], 200);

        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            foreach ($filesUploaded as $file) { if (Storage::exists($file)) Storage::delete($file); }
            return response()->json(['error' => 'Falha interna no processamento transacional.'], 500);
        }
    }

    public function exibirDocumento(Request $request, string $tipo): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        $path = $user->motorista->$tipo ?? null;
        if (!in_array($tipo, self::DOCUMENTOS_PERMITIDOS, true) || !$path || !Storage::exists($path)) {
            return response()->json(['error' => 'Vetor inválido ou arquivo não localizado.'], 404);
        }
        return Storage::response($path);
    }
}