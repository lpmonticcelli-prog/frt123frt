<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carga;
use App\Models\Ciot;
use App\Contracts\PefGatewayInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessarSlaLiquidacao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fretei:liquidar-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audita e liquida automaticamente fretes que estouraram o SLA do Embarcador';

    /**
     * Execute the console command.
     */
    public function handle(PefGatewayInterface $pefGateway)
    {
        $this->info('[123FRETEI WORKER] Iniciando auditoria de SLA de Liquidação...');

        // Define o teto do SLA: 24 horas atrás
        $limiteSla = now()->subHours(24);

        $cargasVencidas = Carga::where('status', 'em_auditoria')
            ->where('em_auditoria_desde', '<=', $limiteSla)
            ->get();

        if ($cargasVencidas->isEmpty()) {
            $this->info('Nenhuma carga com SLA estourado neste ciclo.');
            return Command::SUCCESS;
        }

        foreach ($cargasVencidas as $carga) {
            try {
                DB::transaction(function () use ($carga, $pefGateway) {
                    $ciot = Ciot::where('carga_id', $carga->id)->first();
                    
                    if ($ciot && $ciot->status === 'emitido') {
                        // Força a liquidação financeira na Instituição de Pagamento
                        $pefGateway->liquidarFrete($ciot->codigo_ciot);
                        $ciot->update(['status' => 'liquidado']);
                    }

                    // Encerra o ciclo de vida da carga
                    $carga->update(['status' => 'entregue']);
                });

                Log::info("[AUTO-LIQUIDAÇÃO] Carga {$carga->id} liquidada por estouro de SLA.");
                $this->line("Carga {$carga->id} liquidada e motorista pago com sucesso.");

            } catch (\Exception $e) {
                Log::error("[CRÍTICO - FALHA AUTO-LIQUIDAÇÃO] Carga {$carga->id}: " . $e->getMessage());
                $this->error("Falha ao liquidar carga {$carga->id}. Verifique os logs.");
            }
        }

        return Command::SUCCESS;
    }
}