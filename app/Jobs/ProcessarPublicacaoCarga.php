<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Carga;
use App\Jobs\SolicitarAnaliseRiscoGrJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
                    'foto_carga', 'data_coleta', 'data_entrega_prevista',
                    'valor_mercadoria'
                ]);

                $valorFrete = (float) ($dadosPermitidos['valor_frete'] ?? 0);
                $taxaPlataforma = $valorFrete * 0.05;
                
                $dadosPermitidos['embarcador_id'] = $this->embarcadorId;
                $dadosPermitidos['taxa_plataforma'] = $taxaPlataforma;
                
                // AVALIAÇÃO DE FEATURE FLAG: Se GR estiver ativo, a carga nasce em análise.
                // Se estiver inativo, nasce diretamente "publicada" (disponível no mural).
                $isGrEnabled = Cache::get('feature_flag:gr_enabled', config('services.gr.enabled', true));
                $dadosPermitidos['status'] = $isGrEnabled ? 'em_analise_gr' : 'publicada';
                
                $carga = Carga::create($dadosPermitidos);

                // Geração de Hash Jurídico para não-repúdio (ICP-Brasil Compliance)
                $dataIso = now()->toIso8601String();
                $termoPublicacao = "TERMO PUBLICAÇÃO. Carga {$carga->id}, Origem {$carga->cidade_origem}, Destino {$carga->cidade_destino}, IP {$this->ipAddress}, Data {$dataIso}";
                $termoHash = hash('sha256', $termoPublicacao);

                // Auditoria de trilha imutável
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

                // Se o módulo de gerenciamento de risco estiver ativo, despacha o job de análise de risco imediatamente
                if ($isGrEnabled) {
                    Log::info("Módulo GR Ativo. Enfileirando análise para Carga ID: {$carga->id}");
                    SolicitarAnaliseRiscoGrJob::dispatch($carga->id)->onQueue('default');
                } else {
                    Log::info("Módulo GR Inativo (Bypass). Carga ID: {$carga->id} disponibilizada diretamente.");
                }
            });
        } catch (Exception $e) {
            Log::error('[CRÍTICO] Falha no Job de Publicação de Carga. Embarcador ID: ' . $this->embarcadorId . ' Erro: ' . $e->getMessage());
            throw $e; 
        }
    }
}