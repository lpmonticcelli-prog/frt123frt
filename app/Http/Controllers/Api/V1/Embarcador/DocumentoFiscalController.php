<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Embarcador\ProcessarDocumentoS3Request;
use App\Services\Logistics\XmlParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

class DocumentoFiscalController extends Controller
{
    public function __construct(
        private readonly XmlParserService $xmlParser
    ) {}

    /**
     * Extrai dados semânticos lendo via Stream diretamente do Object Storage.
     */
    public function parse(ProcessarDocumentoS3Request $request): JsonResponse
    {
        $embarcadorId = $request->user()->embarcador->id ?? null;
        $s3Path = $request->validated('s3_path');

        try {
            // Verificação de integridade no S3
            if (!Storage::disk('s3')->exists($s3Path)) {
                Log::warning('DFe Reader: Arquivo não localizado no Object Storage.', [
                    'embarcador_id' => $embarcadorId,
                    's3_path'       => $s3Path,
                ]);

                return response()->json([
                    'error' => 'Documento fiscal não encontrado no repositório remoto.'
                ], 404);
            }

            // Lê o conteúdo do S3 diretamente para a memória da thread (sem I/O de disco local)
            $xmlContent = Storage::disk('s3')->get($s3Path);

            if (empty($xmlContent)) {
                throw new InvalidArgumentException('O documento fiscal está vazio ou corrompido no repositório.');
            }

            // O Parser injetado blinda contra XXE e realiza a extração dos nós
            $dados = $this->xmlParser->parse($xmlContent);

            Log::info('SecOps: Parsing XML (DFe) a partir do S3 concluído com sucesso.', [
                'embarcador_id' => $embarcadorId,
                's3_path'       => $s3Path,
            ]);

            return response()->json([
                'message' => 'Documento processado e extraído com sucesso.',
                'data'    => $dados
            ], 200);

        } catch (InvalidArgumentException $e) {
            Log::warning('WAF Intercept: Falha semântica no Parsing XML.', [
                'embarcador_id' => $embarcadorId,
                's3_path'       => $s3Path,
                'ip'            => $request->ip(),
                'reason'        => $e->getMessage()
            ]);

            return response()->json([
                'error'   => 'Falha na validação semântica do documento.',
                'details' => $e->getMessage()
            ], 422);

        } catch (Throwable $e) {
            Log::critical('System Halt: Falha crítica de I/O ou Parsing ao processar DFe do S3.', [
                'embarcador_id' => $embarcadorId,
                's3_path'       => $s3Path,
                'ip'            => $request->ip(),
                'exception'     => $e->getMessage(),
                'trace'         => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Erro interno ao processar a leitura do documento no cofre cloud.'
            ], 500);
        }
    }
}