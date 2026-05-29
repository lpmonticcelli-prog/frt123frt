<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\ReceitaWSService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PerfilController extends Controller
{
    private const ROLE_EMBARCADOR = 'embarcador';
    private const STATUS_PENDENTE = 'pending';
    private const STATUS_EM_ANALISE = 'em_analise';

    /**
     * Retorna o Contrato de Dados (Payload) do perfil do Embarcador.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['role', 'embarcador']);
        
        if (!$user->role || $user->role->slug !== self::ROLE_EMBARCADOR || !$user->embarcador) {
            Log::warning('Tentativa de L7 de acesso a perfil de embarcador bloqueada.', ['user_id' => $user->id]);
            return response()->json(['error' => 'Acesso negado. Perfil corrompido ou tipo de conta inválido.'], 403);
        }

        $embarcador = $user->embarcador;

        return response()->json([
            'razao_social' => $embarcador->razao_social,
            'cnpj' => $embarcador->cnpj,
            'inscricao_estadual' => $embarcador->inscricao_estadual,
            'telefone' => $user->phone,
            'cep' => $embarcador->cep,
            'logradouro' => $embarcador->logradouro,
            'numero' => $embarcador->numero,
            'complemento' => $embarcador->complemento,
            'bairro' => $embarcador->bairro,
            'cidade' => $embarcador->cidade,
            'uf' => $embarcador->uf,
            'status_conta' => $user->status,
            
            // Proxy Autenticado (Independente de S3/Local)
            'documento_kyc_url' => $embarcador->documento_kyc ? url("/api/v1/embarcador/perfil/documento") : null,
        ], 200);
    }

    /**
     * Processamento ACID de Dados, ReceitaWS e Documentos (KYC).
     */
    public function update(Request $request, ReceitaWSService $receitaWSService): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['role', 'embarcador']);
        
        if (!$user->role || $user->role->slug !== self::ROLE_EMBARCADOR || !$user->embarcador) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $embarcador = $user->embarcador;

        // Validação Rígida
        $validated = $request->validate([
            'razao_social' => 'required|string|max:255',
            'cnpj' => [
                'required',
                'string',
                'max:20',
                Rule::unique('embarcadores', 'cnpj')->ignore($embarcador->id),
            ],
            'inscricao_estadual' => 'nullable|string|max:50',
            'telefone' => 'required|string|max:20',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'uf' => 'nullable|string|size:2',
            'documento_kyc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'cnpj.unique' => 'Este CNPJ já está cadastrado em outra conta.',
            'documento_kyc.mimes' => 'Arquivo suspeito. Apenas PDF, JPG ou PNG são permitidos.',
            'documento_kyc.max' => 'Payload recusa arquivos superiores a 5MB.'
        ]);

        $statusConta = $user->status;
        $documentoPathAtual = $embarcador->documento_kyc;
        $novoDocumentoUpload = null; // Track para Rollback de I/O

        $cnpjAlterado = $embarcador->cnpj !== $validated['cnpj'];
        $enviouNovoDocumento = $request->hasFile('documento_kyc');

        // Auditoria Externa (Antes da Transação para não prender o banco)
        if ($cnpjAlterado || $statusConta === self::STATUS_PENDENTE) {
            try {
                $analiseCNPJ = $receitaWSService->validarCNPJ($validated['cnpj']);
                if (!$analiseCNPJ['valido']) {
                    return response()->json(['message' => $analiseCNPJ['mensagem']], 422);
                }
            } catch (Throwable $e) {
                Log::error('Falha de Integração com ReceitaWS', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Falha ao validar CNPJ no servidor do governo. Tente novamente.'], 503);
            }
        }

        if ($cnpjAlterado || $enviouNovoDocumento) {
            $statusConta = self::STATUS_EM_ANALISE;
        }

        DB::beginTransaction();

        try {
            if ($enviouNovoDocumento) {
                // Operação de I/O Abstrata (S3 ou Local)
                $novoDocumentoUpload = $request->file('documento_kyc')->store('kyc/embarcadores_' . $embarcador->id);
                
                if (!$novoDocumentoUpload) {
                    throw new \RuntimeException('Falha no subsistema de disco ao gravar o KYC.');
                }
            }

            $user->update([
                'phone' => $validated['telefone'],
                'status' => $statusConta
            ]);

            $embarcador->update([
                'razao_social' => $validated['razao_social'],
                'cnpj' => $validated['cnpj'],
                'inscricao_estadual' => $validated['inscricao_estadual'] ?? null,
                'cep' => $validated['cep'] ?? null,
                'logradouro' => $validated['logradouro'] ?? null,
                'numero' => $validated['numero'] ?? null,
                'complemento' => $validated['complemento'] ?? null,
                'bairro' => $validated['bairro'] ?? null,
                'cidade' => $validated['cidade'] ?? null,
                'uf' => strtoupper($validated['uf'] ?? ''),
                'documento_kyc' => $novoDocumentoUpload ?? $documentoPathAtual,
            ]);

            DB::commit();

            // Saneamento Pós-Commit: Apaga o arquivo antigo APENAS se o banco atualizou com sucesso
            if ($enviouNovoDocumento && $documentoPathAtual && Storage::exists($documentoPathAtual)) {
                Storage::delete($documentoPathAtual);
            }

            return response()->json([
                'message' => 'Perfil atualizado com sucesso. Dados verificados.',
                'status_conta' => $statusConta,
                'documento_kyc_url' => ($novoDocumentoUpload ?? $documentoPathAtual) ? url("/api/v1/embarcador/perfil/documento") : null,
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            
            // Compensação de I/O: Previne arquivos órfãos
            if ($novoDocumentoUpload && Storage::exists($novoDocumentoUpload)) {
                Storage::delete($novoDocumentoUpload);
            }

            Log::critical('Falha atômica ao atualizar perfil de Embarcador', [
                'embarcador_id' => $embarcador->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Falha interna ao processar a atualização dos dados.'], 500);
        }
    }

    /**
     * Proxy Binário Seguro (Zero Trust File Access).
     */
    public function exibirDocumento(Request $request): StreamedResponse|JsonResponse
    {
        $user = $request->user();

        if (!$user->role || $user->role->slug !== self::ROLE_EMBARCADOR || !$user->embarcador) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $path = $user->embarcador->documento_kyc;

        if (!$path || !Storage::exists($path)) {
            return response()->json(['error' => 'Documento não localizado no cofre seguro.'], 404);
        }

        return Storage::response($path);
    }
}