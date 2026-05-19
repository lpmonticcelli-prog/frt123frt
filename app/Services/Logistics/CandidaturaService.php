<?php

declare(strict_types=1);

namespace App\Services\Logistics;

use App\Models\Carga;
use App\Models\CargaCandidatura;
use App\Models\Motorista;
use App\Events\CargaAtualizada;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class CandidaturaService
{
    /**
     * Aplica um motorista a uma carga de forma atômica (O Lance).
     */
    public function aplicar(Motorista $motorista, Carga $carga): CargaCandidatura
    {
        if ($carga->status !== 'publicada') {
            throw new Exception('Esta carga não está mais disponível para lances.');
        }

        if ($motorista->suspenso_ate && $motorista->suspenso_ate->isFuture()) {
            throw new Exception('Seu perfil está temporariamente suspenso para novas candidaturas.');
        }

        // Limite de contenção de spam (máximo 10 candidaturas ativas simultâneas)
        $candidaturasAtivas = CargaCandidatura::where('motorista_id', $motorista->id)
            ->where('status', 'pendente')
            ->count();

        if ($candidaturasAtivas >= 10) {
            throw new Exception('Você atingiu o limite de candidaturas simultâneas.');
        }

        return DB::transaction(function () use ($motorista, $carga) {
            return CargaCandidatura::firstOrCreate(
                [
                    'carga_id' => $carga->id,
                    'motorista_id' => $motorista->id,
                ],
                [
                    'status' => 'pendente',
                    'expires_at' => Carbon::now()->addHours(4) // TTL de 4 horas
                ]
            );
        });
    }

    /**
     * Embarcador escolhe um motorista. Orquestração crítica de transição de estado.
     */
    public function aprovarCandidato(int $cargaId, int $candidaturaId, int $embarcadorId): void
    {
        // Variável auxiliar para disparar evento fora do bloqueio de banco de dados
        $cargaAtualizada = null;

        DB::transaction(function () use ($cargaId, $candidaturaId, $embarcadorId, &$cargaAtualizada) {
            // Lock Pessimista na Carga para evitar double-booking
            $carga = Carga::where('id', $cargaId)
                ->where('embarcador_id', $embarcadorId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($carga->status !== 'publicada') {
                throw new Exception('Esta carga já foi atribuída ou cancelada.');
            }

            $candidatura = CargaCandidatura::where('id', $candidaturaId)
                ->where('carga_id', $carga->id)
                ->where('status', 'pendente')
                ->firstOrFail();

            // 1. Aprova a candidatura selecionada
            $candidatura->update(['status' => 'aprovada']);

            // 2. Rejeita automaticamente todas as outras candidaturas desta carga
            CargaCandidatura::where('carga_id', $carga->id)
                ->where('id', '!=', $candidaturaId)
                ->update(['status' => 'rejeitada']);

            // 3. Atualiza a carga (Sincronizado com o Vue: 'processando_aceite' ou 'alocada')
            $carga->update([
                'status' => 'processando_aceite',
                'motorista_id' => $candidatura->motorista_id
            ]);

            $cargaAtualizada = clone $carga;

            Log::info("[Bidding] Embarcador {$embarcadorId} aprovou motorista {$candidatura->motorista_id} para carga {$cargaId}.");
        });

        // 4. Dispara a notificação Websocket para o Frontend do Motorista instantaneamente
        if ($cargaAtualizada) {
            CargaAtualizada::dispatch($cargaAtualizada);
        }
    }

    /**
     * Motorista desiste APÓS ter sido aprovado. Aplica punição severa e devolve a carga.
     */
    public function cancelarPosAprovacao(Motorista $motorista, Carga $carga): void
    {
        $cargaAtualizada = null;

        DB::transaction(function () use ($motorista, $carga, &$cargaAtualizada) {
            $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

            if ($cargaLock->motorista_id !== $motorista->id) {
                throw new Exception('Você não é o motorista desta carga.');
            }

            // Devolve a carga para o mural público
            $cargaLock->update([
                'status' => 'publicada',
                'motorista_id' => null
            ]);

            $candidatura = CargaCandidatura::where('carga_id', $carga->id)
                ->where('motorista_id', $motorista->id)
                ->first();

            if ($candidatura) {
                $candidatura->update(['status' => 'cancelada_motorista']);
            }

            // PUNIÇÃO: Suspensão de 24h
            $motorista->update([
                'suspenso_ate' => Carbon::now()->addHours(24)
            ]);

            $cargaAtualizada = clone $cargaLock;

            Log::warning("[Anti-Trust] Motorista {$motorista->id} sofreu block de 24h por cancelar carga {$carga->id} pós-aprovação.");
        });

        // Atualiza os murais para mostrar a carga novamente
        if ($cargaAtualizada) {
            CargaAtualizada::dispatch($cargaAtualizada);
        }
    }
}