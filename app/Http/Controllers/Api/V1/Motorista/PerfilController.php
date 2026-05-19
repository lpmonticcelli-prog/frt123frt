<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;

class PerfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['role', 'motorista']);

        if (!$user->role || $user->role->slug !== 'motorista' || !$user->motorista) {
            return response()->json(['error' => 'Acesso negado ou perfil de motorista corrompido.'], 403);
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
            'doc_cnh_url' => $motorista->doc_cnh ? url("/api/v1/motorista/perfil/documento/doc_cnh") : null,
            'doc_selfie_cnh_url' => $motorista->doc_selfie_cnh ? url("/api/v1/motorista/perfil/documento/doc_selfie_cnh") : null,
            'doc_rntrc_url' => $motorista->doc_rntrc ? url("/api/v1/motorista/perfil/documento/doc_rntrc") : null,
            'doc_comprovante_endereco_url' => $motorista->doc_comprovante_endereco ? url("/api/v1/motorista/perfil/documento/doc_comprovante_endereco") : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['role', 'motorista']);

        if (!$user->role || $user->role->slug !== 'motorista' || !$user->motorista) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $motorista = $user->motorista;

        $request->validate([
            'doc_cnh' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'doc_selfie_cnh' => 'nullable|file|mimes:jpeg,png,jpg|max:10240',
            'doc_rntrc' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'doc_comprovante_endereco' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ], [
            'mimes' => 'Arquivo suspeito. Apenas imagens ou PDFs.',
            'max' => 'O arquivo excede 10MB.'
        ]);

        $updates = [];
        $pathPrefix = 'kyc/motorista_' . $motorista->id;
        $documentos = ['doc_cnh', 'doc_selfie_cnh', 'doc_rntrc', 'doc_comprovante_endereco'];

        foreach ($documentos as $doc) {
            if ($request->hasFile($doc)) {
                if ($motorista->$doc && Storage::disk('local')->exists($motorista->$doc)) {
                    Storage::disk('local')->delete($motorista->$doc);
                }
                $updates[$doc] = $request->file($doc)->store($pathPrefix, 'local');
            }
        }

        if (!empty($updates)) {
            DB::transaction(function () use ($motorista, $user, $updates) {
                $motorista->update($updates);
                if (in_array($user->status, ['pending', 'rejected'])) $user->update(['status' => 'em_analise']);
                if (in_array($motorista->status_verificacao, ['pendente', 'rejeitado', null])) $motorista->update(['status_verificacao' => 'em_analise']);
            });
        }

        return response()->json(['message' => 'Documentos armazenados com segurança.', 'status_conta' => $user->fresh()->status], 200);
    }

    public function exibirDocumento(Request $request, string $tipo): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        if (!$user->motorista) return response()->json(['error' => 'Acesso negado.'], 403);

        $validTypes = ['doc_cnh', 'doc_selfie_cnh', 'doc_rntrc', 'doc_comprovante_endereco'];
        if (!in_array($tipo, $validTypes)) return response()->json(['error' => 'Documento inválido.'], 400);

        $path = $user->motorista->$tipo;
        if (!$path || !Storage::disk('local')->exists($path)) return response()->json(['error' => 'Arquivo não localizado.'], 404);

        return Storage::disk('local')->response($path);
    }
}