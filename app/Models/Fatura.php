<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fatura extends Model
{
    protected $table = 'faturas';
    
    protected $fillable = [
        'embarcador_id', 'mes_referencia', 'valor_total', 
        'status', 'data_vencimento', 'data_pagamento', 
        'link_boleto', 'detalhes_cargas'
    ];

    protected $casts = [
        'detalhes_cargas' => 'array',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
    ];

    public function embarcador()
    {
        return $this->belongsTo(Embarcador::class);
    }
}