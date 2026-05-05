<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargaPublicacaoLog extends Model
{
    protected $table = 'carga_publicacoes_log';
    
    protected $fillable = [
        'carga_id', 'embarcador_id', 'ip_address', 'user_agent', 'termo_hash', 'publicado_em'
    ];

    protected $casts = [
        'publicado_em' => 'datetime',
    ];
}