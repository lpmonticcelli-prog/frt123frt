<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalOperacional extends Model
{
    use SoftDeletes;

    protected $table = 'locais_operacionais';

    protected $fillable = [
        'embarcador_id',
        'localidade_id',
        'nome_identificador',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'latitude',
        'longitude',
        'is_padrao',
    ];

    protected $casts = [
        'is_padrao' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function embarcador(): BelongsTo
    {
        return $this->belongsTo(Embarcador::class);
    }
}