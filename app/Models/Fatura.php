<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fatura extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'faturas';
    
    protected $fillable = [
        'embarcador_id',
        'mes_referencia',
        'valor_total', 
        'status',
        'data_vencimento',
        'data_pagamento', 
        'link_boleto',
        'detalhes_cargas'
    ];

    protected $casts = [
        'detalhes_cargas' => 'array',
        'data_vencimento' => 'date',
        'data_pagamento' => 'datetime',
        'valor_total' => 'decimal:2',
    ];

    /**
     * O escopo de Embarcador.
     */
    public function embarcador(): BelongsTo
    {
        return $this->belongsTo(Embarcador::class);
    }

    /**
     * Scopes para indexação e telemetria de alta performance
     */
    public function scopeInadimplentes($query)
    {
        return $query->where('status', 'vencida')
                     ->orWhere(function ($q) {
                         $q->where('status', 'pendente')
                           ->where('data_vencimento', '<', now());
                     });
    }
}