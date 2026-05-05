<?php

namespace App\Jobs;

use App\Models\Ciot;
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
        // Travamento na transação para evitar "Race Condition" de liquidação múltipla
        DB::transaction(function () use ($pefGateway) {
            $ciot = Ciot::where('codigo_ciot', $this->codigoCiot)->lockForUpdate()->first();

            if (!$ciot) {
                Log::error("[Worker] CIOT {$this->codigoCiot} não localizado. Abortando liquidação.");
                return;
            }

            // Defesa 1: Controle local de idempotência
            if ($ciot->status === 'liquidado') {
                Log::info("[Worker] CIOT {$this->codigoCiot} já consta como liquidado no 123fretei. Ignorando chamada duplicada ao PEF.");
                return;
            }

            try {
                // Defesa 2: O PEF Gateway deve usar a $ciot->idempotency_key no seu Header de requisição (Idempotency-Key)
                $sucesso = $pefGateway->liquidarFrete($this->codigoCiot);
                
                if ($sucesso) {
                    $ciot->update(['status' => 'liquidado']);
                    Log::info("[Worker] CIOT {$this->codigoCiot} liquidado no Gateway PEF e sincronizado com sucesso.");
                } else {
                    Log::warning("[Worker] Gateway PEF recusou a liquidação do CIOT {$this->codigoCiot}. Analisar regras de negócio externas.");
                }
            } catch (\Exception $e) {
                Log::error("[Worker] Falha de comunicação/liquidação do CIOT {$this->codigoCiot}: " . $e->getMessage());
                // Propaga a exceção para que a fila acione as regras de backoff de forma segura
                throw $e;
            }
        }, 3);
    }
}