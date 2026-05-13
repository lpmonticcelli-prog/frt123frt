<?php

namespace App\Jobs;

use App\Models\Ciot;
use App\Models\Transacao;
use App\Contracts\PefGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LiquidarFreteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [15, 45, 90]; 

    protected string $codigoCiot;

    public function __construct(string $codigoCiot)
    {
        $this->codigoCiot = $codigoCiot;
    }

    public function handle(PefGatewayInterface $pefGateway): void
    {
        DB::transaction(function () use ($pefGateway) {
            // Eager load da carga para acedermos ao ID do motorista
            $ciot = Ciot::with('carga')->where('codigo_ciot', $this->codigoCiot)->lockForUpdate()->first();

            if (!$ciot) {
                Log::error("[Worker] CIOT {$this->codigoCiot} não localizado.");
                return;
            }

            if ($ciot->status === 'liquidado') {
                return; // Idempotência
            }

            try {
                // Em laboratório, o Mock do PEF deve retornar true.
                $sucesso = $pefGateway->liquidarFrete($this->codigoCiot);
                
                if ($sucesso) {
                    $ciot->update(['status' => 'liquidado']);
                    
                    // CIRURGIA: Depósito automático na Carteira do Motorista (Valor Líquido)
                    Transacao::create([
                        'motorista_id' => $ciot->carga->motorista_id,
                        'carga_id' => $ciot->carga_id,
                        'tipo' => 'credito',
                        'valor' => $ciot->valor_frete_liquido,
                        'descricao' => "Liquidação PEF - CIOT: {$ciot->codigo_ciot}"
                    ]);

                    Log::info("[Worker] CIOT {$this->codigoCiot} liquidado. R$ {$ciot->valor_frete_liquido} creditados na carteira do motorista.");
                }
            } catch (\Exception $e) {
                Log::error("[Worker] Falha na liquidação do CIOT {$this->codigoCiot}: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }
}