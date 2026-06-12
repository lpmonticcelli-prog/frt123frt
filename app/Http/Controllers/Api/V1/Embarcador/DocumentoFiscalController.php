<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Embarcador;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Logistics\XmlParserService;
use Illuminate\Support\Facades\Log;
use Exception;

class DocumentoFiscalController extends Controller
{
    public function __construct(private readonly XmlParserService $xmlParser) {}

    public function parse(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role || $user->role->slug !== 'embarcador' || !$user->embarcador) {
            return response()->json(['error' => 'Acesso negado. Requer perfil lógico de Embarcador.'], 403);
        }

        $request->validate([
            'xml_file' => 'required|file|mimes:xml|max:2048' // Limite Arquitetural: 2MB (Evita Exaustão de Buffer)
        ], [
            'xml_file.mimes' => 'Extensão suspeita. Apenas arquivos estritos .xml são aceitos pela plataforma.',
            'xml_file.max' => 'A malha de segurança rejeitou o payload: Tamanho do arquivo excede o teto de 2MB.'
        ]);

        try {
            // A blindagem Anti-XXE é realizada intrinsecamente na Service.
            $dados = $this->xmlParser->parse($request->file('xml_file'));

            Log::info("SecOps: Parsing XML (NF-e) executado. Embarcador ID: {$user->embarcador->id}");

            return response()->json([
                'message' => 'Documento processado com sucesso. Dados inseridos na folha.',
                'data' => $dados
            ], 200);

        } catch (Exception $e) {
            Log::alert("WAF Intercept: Falha crítica de Parsing XML (Possível Indução XXE/Billion Laughs). Motivo: " . $e->getMessage(), ['ip' => $request->ip()]);
            return response()->json(['error' => 'Falha na extração semântica: ' . $e->getMessage()], 422);
        }
    }
}