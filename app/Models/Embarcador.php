<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Security\BlindIndexService;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Embarcador extends Model
{
    use SoftDeletes;

    protected $table = 'embarcadores'; 

    protected $fillable = [
        'user_id',
        'razao_social',
        'cnpj',
        'cnpj_bidx',
        'inscricao_estadual',
        'taxa_frete_percentual',
        'mensalidade_fixa',
        'certificado_a1_path',
        'certificado_a1_senha',
        'certificado_a1_vencimento'
    ];

    protected $hidden = [
        'cnpj',
        'cnpj_bidx',
        'inscricao_estadual',
        'certificado_a1_path',
        'certificado_a1_senha',
    ];

    protected function casts(): array
    {
        return [
            'cnpj' => 'encrypted',
            'inscricao_estadual' => 'encrypted',
            'certificado_a1_senha' => 'encrypted',
            'taxa_frete_percentual' => 'decimal:2',
            'mensalidade_fixa' => 'decimal:2',
            'certificado_a1_vencimento' => 'datetime',
        ];
    }

    /**
     * Motor de Indexação Criptográfica Acionado Automaticamente.
     */
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->isDirty('cnpj') && !empty($model->cnpj)) {
                $model->cnpj_bidx = BlindIndexService::make($model->cnpj);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cargas()
    {
        return $this->hasMany(Carga::class);
    }

    // ZT-DEFENSE: Nova Relação para Múltiplos Endereços / Docas
    public function locaisOperacionais(): HasMany
    {
        return $this->hasMany(LocalOperacional::class);
    }
}