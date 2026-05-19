<?php

declare(strict_types=1);

namespace App\Services\Reputation;

use App\Models\Avaliacao;
use App\Models\Motorista;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReputacaoMotoristaService
{
    /**
     * Calcula e persiste a avaliação, reclassificando o motorista.
     */
    public function processarAvaliacao(
        int $cargaId, 
        int $embarcadorId, 
        int $motoristaId, 
        int $pontualidade, 
        int $cuidado, 
        int $comunicacao, 
        bool $houveAvaria, 
        ?string $comentarios = null
    ): Avaliacao {
        return DB::transaction(function () use (
            $cargaId, $embarcadorId, $motoristaId, $pontualidade, $cuidado, $comunicacao, $houveAvaria, $comentarios
        ) {
            // Regra Implacável: Avaria anula as boas notas
            $notaFinal = $houveAvaria ? 1.00 : round(($pontualidade + $cuidado + $comunicacao) / 3, 2);

            $avaliacao = Avaliacao::create([
                'carga_id' => $cargaId,
                'embarcador_id' => $embarcadorId,
                'motorista_id' => $motoristaId,
                'nota_pontualidade' => $pontualidade,
                'nota_cuidado' => $cuidado,
                'nota_comunicacao' => $comunicacao,
                'houve_avaria' => $houveAvaria,
                'nota_final' => $notaFinal,
                'comentarios' => $comentarios
            ]);

            if ($houveAvaria) {
                // TODO: Emissão de evento para abrir Disputa Financeira na mesa de operações
                Log::error("[Compliance] Avaria reportada na Carga {$cargaId}. Disputa requerida.");
            }

            $this->recalcularScoreETier(Motorista::findOrFail($motoristaId));

            return $avaliacao;
        });
    }

    /**
     * Recalcula a média móvel (últimas 100 viagens) e ajusta o Tier.
     */
    private function recalcularScoreETier(Motorista $motorista): void
    {
        // Média Móvel das últimas 100 avaliações para garantir volatilidade justa
        $ultimasAvaliacoes = Avaliacao::where('motorista_id', $motorista->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        if ($ultimasAvaliacoes->isEmpty()) {
            return;
        }

        $media = $ultimasAvaliacoes->avg('nota_final');
        $totalViagens = Motorista::where('id', $motorista->id)->value('total_viagens') + 1; // Incrementa a viagem atual

        // Máquina de Estado de Tier
        $novoTier = 'novato';
        if ($totalViagens >= 100 && $media >= 4.9 && !$this->teveAvariaRecente($motorista->id)) {
            $novoTier = 'prime';
        } elseif ($totalViagens >= 50 && $media >= 4.8) {
            $novoTier = 'elite';
        } elseif ($totalViagens >= 10 && $media >= 4.5) {
            $novoTier = 'pro';
        }

        $motorista->update([
            'score_geral' => round((float) $media, 2),
            'total_viagens' => $totalViagens,
            'tier_reputacao' => $novoTier
        ]);

        Log::info("[Reputação] Motorista {$motorista->id} atualizado: Score {$media}, Tier {$novoTier}.");
    }

    /**
     * Verifica se houve avaria nas últimas 50 viagens. Impede o tier 'prime'.
     */
    private function teveAvariaRecente(int $motoristaId): bool
    {
        return Avaliacao::where('motorista_id', $motoristaId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->where('houve_avaria', true)
            ->exists();
    }
}