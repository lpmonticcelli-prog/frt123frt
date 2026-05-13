<?php

namespace App\Jobs;

use App\Models\Carga;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Exception;

class ProcessarPublicacaoCarga implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $cargaData;
    public int $embarcadorId;
    public string $ipAddress;
    public string $userAgent;

    public function __construct(array $cargaData, int $embarcadorId, string $ipAddress, string $userAgent)
    {
        $this->cargaData = $cargaData;
        $this->embarcadorId = $embarcadorId;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                
                // Segurança: Higienização rigorosa dos dados permitidos (Anti-Mass Assignment)
                $dadosPermitidos = Arr::only($this->cargaData, [
                    'produto', 'especie', 'peso_kg', 'cubagem_m3', 
                    'tipo_veiculo', 'tipo_carroceria', 'uf_origem', 
                    'cidade_origem', 'uf_destino', 'cidade_destino', 
                    'distancia_km', 'valor_frete', 'foto_canhoto', 
                    'foto_carga', 'data_coleta', 'data_entrega_prevista'
                ]);

                $valorFrete = $dadosPermitidos['valor_frete'] ?? 0;
                $taxaPlataforma = $valorFrete * 0.05;
                
                $dadosPermitidos['embarcador_id'] = $this->embarcadorId;
                // CORREÇÃO CRÍTICA DE ESTADO: Ajustado para o status consumido pelo Mural do Motorista
                $dadosPermitidos['status'] = 'publicada';
                $dadosPermitidos['taxa_plataforma'] = $taxaPlataforma;
                
                $carga = Carga::create($dadosPermitidos);

                // Geração de Hash Jurídico 
                $dataIso = now()->toIso8601String();
                $termoPublicacao = "TERMO PUBLICAÇÃO. Carga {$carga->id}, Origem {$carga->cidade_origem}, Destino {$carga->cidade_destino}, IP {$this->ipAddress}, Data {$dataIso}";
                $termoHash = hash('sha256', $termoPublicacao);

                // Auditoria
                DB::table('carga_publicacoes_log')->insert([
                    'carga_id' => $carga->id,
                    'embarcador_id' => $this->embarcadorId,
                    'ip_address' => $this->ipAddress,
                    'user_agent' => $this->userAgent,
                    'termo_hash' => $termoHash,
                    'publicado_em' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (Exception $e) {
            Log::error('[CRÍTICO] Falha no Job de Publicação de Carga. Embarcador ID: ' . $this->embarcadorId . ' Erro: ' . $e->getMessage());
            throw $e; // Permite que o Worker do Laravel gerencie os retries adequadamente.
        }
    }
}