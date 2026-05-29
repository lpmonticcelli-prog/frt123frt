<?php

declare(strict_types=1);

namespace App\Services\Partners;

use App\Models\Motorista;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class TransSatService
{
    private string $authUrl;
    private string $apiUrl;
    
    // Variáveis que devem estar configuradas no .env
    private string $username;
    private string $password;
    private int $clienteId;
    private int $empresaId;

    public function __construct()
    {
        // 1. ZT-DEFENSE: Fallback e Type Casting de Segurança
        // Garantimos que o valor retornado é convertido para string, evitando TypeErrors
        $authUrl = config('services.transat.auth_url');
        $this->authUrl = is_string($authUrl) && !empty($authUrl) 
            ? $authUrl 
            : 'https://auth.gr.app.br/realms/one/protocol/openid-connect/token';
        
        $baseUrl = config('services.transat.base_url');
        $baseUrlStr = is_string($baseUrl) && !empty($baseUrl) 
            ? $baseUrl 
            : 'https://api.gr.app.br';

        $this->apiUrl = rtrim($baseUrlStr, '/') . '/CadastroService/ProcessarCadastroConsulta';

        // 2. Credenciais com Type Casting seguro
        $username = config('services.transat.username');
        $this->username = is_string($username) ? $username : '';

        $password = config('services.transat.password');
        $this->password = is_string($password) ? $password : '';

        $this->clienteId = (int) config('services.transat.cliente_id', 0);
        $this->empresaId = (int) config('services.transat.empresa_id', 0);

        if (empty($this->username) || empty($this->password) || $this->clienteId === 0 || $this->empresaId === 0) {
            throw new Exception("As credenciais da Trans Sat não estão configuradas corretamente no ficheiro .env.");
        }
    }

    /**
     * Obtém o token Bearer do Keycloak da Trans Sat.
     */
    private function obterToken(): string
    {
        return Cache::remember('transat_auth_token', now()->addHours(70), function () {
            $response = Http::asForm()->post($this->authUrl, [
                'client_id' => 'grapp',
                'grant_type' => 'password',
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->failed()) {
                Log::error("[TransSat] Falha ao obter token Keycloak.", ['response' => $response->json()]);
                throw new Exception("Não foi possível autenticar com a Gerenciadora de Risco.");
            }

            return (string) $response->json('access_token');
        });
    }

    /**
     * Envia o Motorista para análise na Gerenciadora de Risco.
     */
    public function enviarParaAnalise(Motorista $motorista): string
    {
        $token = $this->obterToken();

        $veiculosFormatados = [];

        $webhookSecret = config('services.transat.webhook_secret');
        $webhookSecretStr = is_string($webhookSecret) && !empty($webhookSecret) ? $webhookSecret : 'fallback-secret';

        $payload = [
            'dados' => [
                'motorista' => [
                    'documento' => preg_replace('/[^0-9]/', '', $motorista->cpf ?? '11111111111'),
                    'nome' => $motorista->user->name ?? 'Motorista Desconhecido',
                    'data_nasc' => $motorista->data_nascimento ? $motorista->data_nascimento->format('Y-m-d') : null, 
                    'cnh' => preg_replace('/[^0-9]/', '', $motorista->cnh ?? ''),
                    'cnh_cat' => $motorista->categoria_cnh ?? 'E', 
                    'cnh_uf' => $motorista->uf_cnh ?? 'SP', 
                    'telefone' => preg_replace('/[^0-9]/', '', $motorista->user->phone ?? ''),
                ]
            ],
            'parametros' => [
                'tipo' => 8, 
                'check_rdo' => 1, 
                'biometria_facial' => true, 
                'hook_link' => route('webhook.transat'), 
                'hook_method' => 'POST',
                'hook_headers' => [
                    'Authorization' => 'Bearer ' . $webhookSecretStr
                ],
                'enviar_link_biometria' => false 
            ]
        ];

        if (!empty($veiculosFormatados)) {
            $payload['dados']['veiculos'] = $veiculosFormatados;
            $payload['parametros']['tipo'] = 8;
            $payload['parametros']['check_proprietario'] = true;
            $payload['parametros']['check_rdo'] = 3;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'cliente_id' => $this->clienteId,
            'empresa_id' => $this->empresaId,
        ])->post($this->apiUrl, $payload);

        if ($response->failed() || !($response->json('success') || $response->json('sucesso'))) {
            Log::error("[TransSat] Falha ao enviar motorista ID {$motorista->id} para análise.", [
                'status' => $response->status(),
                'response' => $response->json(),
                'payload' => $payload
            ]);
            
            $errorMessage = $response->json('message') ?? $response->json('mensagem') ?? 'Falha na comunicação com a Gerenciadora de Risco.';
            throw new Exception("Erro GR: " . $errorMessage);
        }

        return (string) ($response->json('referencia') ?? 'REF-MOCK-FALLBACK');
    }
}