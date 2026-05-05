<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargaAceiteLog extends Model
{
    protected $table = 'carga_aceites_log';
    
    protected $fillable = [
        'carga_id', 'motorista_id', 'ip_address', 'user_agent', 'termo_hash', 'aceito_em'
    ];

    protected $casts = [
        'aceito_em' => 'datetime',
    ];
}