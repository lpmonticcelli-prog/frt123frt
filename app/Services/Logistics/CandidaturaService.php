<?php

declare(strict_types=1);

namespace App\Services\Logistics;

use App\Models\Carga;
use App\Models\CargaCandidatura;
use App\Models\Motorista;
use App\Events\CargaAtualizada;
use App\Jobs\ProcessarAceiteCarga;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use DomainException;

class CandidaturaService
{
    // Definição estrita da máquina de estado do agregado Carga
    private const STATUS_PUBLICADA = 'publicada';
    private const STATUS_PROCESSANDO_ACEITE = 'processando_aceite';
    private const STATUS_PENDENTE = 'pendente';
    private const STATUS_APROVADA = 'aprovada';
    private const STATUS_REJEITADA = 'rejeitada';
    private const STATUS_CANCELADA_MOTORISTA = 'cancelada_motorista';

    /**
     * Aplica um motorista a uma carga de forma atômica (O Lance).
     *
     * @throws DomainException
     */
    public function aplicar(Motorista $motorista, Carga $carga): CargaCandidatura
    {
        if (!$motorista->podeAceitarFrete()) {
            $mensagemErro = config('services.gr.enabled', false)
                ? 'pela auditoria interna e pela Gerenciadora de Risco (Trans Sat).'
                : 'pela auditoria interna (KYC base).';

            throw new DomainException("O seu perfil não está autorizado a aceitar fretes neste momento. Verifique se o seu cadastro está aprovado {$mensagemErro}");
        }

        return DB::transaction(function () use ($motorista, $carga) {
            // ZT-DEFENSE (Lock Hierarchy Step 1): Lock pessimista na Carga (Aggregate Root).
            // Sempre travamos a Carga antes do Motorista para erradicar risco de Deadlock cruzado.
            $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

            if ($cargaLock->status !== self::STATUS_PUBLICADA) {
                throw new DomainException('Esta carga não está mais disponível para lances.');
            }

            // ZT-DEFENSE (Lock Hierarchy Step 2): Lock pessimista no Motorista.
            $motoristaLock = Motorista::where('id', $motorista->id)->lockForUpdate()->firstOrFail();

            $candidaturasAtivas = CargaCandidatura::where('motorista_id', $motoristaLock->id)
                ->where('status', self::STATUS_PENDENTE)
                ->count();

            if ($candidaturasAtivas >= 10) {
                throw new DomainException('Você atingiu o limite de candidaturas simultâneas.');
            }

            return CargaCandidatura::firstOrCreate(
                [
                    'carga_id'     => $cargaLock->id,
                    'motorista_id' => $motoristaLock->id,
                ],
                [
                    'status'     => self::STATUS_PENDENTE,
                    'expires_at' => Carbon::now()->addHours(4) // TTL de 4 horas
                ]
            );
        });
    }

