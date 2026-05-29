<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * Retorna o Contrato de Dados (Payload) absoluto para o Vue 3.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['role', 'motorista']);

        if (!$user->role || $user->role->slug !== self::ROLE_MOTORISTA || !$user->motorista) {
            Log::warning('Tentativa de acesso a perfil de motorista bloqueada', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);
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
            
            // Integração Gerenciadora de Risco (Trans Sat)
            'gr_status' => $motorista->gr_status ?? self::GR_NAO_SOLICITADO,
            'gr_referencia' => $motorista->gr_referencia,
            'gr_biometria_url' => $motorista->gr_biometria_url,
            
            // URLs de Proxy Seguras
            'doc_cnh_url' => $motorista->doc_cnh ? url("/api/v1/motorista/perfil/documento/doc_cnh") : null,
            'doc_selfie_cnh_url' => $motorista->doc_selfie_cnh ? url("/api/v1/motorista/perfil/documento/doc_selfie_cnh") : null,
            'doc_rntrc_url' => $motorista->doc_rntrc ? url("/api/v1/motorista/perfil/documento/doc_rntrc") : null,
            'doc_comprovante_endereco_url' => $motorista->doc_comprovante_endereco ? url("/api/v1/motorista/perfil/documento/doc_comprovante_endereco") : null,
        ], 200);
    }

    /**
     * Processamento ACID de Documentos (KYC).
     */
    public function update(Request $request): JsonResponse
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
        ], [
            'mimes' => 'Extensão de arquivo não permitida. Risco de segurança.',
            'max' => 'Payload recusa arquivos superiores a 10MB para prevenir exaustão de I/O.'
        ]);

        $updates = [];
        $filesUploaded = []; // Trackear para rollback de I/O em caso de falha de DB
        $pathPrefix = 'kyc/motorista_' . $motorista->id;

        DB::beginTransaction();

        try {
            foreach (self::DOCUMENTOS_PERMITIDOS as $doc) {
                if ($request->hasFile($doc)) {
                    // Deleção na Storage Abstrata (S3 default)
                    if ($motorista->$doc && Storage::exists($motorista->$doc)) {
                        Storage::delete($motorista->$doc);
                    }
                    
                    $path = $request->file($doc)->store($pathPrefix);
                    if (!$path) {
                        throw new \RuntimeException("Falha de I/O ao gravar o documento {$doc}.");
                    }
                    
                    $updates[$doc] = $path;
                    $filesUploaded[] = $path;
                }
            }

            if (!empty($updates)) {
                $motorista->update($updates);

                // Máquina de Estado: Atualização defensiva de status
                if (in_array($user->status, ['pending', 'rejected'], true)) {
                    $user->update(['status' => self::STATUS_EM_ANALISE]);
                }
                
                if (in_array($motorista->status_verificacao, [self::STATUS_PENDENTE, self::STATUS_REJEITADO, null], true)) {
                    $motorista->update(['status_verificacao' => self::STATUS_EM_ANALISE]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Pipeline de KYC atualizada com sucesso.',
                'status_conta' => $user->fresh()->status
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            
            // Compensação de I/O: Excluir arquivos orfãos se o DB falhar
            foreach ($filesUploaded as $file) {
                if (Storage::exists($file)) {
                    Storage::delete($file);
                }
            }

            Log::critical('Falha catastrófica no pipeline KYC', [
                'motorista_id' => $motorista->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Falha interna no processamento de transações ou I/O.'], 500);
        }
    }

    /**
     * Proxy de Objeto Binário Seguro (Zero Trust File Access).
     */
    public function exibirDocumento(Request $request, string $tipo): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        if (!$user->motorista) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        if (!in_array($tipo, self::DOCUMENTOS_PERMITIDOS, true)) {
            Log::warning('Tentativa de LFI detectada no Proxy de Documentos', [
                'user_id' => $user->id,
                'payload' => $tipo
            ]);
            return response()->json(['error' => 'Vetor de documento inválido.'], 400);
        }

        $path = $user->motorista->$tipo;
        
        // Storage::exists opera no driver default (S3/EFS), abstraindo a infra
        if (!$path || !Storage::exists($path)) {
            return response()->json(['error' => 'Objeto binário não localizado na storage.'], 404);
        }

        return Storage::response($path);
    }
}