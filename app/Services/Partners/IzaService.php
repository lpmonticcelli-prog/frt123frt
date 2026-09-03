<?php

namespace App\Services\Partners;

use App\Models\Motorista;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class IzaService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.iza.base_url');
        $this->clientId = config('services.iza.client_id');
        $this->clientSecret = config('services.iza.client_secret');
    }

    /**
     * Autentica na API da Iza e retorna o Bearer Token.
     * Utiliza cache para evitar requisições desnecessárias.
     */
    protected function getToken(): string
    {
        return Cache::remember('iza_access_token', 3000, function () {
            $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->failed()) {
                Log::error('[IZA] Falha na autenticação', ['response' => $response->json()]);
                throw new Exception('Não foi possível autenticar com a API da IZA Seguradora.');
            }

            return $response->json('access_token');
        });
    }

    /**
     * Instância base do Client HTTP já com os headers de autenticação.
     */
    protected function client()
    {
        return Http::withToken($this->getToken())
            ->acceptJson()
            ->timeout(15);
    }

    /**
     * Solicita a emissão da apólice para o motorista.
     * Mapeia o plano escolhido para as coberturas da cotação.
     */
    public function emitirApolice(Motorista $motorista, string $plano): array
    {
        // Mapeamento interno dos planos baseados na cotação comercial
        $planosValidos = ['basico', 'intermediario', 'superior'];
        
        if (!in_array($plano, $planosValidos)) {
            throw new Exception("Plano IZA inválido. Escolha entre: " . implode(', ', $planosValidos));
        }

        // Construção do payload. A estrutura exata dependerá da documentação final da API da IZA,
        // mas este é o padrão de mercado para apólices por vida.
        $payload = [
            'external_id' => (string) $motorista->id,
            'documento' => $motorista->cpf, // O Laravel descriptografa automaticamente via Model Cast
            'nome' => $motorista->user->name ?? 'Motorista 123Fretei',
            'data_nascimento' => $motorista->user->data_nascimento ?? '1990-01-01', // Ajuste conforme seu schema
            'plano_contratado' => $plano,
            'inicio_vigencia' => now()->toIso8601String(),
        ];

        Log::info('[IZA] Solicitando emissão de apólice', ['motorista_id' => $motorista->id, 'plano' => $plano]);

        $response = $this->client()->post("{$this->baseUrl}/v1/apolices/emitir", $payload);

        if ($response->failed()) {
            Log::error('[IZA] Falha ao emitir apólice', [
                'motorista_id' => $motorista->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new Exception('Erro ao emitir apólice na IZA: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Consulta o status atual de uma apólice (útil para fallback caso o webhook falhe).
     */
    public function consultarStatus(string $policyId): array
    {
        $response = $this->client()->get("{$this->baseUrl}/v1/apolices/{$policyId}");

        if ($response->failed()) {
            Log::error('[IZA] Falha ao consultar apólice', ['policy_id' => $policyId]);
            throw new Exception('Erro ao consultar apólice na IZA.');
        }

        return $response->json();
    }

    /**
     * Solicita o cancelamento da apólice (ex: inadimplência ou encerramento de conta).
     */
    public function cancelarApolice(string $policyId): bool
    {
        $response = $this->client()->post("{$this->baseUrl}/v1/apolices/{$policyId}/cancelar");

        if ($response->failed()) {
            Log::error('[IZA] Falha ao cancelar apólice', ['policy_id' => $policyId]);
            throw new Exception('Erro ao cancelar apólice na IZA.');
        }

        return true;
    }
}