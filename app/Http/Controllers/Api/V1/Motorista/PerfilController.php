<?php

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PerfilController extends Controller
{
    /**
     * Retorna os dados completos do perfil do motorista logado mapeado com URLs
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // 1. Carregamento Explícito (Evita N+1 e Erro 500 por Lazy Load)
        $user->loadMissing(['role', 'motorista']);

        // 2. Defesa contra Null Pointer e Proteção RBAC
        if (!$user->role || $user->role->slug !== 'motorista' || !$user->motorista) {
            return response()->json(['error' => 'Acesso negado ou perfil de motorista corrompido.'], 403);
        }

        $motorista = $user->motorista;

        // 3. Mapeamento Estrito de Saída (Data Contract)
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
            
            // Tratamento das Imagens KYC para renderização no Frontend
            'doc_cnh_url' => $motorista->doc_cnh ? Storage::url($motorista->doc_cnh) : null,
            'doc_selfie_cnh_url' => $motorista->doc_selfie_cnh ? Storage::url($motorista->doc_selfie_cnh) : null,
            'doc_rntrc_url' => $motorista->doc_rntrc ? Storage::url($motorista->doc_rntrc) : null,
            'doc_comprovante_endereco_url' => $motorista->doc_comprovante_endereco ? Storage::url($motorista->doc_comprovante_endereco) : null,
        ]);
    }

    /**
     * Recebe e processa o upload de documentos KYC
     */
    public function uploadDocumentos(Request $request)
    {
        $user = $request->user();
        $user->loadMissing(['role', 'motorista']);

        if (!$user->role || $user->role->slug !== 'motorista' || !$user->motorista) {
            return response()->json(['error' => 'Acesso negado.'], 403);
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
            'max' => 'O arquivo excede o limite de 10MB.'
        ]);

        $updates = [];
        $pathPrefix = 'kyc/motorista_' . $motorista->id;

        $documentos = [
            'doc_cnh',
            'doc_selfie_cnh',
            'doc_rntrc',
            'doc_comprovante_endereco'
        ];

        foreach ($documentos as $doc) {
            if ($request->hasFile($doc)) {
                // Prevenção de Custo de Servidor: Apaga arquivo órfão antes de salvar o novo
                if ($motorista->$doc && Storage::disk('public')->exists($motorista->$doc)) {
                    Storage::disk('public')->delete($motorista->$doc);
                }
                $updates[$doc] = $request->file($doc)->store($pathPrefix, 'public');
            }
        }

        if (!empty($updates)) {
            DB::transaction(function () use ($motorista, $user, $updates) {
                
                $motorista->update($updates);

                // SINCRONIZAÇÃO DE STATUS (Correção de tipagem 'pending')
                if (in_array($user->status, ['pending', 'rejected'])) {
                    $user->update(['status' => 'em_analise']);
                }

                if (in_array($motorista->status_verificacao, ['pendente', 'rejeitado', null])) {
                    $motorista->update(['status_verificacao' => 'em_analise']);
                }
            });
        }

        return response()->json([
            'message' => 'Documentos enviados com sucesso. Nossa equipe de compliance irá analisar seu perfil em breve.',
            'status_conta' => $user->fresh()->status
        ], 200);
    }
}