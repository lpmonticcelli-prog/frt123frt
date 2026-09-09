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

        // ==========================================
        // 3. REGRA MÁXIMA (O PODER DO EMBARCADOR)
        // ==========================================
        if ($carga->exigir_seguro_iza && !$temSeguro) {
            return [
                'permitido'         => false,
                'motivo'            => 'seguro_obrigatorio_pelo_embarcador',
                'mensagem'          => 'O dono desta carga EXIGE que o motorista tenha o Seguro IZA Ativo. A opção de assumir o risco não é aceita para este frete.',
                'exibir_oferta_iza' => true, 
                'bloquear_isencao'  => true // Flag para o Vue.js ESCONDER o botão de assumir risco
            ];
        }

        // 4. Regra Padrão da Plataforma (Embarcador não exigiu o seguro)
        if (!$temSeguro && !$assumiuRisco) {
            return [
                'permitido'         => false,
                'motivo'            => 'requer_seguro_pessoal_ou_isencao',
                'mensagem'          => 'Para sua segurança, exigimos um Seguro de Acidentes Pessoais ativo ou a assinatura do Termo de Assunção de Risco para liberar esta viagem.',
                'exibir_oferta_iza' => true,
                'bloquear_isencao'  => false // Permite que o Vue.js exiba o botão de isenção
            ];
        }

        // 5. Se chegou aqui, o caminho está livre!
        // (Ou ele tem o seguro pago, ou ele aceitou o risco de forma registrada numa carga permitida)
        return [
            'permitido'         => true,
            'motivo'            => $temSeguro ? 'seguro_iza_ativo' : 'isencao_risco_assinada',
            'exibir_oferta_iza' => false,
            'bloquear_isencao'  => false
        ];
    }
}