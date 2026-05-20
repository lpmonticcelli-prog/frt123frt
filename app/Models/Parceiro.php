<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Parceiro extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nome', 'categoria', 'audience', 'descricao', 'imagem_url', 'link_url', 'conteudo_contrato',
        'is_active', 'ordem_exibicao', 'valor_cobrado', 'modelo_cobranca', 'data_inicio', 'data_fim',
        'codigo_contrato_externo', 'cliques_acumulados', 'posicionamento', 'status_financeiro',
        'dias_duracao', 'data_ativacao', 'data_expiracao',
        // Adicionados para o motor AdTech real:
        'limite_cliques', 'limite_conversoes', 'conversoes_acumuladas'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ordem_exibicao' => 'integer',
        'valor_cobrado' => 'decimal:2',
        'cliques_acumulados' => 'integer',
        'conversoes_acumuladas' => 'integer',
        'limite_cliques' => 'integer',
        'limite_conversoes' => 'integer',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'dias_duracao' => 'integer',
        'data_ativacao' => 'datetime',
        'data_expiracao' => 'datetime',
    ];

    /**
     * O MOTOR ADTECH: Só retorna anúncios que ainda têm saldo (Tempo, Cliques ou Conversões).
     */
    public function scopeAtivosPublicos(Builder $query)
    {
        return $query->where('is_active', true)
            ->whereIn('status_financeiro', ['pago', 'isento'])
            ->where(function ($q) {
                // REGRA 1: Assinatura ou Gratuito (Morre quando a data passa)
                $q->where(function ($sub) {
                    $sub->whereIn('modelo_cobranca', ['assinatura', 'gratuito'])
                        ->whereNotNull('data_expiracao')
                        ->where('data_expiracao', '>', now());
                })
                // REGRA 2: CPC - Custo Por Clique (Morre quando os cliques chegam ao limite)
                ->orWhere(function ($sub) {
                    $sub->where('modelo_cobranca', 'cpc')
                        ->whereColumn('cliques_acumulados', '<', 'limite_cliques');
                })
                // REGRA 3: CPA - Custo Por Ação (Morre quando as conversões chegam ao limite)
                ->orWhere(function ($sub) {
                    $sub->where('modelo_cobranca', 'cpa')
                        ->whereColumn('conversoes_acumuladas', '<', 'limite_conversoes');
                });
            });
    }
}