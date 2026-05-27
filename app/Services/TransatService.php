<?php

namespace App\Services;

use App\Contracts\RiskManagementInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class TransatService implements RiskManagementInterface
{
    private string $baseUrl = 'https://api.gr.app.br';

    private function getAuthToken(): string
    {
        // Documentação diz: Token dura 72h. Fazemos cache em memória por 71h (255.600 seg) para zero-latency.
        return Cache::remember('transat_access_token', 255600, function () {
            $response = Http::asForm()->post('https://auth.gr.app.br/realms/one/protocol/openid-connect/token', [
                'client_id'  => 'grapp',
                'grant_type' => 'password',
                'username'   => config('services.transat.username'),
                'password'   => config('services.transat.password'),
            ]);

            if ($response->failed()) {
                Log::critical('[TRANSAT AUTH] Falha Crítica de Conexão', ['res' => $response->body()]);
                throw new Exception('Falha ao autenticar na Transat. Verifique suas credenciais.');
            }

            return $response->json('access_token');
        });
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getAuthToken(),
            'cliente_id'    => config('services.transat.cliente_id'),
            'empresa_id'    => config('services.transat.empresa_id'),
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
    }

    public function extrairDadosOcr(string $tipoDocumento, string $conteudoArquivo, string $mimeType): array
    {
        $endpoint = strtolower($tipoDocumento) === 'crlv' ? '/api/ocr/crlv' : '/api/ocr/cnh';
        
        $response = Http::withHeaders($this->getHeaders())
            ->timeout(20) // OCR de Inteligência Artificial demanda limite maior
            ->post($this->baseUrl . $endpoint, [
                'mime_type' => $mimeType,
                'file'      => base64_encode($conteudoArquivo)
            ]);

        if ($response->failed()) {
            Log::error("[TRANSAT OCR] Erro em {$tipoDocumento}", ['res' => $response->body()]);
            throw new Exception("Falha da Transat na leitura OCR da {$tipoDocumento}.");
        }

        return $response->json();
    }

    public function consultarRisco(array $dadosMotorista, array $dadosVeiculos = []): array
    {
        $payload = [
            'dados' => [
                'motorista' => $dadosMotorista
            ],
            'parametros' => [
                'tipo' => 8, // 8 = Autônomo. 
                'check_rdo' => 3, // 3 = Motorista + Veículo
                'trava_tempo' => false,
                'biometria_facial' => true,
                'check_proprietario' => true,
                'enviar_link_biometria' => false, // Setado False para não enviar SMS. Faremos no nosso próprio FrontEnd.
                
                // INJEÇÃO BLINDADA DO WEBHOOK PARA RECEBER A RESPOSTA
                'hook_link' => route('api.webhooks.transat'),
                'hook_method' => 'POST',
                'hook_headers' => [
                    'Authorization' => 'Bearer ' . config('services.transat.webhook_secret')
                ]
            ]
        ];

        if (!empty($dadosVeiculos)) {
            $payload['dados']['veiculo'] = $dadosVeiculos;
        }

        $response = Http::withHeaders($this->getHeaders())
            ->timeout(45) // Buscas governamentais demoram
            ->post($this->baseUrl . '/CadastroService/ProcessarCadastroConsulta', $payload);

        if ($response->failed() || !$response->json('success')) {
            Log::error('[TRANSAT CONSULTA] Rejeitado pela GR', ['payload' => $payload, 'res' => $response->json()]);
            throw new Exception('A API da Transat rejeitou a pesquisa.');
        }

        return [
            'referencia' => $response->json('referencia'),
            'hash_biometria' => $response->json('url') // Extraído conforme a sua documentação
        ];
    }
}