<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Mock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TransatMockController extends Controller
{
    /**
     * 1. SIMULAÇÃO DO KEYCLOAK (Autenticação)
     */
    public function token(Request $request): JsonResponse
    {
        return response()->json([
            'access_token' => 'mock_token_super_secreto_' . time(),
            'expires_in' => 255600,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * 2. SIMULAÇÃO DA RECEÇÃO DA CONSULTA (A GR recebe e manda aguardar)
     */
    public function processarConsulta(Request $request): JsonResponse
    {
        $dados = $request->input('dados.motorista');
        if (empty($dados['documento']) || empty($dados['nome'])) {
            return response()->json([
                'success' => false,
                'message' => 'MOCK ERROR: CPF ou Nome não enviados no payload.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'referencia' => (string) Str::uuid(), 
            'url' => 'mock_hash_biometria_' . rand(1000, 9999), 
            'message' => 'MOCK: Consulta recebida. Aguarde o processamento via Webhook.'
        ]);
    }

    /**
     * 3. GATILHOS DO SIMULADOR (Para você testar a máquina de estados via Navegador)
     */
    public function dispararWebhook(Request $request, string $referencia, int $codigo)
    {
        // Pega a senha do webhook real
        $webhookSecret = config('services.transat.webhook_secret');
        $webhookUrl = route('webhook.transat');

        $payload = [
            'referencia' => $referencia,
            'codigo' => $codigo,
            'lines' => [
                [
                    'codigo' => $codigo,
                    'mensagem' => $this->getMensagemMock($codigo)
                ]
            ]
        ];

        // Dispara o POST contra o seu próprio webhook real
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $webhookSecret,
            'Content-Type' => 'application/json'
        ])->post($webhookUrl, $payload);

        return response()->json([
            'mock_status' => 'Webhook disparado com sucesso para o seu sistema!',
            'cenario_simulado' => $this->getMensagemMock($codigo),
            'http_status_recebido_do_seu_sistema' => $response->status(),
            'resposta_do_seu_sistema' => $response->json()
        ]);
    }

    private function getMensagemMock(int $codigo): string
    {
        return match ($codigo) {
            1 => 'SIMULAÇÃO: Perfil Limpo - Acordo Aprovado',
            2 => 'SIMULAÇÃO: Divergência Criminal - Desacordo',
            5 => 'SIMULAÇÃO: CNH Inválida - Erro de Parâmetro',
            7 => 'SIMULAÇÃO: Aguardando Biometria Facial',
            default => 'SIMULAÇÃO: Código Desconhecido'
        };
    }
}