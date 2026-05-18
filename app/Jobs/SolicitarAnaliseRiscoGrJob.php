<?php

namespace App\Jobs;

use App\Models\Carga;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SolicitarAnaliseRiscoGrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cargaId;

    /**
     * O número de vezes que o job pode tentar executar caso a API da e3 caia.
     */
    public $tries = 3;

    public function __construct(int $cargaId)
    {
        $this->cargaId = $cargaId;
    }

    public function handle()
    {
        // 1. Puxa os dados vitais com os relacionamentos blindados
        $carga = Carga::with(['embarcador', 'motorista.user'])->find($this->cargaId);

        if (!$carga || $carga->status !== 'em_analise_gr') {
            Log::warning("Job GR: Carga {$this->cargaId} não encontrada ou status inválido. Abortando envio.");
            return;
        }

        // 2. Monta o Payload LGPD-Compliant Universal (Sem e-mails, sem telefones)
        $payload = [
            "protocolo_interno" => $carga->id,
            "embarcador" => [
                "cnpj" => $carga->embarcador->cnpj ?? 'N/A'
            ],
            "motorista" => [
                "cpf" => $carga->motorista->cpf ?? 'N/A',
                "cnh" => $carga->motorista->cnh ?? 'N/A'
            ],
            "veiculo" => [
                "placa_cavalo" => $carga->motorista->placa_veiculo ?? 'N/A',
                "placa_carreta" => $carga->motorista->placa_carreta ?? 'N/A'
            ],
            "viagem" => [
                "origem_uf" => $carga->uf_origem,
                "destino_uf" => $carga->uf_destino,
                "valor_mercadoria" => $carga->valor_mercadoria ?? 0.00 // Adicione este campo se existir na base, ou use valor_frete provisoriamente
            ]
        ];

        // 3. Dispara para a API Externa da Seguradora (e3 ou outra)
        // O ideal é colocar a URL da e3 e o Token deles no seu .env
        $apiUrl = env('GR_PARTNER_API_URL', 'https://api.sandbox.e3seguros.com.br/v1/analise-risco');
        $apiToken = env('GR_PARTNER_API_TOKEN', 'token-de-teste-e3');

        Log::info("Job GR: Disparando análise para Carga {$carga->id}...", $payload);

        try {
            $response = Http::withToken($apiToken)
                ->timeout(10) // Timeout curto para não travar a fila
                ->post($apiUrl, $payload);

            if ($response->successful()) {
                Log::info("Job GR: Payload entregue com sucesso na seguradora. Protocolo: {$carga->id}");
            } else {
                Log::error("Job GR: Falha na seguradora. Status HTTP: " . $response->status(), $response->json());
                // Força o job a falhar para o Laravel tentar novamente (tries = 3)
                $this->fail(new \Exception("A API da GR retornou erro " . $response->status()));
            }

        } catch (\Exception $e) {
            Log::error("Job GR: Erro de conexão com a seguradora: " . $e->getMessage());
            $this->fail($e);
        }
    }
}