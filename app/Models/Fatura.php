<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fatura extends Model
{
    protected $fillable = [
        'embarcador_id', 'codigo_referencia', 'data_vencimento', 
        'valor_fretes', 'valor_taxas', 'valor_total', 'status', 'nfe_url'
    ];

    public function embarcador()
    {
        return $this->belongsTo(Embarcador::class);
    }

    public function cargas()
    {
        return $this->hasMany(Carga::class);
    }
}