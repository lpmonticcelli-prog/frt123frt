<?php

namespace App\Jobs;

use App\Models\Carga;
use App\Models\Ciot;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessarAceiteCarga implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $cargaId;
    protected int $motoristaUserId;
    protected string $ipAddress;
    protected string $userAgent;

    public function __construct(int $cargaId, int $motoristaUserId, string $ipAddress, string $userAgent)
    {
        $this->cargaId = $cargaId;
        $this->motoristaUserId = $motoristaUserId;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $carga = Carga::where('id', $this->cargaId)->lockForUpdate()->first();

            if (!$carga || $carga->status !== 'processando_aceite') {
                Log::warning("[Worker] Processamento cancelado: Carga {$this->cargaId} não se encontra em processamento.");
                return;
            }

            $motoristaUser = User::with('motorista')->findOrFail($this->motoristaUserId);
            
            if (!$motoristaUser->motorista || $motoristaUser->status !== 'active') {
                Log::error("[Worker] Processamento abortado: Usuário bloqueado.");
                return;
            }

            $valorFormatado = number_format($carga->valor_frete, 2, ',', '.');
            
            $termoContrato = "CONTRATO DE TRANSPORTE AUTÔNOMO DE CARGA. "
                           . "Pelo presente aceite eletrônico, o motorista {$motoristaUser->name} "
                           . "aceita realizar o transporte da carga ID {$carga->id}, "
                           . "com origem em {$carga->cidade_origem}/{$carga->uf_origem} e destino a {$carga->cidade_destino}/{$carga->uf_destino}, "
                           . "referente ao produto {$carga->produto} ({$carga->peso_kg}kg), "
                           . "pelo valor acordado de R$ {$valorFormatado}.";

            $termoHash = hash('sha256', $termoContrato);

            DB::table('carga_aceites_log')->insert([
                'carga_id' => $carga->id,
                'motorista_id' => $motoristaUser->motorista->id,
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'termo_hash' => $termoHash,
                'aceito_em' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Cria o registo base do CIOT. Os impostos serão preenchidos no próximo passo.
            $ciot = Ciot::create([
                'idempotency_key' => (string) Str::uuid(),
                'carga_id' => $carga->id,
                'embarcador_id' => $carga->embarcador_id,
                'motorista_id' => $motoristaUser->motorista->id,
                'status' => 'processando',
                'valor_frete_bruto' => $carga->valor_frete,
                'valor_frete_liquido' => 0.00,
                'taxa_123fretei' => $carga->taxa_plataforma,
            ]);

            // Despacha o Job final para gerar o CIOT
            SolicitarEmissaoCiotJob::dispatch($ciot->id)->onQueue('default');

            Log::info("[Worker] Contrato assinado. Carga {$carga->id} preparada. CIOT enfileirado.");
        }, 3);
    }
}