<?php

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PerfilController extends Controller
{
    /**
     * Retorna os dados completos do perfil do motorista logado
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Trava de segurança: Apenas motoristas acessam
        if ($user->role->slug !== 'motorista') {
            abort(403, 'Acesso negado. Apenas motoristas podem acessar este recurso.');
        }

        $motorista = $user->motorista()->with('user:id,name,email,phone')->first();

        return response()->json($motorista);
    }

    /**
     * Recebe e processa o upload de documentos KYC
     */
    public function uploadDocumentos(Request $request)
    {
        $user = $request->user();

        if ($user->role->slug !== 'motorista') {
            abort(403, 'Acesso negado. Apenas motoristas podem enviar documentos aqui.');
        }

        $motorista = $user->motorista;

        // VALIDAÇÃO MILITAR CONTRA SHELL INJECTION E TAMANHO DE PAYLOAD
        $request->validate([
            'doc_cnh' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'doc_selfie_cnh' => 'nullable|file|mimes:jpeg,png,jpg|max:10240',
            'doc_rntrc' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'doc_comprovante_endereco' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ], [
            'mimes' => 'Arquivo suspeito. Apenas imagens (JPG/PNG) ou PDFs são permitidos.',
            'max' => 'O arquivo é muito grande. O tamanho máximo permitido é de 10MB.'
        ]);

        $updates = [];
        $pathPrefix = 'kyc/motorista_' . $motorista->id;

        $documentos = [
            'doc_cnh',
            'doc_selfie_cnh',
            'doc_rntrc',
            'doc_comprovante_endereco'
        ];

        // Processamento de Arquivos
        foreach ($documentos as $doc) {
            if ($request->hasFile($doc)) {
                // Prevenção de Custo de Servidor: Apaga o arquivo antigo antes de salvar o novo
                if ($motorista->$doc && Storage::disk('public')->exists($motorista->$doc)) {
                    Storage::disk('public')->delete($motorista->$doc);
                }
                
                $updates[$doc] = $request->file($doc)->store($pathPrefix, 'public');
            }
        }

        // Se algum arquivo foi enviado, atualiza o banco atômicamente
        if (!empty($updates)) {
            DB::transaction(function () use ($motorista, $user, $updates) {
                
                // Atualiza os caminhos dos arquivos na tabela motoristas
                $motorista->update($updates);

                // SINCRONIZAÇÃO DE STATUS MESTRE (Para aparecer no painel Admin)
                if (in_array($user->status, ['pendente', 'rejected'])) {
                    $user->update(['status' => 'em_analise']);
                }

                // Sincronização do status secundário do motorista
                if (in_array($motorista->status_verificacao, ['pendente', 'rejeitado', null])) {
                    $motorista->update(['status_verificacao' => 'em_analise']);
                }
            });
        }

        return response()->json([
            'message' => 'Documentos enviados com sucesso. Nossa equipe de compliance irá analisar seu perfil em breve.',
            'motorista' => $motorista->fresh()->load('user') 
        ]);
    }
}