<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Carga;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessarAceiteCarga implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Resiliência Cloud Native: Limite máximo de tentativas.
     */
    public int $tries = 3;

    /**
     * Prevenção de Thundering Herd: Exponential Backoff.
     * Retentativas ocorrerão em 10s, 30s e 60s.
     */
    public array $backoff = [10, 30, 60];

    /**
     * Limite de exceções antes de mover para a Dead Letter Queue (DLQ).
     */
    public int $maxExceptions = 2;

    public function __construct(
        protected readonly int $cargaId, 
        protected readonly int $motoristaUserId, 
        protected readonly string $ipAddress, 
        protected readonly string $userAgent
    ) {}

    /**
     * ID de Idempotência do Job. 
     * Garante que a mesma carga não seja processada simultaneamente por 2 workers distintos.
     */
    public function uniqueId(): string
    {
        return (string) $this->cargaId;
    }

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                // ROW-LEVEL LOCKING: Bloqueia a linha da carga para garantir atomicidade.
                $carga = Carga::where('id', $this->cargaId)->lockForUpdate()->first();

                // Idempotency Check: Se não existir ou o status mutou, descarta a operação silenciosamente.
                if (!$carga || $carga->status !== 'processando_aceite') {
                    Log::warning('[Worker/Matchmaking] Orquestração abortada: Carga já processada ou status mutado.', [
                        'carga_id' => $this->cargaId
                    ]);
                    return;
                }

                $motoristaUser = User::with('motorista')->findOrFail($this->motoristaUserId);
                
                if (!$motoristaUser->motorista || $motoristaUser->status !== 'active') {
                    Log::alert('[Worker/Compliance] Alocação abortada: Usuário/Motorista inativo detectado no momento do processamento.', [
                        'motorista_user_id' => $this->motoristaUserId,
                        'carga_id'          => $this->cargaId
                    ]);
                    
                    // Fallback seguro: Devolve a carga ao pool de matchmaking
                    $carga->update([
                        'status'       => 'publicada',
                        'motorista_id' => null
                    ]);
                    return;
                }

                // Efetivação do Matchmaking e Transição de Estado Logística
                $carga->update([
                    'motorista_id' => $motoristaUser->motorista->id,
                    'status'       => 'alocada'
                ]);

                $valorFormatado = number_format((float) $carga->valor_frete, 2, ',', '.');
                
                // ZT-DEFENSE: Auditoria Forense e Não-Repúdio (Termo de Aceite)
                $termoContrato = sprintf(
                    "ACEITE DE TRANSPORTE AUTÔNOMO (MATCHMAKING). Pelo presente registro eletrônico, o motorista %s (%s) aceita realizar o transporte da carga ID %d, origem: %s/%s, destino: %s/%s, produto: %s (%skg). Valor bruto acordado com o embarcador: R$ %s.",
                    $motoristaUser->name,
                    $motoristaUser->document_number ?? 'N/D',
                    $carga->id,
                    $carga->cidade_origem,
                    $carga->uf_origem,
                    $carga->cidade_destino,
                    $carga->uf_destino,
                    $carga->produto,
                    $carga->peso_kg,
                    $valorFormatado
                );

                $termoHash = hash('sha256', $termoContrato . $this->ipAddress . time());

                DB::table('carga_aceites_log')->insert([
                    'carga_id'     => $carga->id,
                    'motorista_id' => $motoristaUser->motorista->id,
                    'ip_address'   => $this->ipAddress,
                    'user_agent'   => $this->userAgent,
                    'termo_hash'   => $termoHash,
                    'aceito_em'    => now(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                Log::info('[Worker/Matchmaking] Alocação confirmada e trilha de auditoria gerada.', [
                    'carga_id'     => $carga->id,
                    'motorista_id' => $motoristaUser->motorista->id,
                    'termo_hash'   => $termoHash
                ]);
            });
        } catch (Throwable $e) {
            Log::critical('[Worker/Fatal] Falha atômica ao processar o job de matchmaking.', [
                'carga_id'          => $this->cargaId,
                'motorista_user_id' => $this->motoristaUserId,
                'exception'         => $e->getMessage(),
                'trace'             => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}