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
use Exception;

class CandidaturaService
{
    /**
     * Aplica um motorista a uma carga de forma atômica (O Lance).
     */
    public function aplicar(Motorista $motorista, Carga $carga): CargaCandidatura
    {
        // ZT-DEFENSE: Validação Absoluta de Integridade e Risco (O Escudo)
        // Bloqueia se o motorista estiver suspenso, com KYC interno pendente ou reprovado na Gerenciadora de Risco.
        if (!$motorista->podeAceitarFrete()) {
            throw new Exception('O seu perfil não está autorizado a aceitar fretes neste momento. Verifique se o seu cadastro está aprovado pela auditoria interna e pela Gerenciadora de Risco (Trans Sat).');
        }

        return DB::transaction(function () use ($motorista, $carga) {
            // ZT-DEFENSE: Serialização da sessão do motorista.
            // Trava o registro do motorista para impedir Race Condition na contagem de spam.
            Motorista::where('id', $motorista->id)->lockForUpdate()->firstOrFail();

            // Limite de contenção de spam (máximo 10 candidaturas ativas simultâneas)
            $candidaturasAtivas = CargaCandidatura::where('motorista_id', $motorista->id)
                ->where('status', 'pendente')
                ->count();

            if ($candidaturasAtivas >= 10) {
                throw new Exception('Você atingiu o limite de candidaturas simultâneas.');
            }

            // ZT-DEFENSE: Lock pessimista na Carga.
            // Movemos a validação de status para dentro da transação para erradicar o TOCTOU (Ghost Bids).
            $cargaLock = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();

            if ($cargaLock->status !== 'publicada') {
                throw new Exception('Esta carga não está mais disponível para lances.');
            }

            return CargaCandidatura::firstOrCreate(
                [
                    'carga_id' => $cargaLock->id,
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
        // Variáveis auxiliares para orquestração fora do bloqueio de banco de dados
        $cargaAtualizada = null;
        $motoristaUserId = null;

        DB::transaction(function () use ($cargaId, $candidaturaId, $embarcadorId, &$cargaAtualizada, &$motoristaUserId) {
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

            // Recupera a identidade base do Motorista para o Job subsequente
            $motoristaUserId = Motorista::where('id', $candidatura->motorista_id)->value('user_id');
            $cargaAtualizada = clone $carga;

            Log::info("[Bidding] Embarcador {$embarcadorId} aprovou motorista {$candidatura->motorista_id} para carga {$cargaId}.");
        });

        // ZT-DEFENSE: Resolução do Deadlock Operacional.
        // O Job agora é corretamente empurrado para a fila após o commit, assumindo o controle da máquina de estado.
        if ($motoristaUserId) {
            ProcessarAceiteCarga::dispatch(
                $cargaId,
                $motoristaUserId,
                request()->ip() ?? '127.0.0.1',
                request()->userAgent() ?? 'Automação de Orquestração B2B'
            )->onQueue('default');
        }

        // Dispara a notificação Websocket para o Frontend do Motorista
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

            // ZT-DEFENSE: Prevenção Atômica contra Roubo de Carga.
            // Barra a desistência caso a mercadoria já esteja na estrada ou além.
            $statusBlindados = ['em_transito', 'em_auditoria', 'entregue', 'pago', 'concluido', 'finalizada', 'em_disputa'];
            
            if (in_array($cargaLock->status, $statusBlindados, true)) {
                Log::alert("[SECURITY AUDIT] Tentativa de roubo/evasão bloqueada. Motorista ID {$motorista->id} tentou cancelar a Carga ID {$carga->id} no status: {$cargaLock->status}.");
                throw new Exception('Ação bloqueada de forma irrevogável. O transporte já foi iniciado ou concluído. Entre em contato com a mesa de operações de suporte.');
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