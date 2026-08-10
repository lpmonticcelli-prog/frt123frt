<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Embarcador;
use App\Services\ReceitaWSService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PerfilController extends Controller
{
    private const ROLE_EMBARCADOR = 'embarcador';
    private const STATUS_PENDENTE = 'pending';
    private const STATUS_EM_ANALISE = 'em_analise';

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['role', 'embarcador']);
        
        if (!$user->role || $user->role->slug !== self::ROLE_EMBARCADOR || !$user->embarcador) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $embarcador = $user->embarcador;

        return response()->json([
            'razao_social' => $embarcador->razao_social,
            'cnpj' => $embarcador->cnpj,
            'inscricao_estadual' => $embarcador->inscricao_estadual,
            'telefone' => $user->phone,
            'status_conta' => $user->status,
            'documento_kyc_url' => $embarcador->documento_kyc ? url("/api/v1/embarcador/perfil/documento") : null,
        ], 200);
    }

    public function update(Request $request, ReceitaWSService $receitaWSService): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['role', 'embarcador']);
        
        if (!$user->role || $user->role->slug !== self::ROLE_EMBARCADOR || !$user->embarcador) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $embarcador = $user->embarcador;

        $validated = $request->validate([
            'razao_social' => 'required|string|max:255',
            'cnpj' => [
                'required', 'string', 'max:20',
                function ($attribute, $value, $fail) use ($embarcador) {
                    $cnpjLimpo = preg_replace('/[^0-9]/', '', $value);
                    if (Embarcador::where('cnpj', $cnpjLimpo)->where('id', '!=', $embarcador->id)->exists()) {
                        $fail('Este CNPJ já está cadastrado em outra conta corporativa.');
                    }
                }
            ],
            'inscricao_estadual' => 'nullable|string|max:50',
            'telefone' => 'required|string|max:20',
            'documento_kyc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $statusConta = $user->status;
        $documentoPathAtual = $embarcador->documento_kyc;
        $novoDocumentoUpload = null; 

        $cnpjLimpo = preg_replace('/[^0-9]/', '', $validated['cnpj']);
        $cnpjAlterado = $embarcador->cnpj !== $cnpjLimpo;
        $enviouNovoDocumento = $request->hasFile('documento_kyc');

        if ($cnpjAlterado || $statusConta === self::STATUS_PENDENTE) {
            try {
                $analiseCNPJ = $receitaWSService->validarCNPJ($cnpjLimpo);
                if (!$analiseCNPJ['valido']) {
                    return response()->json(['message' => $analiseCNPJ['mensagem']], 422);
                }
            } catch (Throwable $e) {
                return response()->json(['error' => 'Falha ao validar CNPJ com a Receita Federal.'], 503);
            }
        }

        if ($cnpjAlterado || $enviouNovoDocumento) {
            $statusConta = self::STATUS_EM_ANALISE;
        }

        try {
            if ($enviouNovoDocumento) {
                $file = $request->file('documento_kyc');
                $novoDocumentoUpload = $file->storeAs('kyc/embarcadores_' . $embarcador->id, $file->hashName());
            }
        } catch (Throwable $e) {
            return response()->json(['error' => 'Falha ao processar o upload do documento KYC.'], 422);
        }

        DB::beginTransaction();

        try {
            $user->update([
                'phone' => preg_replace('/[^0-9]/', '', $validated['telefone']),
                'status' => $statusConta
            ]);

            $embarcador->update([
                'razao_social' => $validated['razao_social'],
                'cnpj' => $cnpjLimpo,
                'inscricao_estadual' => $validated['inscricao_estadual'] ?? null,
                'documento_kyc' => $novoDocumentoUpload ?? $documentoPathAtual,
            ]);

            DB::commit();

            if ($enviouNovoDocumento && $documentoPathAtual && Storage::exists($documentoPathAtual)) {
                Storage::delete($documentoPathAtual);
            }

            return response()->json([
                'message' => 'Perfil atualizado com sucesso.',
                'status_conta' => $statusConta,
                'documento_kyc_url' => ($novoDocumentoUpload ?? $documentoPathAtual) ? url("/api/v1/embarcador/perfil/documento") : null,
            ], 200);

        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            if ($novoDocumentoUpload && Storage::exists($novoDocumentoUpload)) Storage::delete($novoDocumentoUpload);
            
            Log::error('[KYC Embarcador] Erro ao atualizar perfil', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha interna ao atualizar dados.'], 500);
        }
    }

    public function exibirDocumento(Request $request): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        $path = $user->embarcador->documento_kyc ?? null;
        if (!$path || !Storage::exists($path)) {
            return response()->json(['error' => 'Documento não localizado.'], 404);
        }
        return Storage::response($path);
    }
}