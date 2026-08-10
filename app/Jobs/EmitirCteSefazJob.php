<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Carga;
use App\Events\CargaAtualizada;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmitirCteSefazJob implements ShouldQueue 
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    // SLA de Resiliência: Anti-504 Gateway Timeout
    // Tenta 5 vezes, com intervalos gradativos: 10s, 30s, 1m, 2m e 5 minutos.
    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120, 300]; 
    
    public function __construct(protected int $cargaId) {}
    
    public function handle(): void 
    {
        $carga = Carga::find($this->cargaId);
        
        // Fail-Fast: Otimização de RAM para o Worker.
        if (!$carga || $carga->status !== 'processando_sefaz') { 
            return; 
        }
        
        try {
            Log::info("[Mensageria] Iniciando handshake B2B com servidor SEFAZ para Carga ID {$this->cargaId}");

            // TODO: Integrar SoapClient ou Lib de terceiros (Ex: NFePHP)
            // Mock de I/O Delay representando assinatura A1 e latência Sefaz (3 Segundos)
            sleep(3); 
            
            DB::transaction(function () use ($carga) {
                // Lock Pessimista impede cancelamentos concorrentes pelo usuário no momento da aprovação
                $cargaLock = Carga::where('id', $this->cargaId)->lockForUpdate()->firstOrFail();
                
                if ($cargaLock->status === 'processando_sefaz') {
                    $cargaLock->update(['status' => 'publicada']); 
                }
            });

            Log::info("[Mensageria] ✅ Autorização de Uso de CT-e deferida. Carga {$this->cargaId} despachada para o mural de fretes.");
            
            // Emissão Reverb/Pusher: O Web Socket atualiza a tela B2B sem Refresh
            CargaAtualizada::dispatch($carga->fresh());

        } catch (\Exception $e) {
            Log::error("[Mensageria] ❌ Falha na comunicação SEFAZ: " . $e->getMessage());
            
            if ($this->attempts() >= $this->tries) {
                DB::transaction(function () {
                    $cargaLock = Carga::where('id', $this->cargaId)->lockForUpdate()->first();
                    if ($cargaLock && $cargaLock->status === 'processando_sefaz') {
                        $cargaLock->update(['status' => 'falha_sefaz']);
                    }
                });
                Log::critical("[Mensageria] Abortando comunicação. Carga {$this->cargaId} paralisada com status falha_sefaz.");
            }
            
            // Lança a exceção para que o Horizon/Worker reenvie a Payload para a Queue
            throw $e;
        }
    }
}