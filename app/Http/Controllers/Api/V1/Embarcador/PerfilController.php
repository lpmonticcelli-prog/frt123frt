<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ReceitaWSService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PerfilController extends Controller
{
    /**
     * Retorna os dados atuais do perfil do Embarcador logado
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Defesa Universal IDOR: Apenas Embarcadores acessam
        if ($user->role->slug !== 'embarcador' || !$user->embarcador) {
            abort(403, 'Acesso negado. Perfil de embarcador não encontrado ou tipo de conta inválido.');
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
            'status_conta' => $user->status, // pending, em_analise, active, rejected
            
            // ZT-DEFENSE: Substituição da URL Pública por Proxy Autenticado
            'documento_kyc_url' => $embarcador->documento_kyc ? url("/api/v1/embarcador/perfil/documento") : null,
        ]);
    }

    /**
     * Atualiza os dados, valida o CNPJ na Receita Federal e faz o upload do documento KYC
     */
    public function update(Request $request, ReceitaWSService $receitaWSService): JsonResponse
    {
        $user = $request->user();
        
        if ($user->role->slug !== 'embarcador') {
            abort(403, 'Acesso negado.');
        }

        $embarcador = $user->embarcador;

        // VALIDAÇÃO CONTRA SHELL INJECTION E TAMANHO DE PAYLOAD
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
            'documento_kyc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Máx 5MB
        ], [
            'cnpj.unique' => 'Este CNPJ já está cadastrado em outra conta.',
            'documento_kyc.mimes' => 'Arquivo não permitido. O documento deve ser um PDF, JPG ou PNG.',
            'documento_kyc.max' => 'O arquivo é muito grande. Não pode ter mais de 5MB.'
        ]);

        $statusConta = $user->status;
        $documentoPath = $embarcador->documento_kyc;

        // Regra de Negócio: Se enviar novo documento ou trocar CNPJ, volta para análise
        $cnpjAlterado = $embarcador->cnpj !== $validated['cnpj'];
        $novoDocumento = $request->hasFile('documento_kyc');

        // =================================================================
        // AUDITORIA TÉCNICA: Validação na Receita Federal em tempo real
        // Executamos a chamada à API APENAS se o CNPJ for alterado ou for o primeiro cadastro, 
        // poupando limite de requisições da ReceitaWS.
        // =================================================================
        if ($cnpjAlterado || $statusConta === 'pending') {
            $analiseCNPJ = $receitaWSService->validarCNPJ($validated['cnpj']);
            
            if (!$analiseCNPJ['valido']) {
                // Retorna 422 ao Vue.js bloqueando a ação. O utilizador verá o motivo (ex: Inapta, Baixada)
                return response()->json(['message' => $analiseCNPJ['mensagem']], 422);
            }
            
            // Opcional de integridade: Descomente a linha abaixo para forçar a Razão Social a ser idêntica à da Receita Federal.
            // $validated['razao_social'] = $analiseCNPJ['razao_social'];
        }

        if ($novoDocumento) {
            // ZT-DEFENSE: Apaga o arquivo antigo do cofre seguro (local) para economizar espaço
            if ($documentoPath && Storage::disk('local')->exists($documentoPath)) {
                Storage::disk('local')->delete($documentoPath);
            }
            
            // ZT-DEFENSE: Salva o novo arquivo isolado na pasta do embarcador no disco local (NÃO PÚBLICO)
            $documentoPath = $request->file('documento_kyc')->store('kyc/embarcadores/' . $embarcador->id, 'local');
        }

        if ($cnpjAlterado || $novoDocumento) {
            // Padronizado 'em_analise' para sincronizar com o Controller do Administrador
            $statusConta = 'em_analise';
        }

        // Atualização Atômica
        DB::transaction(function () use ($user, $embarcador, $validated, $statusConta, $documentoPath) {
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
                'documento_kyc' => $documentoPath,
            ]);
        });

        return response()->json([
            'message' => 'Perfil atualizado com sucesso. Dados verificados na Receita Federal.',
            'status_conta' => $statusConta,
            // Retorna o Proxy Seguro
            'documento_kyc_url' => $documentoPath ? url("/api/v1/embarcador/perfil/documento") : null,
        ]);
    }

    /**
     * ZT-DEFENSE: Servidor de Arquivo Seguro (Proxy Local)
     * Entrega o documento KYC diretamente da memória sem expor a URI física.
     */
    public function exibirDocumento(Request $request): StreamedResponse|JsonResponse
    {
        $user = $request->user();

        if ($user->role->slug !== 'embarcador' || !$user->embarcador) {
            abort(403, 'Acesso negado.');
        }

        $path = $user->embarcador->documento_kyc;

        if (!$path || !Storage::disk('local')->exists($path)) {
            return response()->json(['error' => 'Documento não localizado no cofre seguro.'], 404);
        }

        return Storage::disk('local')->response($path);
    }
}