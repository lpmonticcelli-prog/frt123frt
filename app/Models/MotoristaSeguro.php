<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotoristaSeguro extends Model
{
    use HasFactory, SoftDeletes;

    // Nome da tabela correspondente
    protected $table = 'motorista_seguros';

    // Campos permitidos para preenchimento em massa
    protected $fillable = [
        'motorista_id',
        'iza_policy_id',
        'plano',
        'status',
        'data_inicio_vigencia',
        'data_fim_vigencia',
        'payload_retorno',
    ];

    // Conversões de tipo automáticas do Laravel
    protected $casts = [
        'data_inicio_vigencia' => 'datetime',
        'data_fim_vigencia' => 'datetime',
        'payload_retorno' => 'array', // Converte o JSON do banco para Array no PHP
    ];

    /**
     * Relacionamento: Este registro de seguro pertence a um motorista.
     */
    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class);
    }

    /**
     * Helper: Verifica se o seguro está ativo neste momento.
     * Útil para usar em Middlewares ou na sua classe BypassRiskManager.
     */
    public function isAtivo(): bool
    {
        return $this->status === 'ativo' 
            && $this->data_fim_vigencia 
            && $this->data_fim_vigencia->isFuture();
    }
}