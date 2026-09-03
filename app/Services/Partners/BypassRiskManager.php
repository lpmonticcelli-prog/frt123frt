<?php

namespace App\Services\Partners;

use App\Models\Motorista;
use App\Models\Carga;

class BypassRiskManager
{
    public function avaliar(Motorista $motorista, Carga $carga): array
    {
        // 1. Verifica se o motorista marcou a caixinha de assumir o risco lá no Modal
        $assumiuRisco = request()->boolean('assumiu_risco_sem_seguro');

        // 2. Verifica se ele tem o seguro ativo lendo as novas colunas nativas do banco
        $temSeguro = $motorista->seguro_iza_status === 'ativo' && $motorista->seguro_iza_vencimento > now();

        // 3. Se não tem seguro E não assumiu o risco, bloqueia e manda abrir o Modal da IZA
        if (!$temSeguro && !$assumiuRisco) {
            return [
                'permitido'         => false,
                'motivo'            => 'requer_seguro_pessoal_ou_isencao',
                'mensagem'          => 'Para sua segurança, exigimos um Seguro de Acidentes Pessoais ativo ou a assinatura do Termo de Assunção de Risco para liberar esta viagem.',
                'exibir_oferta_iza' => true, // <-- Aciona o SeguroBloqueioModal.vue
            ];
        }

        // 4. Se chegou aqui, o caminho está livre!
        // (Ou ele tem o seguro pago, ou ele aceitou o risco de forma registrada)
        return [
            'permitido'         => true,
            'motivo'            => $assumiuRisco ? 'isencao_risco_assinada' : 'seguro_iza_ativo',
            'exibir_oferta_iza' => false,
        ];
    }
}