    /**
     * Embarcador escolhe um motorista. Orquestração crítica de transição de estado.
     *
     * @throws DomainException
     */
    public function aprovarCandidato(int $cargaId, int $candidaturaId, int $embarcadorId): void
    {
        $cargaAtualizada = null;
        $motoristaUserId = null;

        DB::transaction(function () use ($cargaId, $candidaturaId, $embarcadorId, &$cargaAtualizada, &$motoristaUserId) {
            // Lock Pessimista na Carga (Aggregate Root)
            $cargaLock = Carga::where('id', $cargaId)
                ->where('embarcador_id', $embarcadorId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cargaLock->status !== self::STATUS_PUBLICADA) {
                throw new DomainException('Esta carga já foi atribuída ou seu status foi alterado.');
            }

            // Lock na Candidatura com Eager Loading otimizado para evitar N+1 no user_id
            $candidatura = CargaCandidatura::with('motorista:id,user_id')
                ->where('id', $candidaturaId)
                ->where('carga_id', $cargaLock->id)
                ->where('status', self::STATUS_PENDENTE)
                ->lockForUpdate()
                ->firstOrFail();

            $candidatura->update(['status' => self::STATUS_APROVADA]);

            // Rejeita automaticamente todas as outras candidaturas em massa (Operação DML otimizada)
            CargaCandidatura::where('carga_id', $cargaLock->id)
                ->where('id', '!=', $candidaturaId)
                ->update(['status' => self::STATUS_REJEITADA]);

            $cargaLock->update([
                'status'       => self::STATUS_PROCESSANDO_ACEITE,
                'motorista_id' => $candidatura->motorista_id
            ]);

            // Desacoplamento para o evento/job de orquestração fora da transação
            $motoristaUserId = $candidatura->motorista->user_id;
            $cargaAtualizada = clone $cargaLock;

            Log::info('[Matchmaking] Aprovação consolidada.', [
                'embarcador_id' => $embarcadorId,
                'motorista_id'  => $candidatura->motorista_id,
                'carga_id'      => $cargaId
            ]);
        });

        // Delegação para background job (Worker Horizon). Exime a API de latência HTTP síncrona.
        if ($motoristaUserId !== null) {
            ProcessarAceiteCarga::dispatch(
                $cargaId,
                $motoristaUserId,
                request()->ip() ?? '127.0.0.1',
                request()->userAgent() ?? 'Automação Orquestração B2B'
            )->onQueue('default');
        }

        if ($cargaAtualizada !== null) {
            event(new CargaAtualizada($cargaAtualizada));
        }
    }

    /**
     * Motorista desiste APÓS ter sido aprovado. Aplica punição e devolve a carga ao mercado.
     *
     * @throws DomainException
     */
    public function cancelarPosAprovacao(Motorista $motorista, Carga $carga): void
    {
        $cargaAtualizada = null;

        DB::transaction(function () use ($motorista, $carga, &$cargaAtualizada) {
            // ZT-DEFENSE (Lock Hierarchy Step 1): Carga
            $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

            if ($cargaLock->motorista_id !== $motorista->id) {
                throw new DomainException('Identidade não corresponde ao motorista alocado nesta carga.');
            }

            // Prevenção atômica de evasão logística (Hard Coded Status Protection)
            $statusBlindados = [
                'em_transito', 'em_auditoria', 'entregue', 'pago', 'concluido', 'finalizada', 'em_disputa'
            ];
            
            if (in_array($cargaLock->status, $statusBlindados, true)) {
                Log::alert('[SECURITY AUDIT] Tentativa de evasão de frete bloqueada.', [
                    'motorista_id'   => $motorista->id,
                    'carga_id'       => $cargaLock->id,
                    'current_status' => $cargaLock->status
                ]);
                throw new DomainException('Cancelamento bloqueado irrevogavelmente. O transporte logístico já foi iniciado.');
            }

            // ZT-DEFENSE (Lock Hierarchy Step 2): Motorista
            $motoristaLock = Motorista::where('id', $motorista->id)->lockForUpdate()->firstOrFail();

            // Devolução da carga ao pool público
            $cargaLock->update([
                'status'       => self::STATUS_PUBLICADA,
                'motorista_id' => null
            ]);

            CargaCandidatura::where('carga_id', $cargaLock->id)
                ->where('motorista_id', $motoristaLock->id)
                ->update(['status' => self::STATUS_CANCELADA_MOTORISTA]);

            // SLA Penalty
            $motoristaLock->update([
                'suspenso_ate' => Carbon::now()->addHours(24)
            ]);

            $cargaAtualizada = clone $cargaLock;

            Log::warning('[Anti-Trust] Punição de SLA aplicada.', [
                'motorista_id' => $motoristaLock->id,
                'carga_id'     => $cargaLock->id,
                'penalty'      => '24h_suspension'
            ]);
        });

        if ($cargaAtualizada !== null) {
            event(new CargaAtualizada($cargaAtualizada));
        }
    }
}