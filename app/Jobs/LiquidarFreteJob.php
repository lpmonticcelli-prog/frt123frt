<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Ciot;
use App\Models\Transacao;
use App\Models\PagamentoEscrow;
use App\Contracts\PefGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class LiquidarFreteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [15, 45, 90]; 

    public function __construct(protected string $codigoCiot) {}

    public function handle(PefGatewayInterface $pefGateway): void
    {
        if (empty($this->codigoCiot)) {
            Log::error("[Worker/Financeiro] Erro Crítico: Tentativa de liquidar CIOT nulo.");
            return;
        }

        $ciotCheck = Ciot::where('codigo_ciot', $this->codigoCiot)->first();

        if (!$ciotCheck) {
            Log::error("[Worker/Financeiro] CIOT {$this->codigoCiot} não localizado na malha.");
            return;
        }

        if ($ciotCheck->status === 'liquidado') {
            return; 
        }

        try {
            // ZT-DEFENSE: Comunicação de liquidação HTTP externa fora do DB::transaction
            $sucesso = $pefGateway->liquidarFrete($this->codigoCiot);
            
            if ($sucesso) {
                DB::transaction(function () {
                    // Double-Check Locking absoluto para evitar saque duplicado do Escrow
                    $ciot = Ciot::with('carga')->where('codigo_ciot', $this->codigoCiot)->lockForUpdate()->firstOrFail();

                    if ($ciot->status === 'liquidado') return;

                    $ciot->update(['status' => 'liquidado']);
                    
                    Transacao::create([
                        'motorista_id' => $ciot->carga->motorista_id,
                        'carga_id' => $ciot->carga_id,
                        'tipo' => 'credito',
                        'valor' => $ciot->valor_frete_liquido,
                        'descricao' => "Liquidação PEF - CIOT: {$ciot->codigo_ciot}"
                    ]);

                    PagamentoEscrow::where('carga_id', $ciot->carga_id)
                        ->where('status', 'liquidado')
                        ->update(['status' => 'estornado']);

                    $ciot->carga->update(['status' => 'concluida']);

                    Log::info("[Worker/Treasury] CIOT {$this->codigoCiot} liquidado. Split Executado. R$ {$ciot->valor_frete_liquido} repassados à carteira do motorista.");
                });
            } else {
                throw new \Exception("A Integradora financeira bloqueou a ordem de saque para o CIOT {$this->codigoCiot}.");
            }
        } catch (Throwable $e) {
            Log::error("[Worker/Treasury] Falha na liquidação do CIOT {$this->codigoCiot}: " . $e->getMessage());
            throw $e;
        }
    }
}