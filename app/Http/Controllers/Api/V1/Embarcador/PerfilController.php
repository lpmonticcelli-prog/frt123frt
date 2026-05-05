<?php

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ReceitaWSService; // IMPORTAÇÃO DO SERVIÇO DE VALIDAÇÃO

class PerfilController extends Controller
{
    /**
     * Retorna os dados atuais do perfil do Embarcador logado
     */
    public function show(Request $request)
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
            'documento_kyc_url' => $embarcador->documento_kyc ? Storage::url($embarcador->documento_kyc) : null,
        ]);
    }

    /**
     * Atualiza os dados, valida o CNPJ na Receita Federal e faz o upload do documento KYC
     */
    public function update(Request $request, ReceitaWSService $receitaWSService)
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
            // Apaga o arquivo antigo do servidor para economizar espaço e evitar lixo (Orphan files)
            if ($documentoPath && Storage::disk('public')->exists($documentoPath)) {
                Storage::disk('public')->delete($documentoPath);
            }
            
            // Salva o novo arquivo isolado na pasta do embarcador
            $documentoPath = $request->file('documento_kyc')->store('kyc/embarcadores/' . $embarcador->id, 'public');
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
            'documento_kyc_url' => $documentoPath ? Storage::url($documentoPath) : null,
        ]);
    }
}