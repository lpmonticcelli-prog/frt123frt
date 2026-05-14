<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parceiro extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nome',
        'categoria',
        'audience',
        'descricao',
        'imagem_url',
        'link_url',
        'conteudo_contrato',
        'is_active',
        'ordem_exibicao',
        // NOVOS CAMPOS FINANCEIROS E TEMPORAIS
        'valor_cobrado',
        'modelo_cobranca',
        'data_inicio',
        'data_fim',
        'codigo_contrato_externo',
        'cliques_acumulados',
        'posicionamento',
        'status_financeiro'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ordem_exibicao' => 'integer',
        'valor_cobrado' => 'decimal:2',
        'cliques_acumulados' => 'integer',
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];
}