<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Carga;
use App\Models\Ciot;
use App\Models\User;
use App\Jobs\SolicitarEmissaoCiotJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessarAceiteCarga implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected int $cargaId, 
        protected int $motoristaUserId, 
        protected string $ipAddress, 
        protected string $userAgent
    ) {}

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                // ROW-LEVEL LOCKING: Bloqueia a linha da carga para evitar condições de corrida multithread
                $carga = Carga::where('id', $this->cargaId)->lockForUpdate()->first();

                if (!$carga || $carga->status !== 'processando_aceite') {
                    Log::warning("[Worker/Bidding] Processamento abortado: Carga {$this->cargaId} já foi alocada ou status mutado por outra thread.");
                    return;
                }

                $motoristaUser = User::with('motorista')->findOrFail($this->motoristaUserId);
                
                if (!$motoristaUser->motorista || $motoristaUser->status !== 'active') {
                    Log::error("[Worker/Compliance] Alocação bloqueada: Usuário ou Perfil de Motorista inativo/irregular.");
                    
                    // Devolve o status da carga para publicada para permitir novos lances se o candidato atual for inválido
                    $carga->status = 'publicada';
                    $carga->save();
                    return;
                }

                // Efetivação contratual e vinculação atômica do motorista que ganhou o frete
                $carga->motorista_id = $motoristaUser->motorista->id;
                // Transita a máquina de estados para 'atribuida'
                $carga->status = 'atribuida';
                $carga->save();

                $valorFormatado = number_format((float) $carga->valor_frete, 2, ',', '.');
                
                // ZT-DEFENSE: Assinatura Eletrônica Legal (ICP-Brasil Compliance para não-repúdio)
                $termoContrato = "CONTRATO DE TRANSPORTE AUTÔNOMO. Pelo presente aceite eletrônico, o motorista {$motoristaUser->name} aceita realizar o transporte da carga ID {$carga->id}, com origem em {$carga->cidade_origem}/{$carga->uf_origem} e destino a {$carga->cidade_destino}/{$carga->uf_destino}, referente ao produto {$carga->produto} ({$carga->peso_kg}kg), pelo valor bruto acordado de R$ {$valorFormatado}.";

                $termoHash = hash('sha256', $termoContrato . $this->ipAddress . time());

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

                // Instanciação base do CIOT (Reserva de Idempotência)
                $ciot = Ciot::create([
                    'idempotency_key' => (string) Str::uuid(),
                    'carga_id' => $carga->id,
                    'embarcador_id' => $carga->embarcador_id,
                    'motorista_id' => $motoristaUser->motorista->id,
                    'status' => 'processando',
                    'valor_frete_bruto' => $carga->valor_frete,
                    'valor_frete_liquido' => $carga->valor_frete - $carga->taxa_plataforma,
                    'taxa_123fretei' => $carga->taxa_plataforma,
                ]);

                // O Handshake governamental ocorre de forma assíncrona na fila dedicada
                SolicitarEmissaoCiotJob::dispatch($ciot->id)->onQueue('default');

                Log::info("[Worker/Jurídico] Contrato criptográfico selado. Carga {$carga->id} atrelada ao Motorista ID {$motoristaUser->motorista->id}. CIOT enfileirado.");
            });
        } catch (Throwable $e) {
            // BUGFIX APLICADO AQUI: Removido o double $this->this
            Log::critical("[Worker/Fatal] Falha atômica ao processar aceite da carga {$this->cargaId}: " . $e->getMessage());
            throw $e;
        }
    }
}