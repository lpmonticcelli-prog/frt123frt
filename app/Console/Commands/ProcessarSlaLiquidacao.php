<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carga;
use App\Models\Ciot;
use App\Contracts\PefGatewayInterface;
use App\Jobs\LiquidarFreteJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessarSlaLiquidacao extends Command
{
    protected $signature = 'fretei:liquidar-sla';

    protected $description = 'Audita e liquida automaticamente fretes que estouraram o SLA do Embarcador';

    public function handle(PefGatewayInterface $pefGateway): int
    {
        $this->info('[123FRETEI WORKER] Iniciando auditoria de SLA (24h) de Liquidação...');

        $limiteSla = now()->subHours(24);

        $cargasVencidas = Carga::where('status', 'em_auditoria')
            ->whereNotNull('em_auditoria_desde')
            ->where('em_auditoria_desde', '<=', $limiteSla)
            ->get();

        if ($cargasVencidas->isEmpty()) {
            $this->info('Nenhuma carga com SLA estourado neste ciclo de auditoria.');
            return Command::SUCCESS;
        }

        foreach ($cargasVencidas as $carga) {
            try {
                DB::transaction(function () use ($carga) {
                    
                    // ZT-DEFENSE: Lock Pessimista evita que o Embarcador e o Cronjob aprovem simultaneamente
                    $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();
                    
                    if ($cargaLock->status !== 'em_auditoria') {
                        return; 
                    }

                    $cargaLock->update(['status' => 'finalizada']);

                    $ciot = Ciot::where('carga_id', $cargaLock->id)->lockForUpdate()->first();
                    
                    if ($ciot && !in_array($ciot->status, ['liquidado', 'processando_liquidacao'], true)) {
                        $ciot->update(['status' => 'processando_liquidacao']);
                        // Delega ao Job especialista para evitar o gargalo do loop do cron
                        LiquidarFreteJob::dispatch((string) $ciot->codigo_ciot)->onQueue('financeiro');
                    }
                });

                Log::info("[AUTO-LIQUIDAÇÃO] Carga {$carga->id} transacionada para liquidação por SLA.");
                $this->line("Carga {$carga->id}: Motorista aprovado tacitamente.");

            } catch (Throwable $e) {
                Log::error("[CRÍTICO - FALHA AUTO-LIQUIDAÇÃO] Carga {$carga->id}: " . $e->getMessage());
                $this->error("Falha ao injetar carga {$carga->id} no pipeline de pagamentos.");
            }
        }

        return Command::SUCCESS;
    }
}