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

    // INJEÇÃO RELACIONAL: Permite saber qual embarcador publicou a carga
    public function embarcador() 
    { 
        return $this->belongsTo(Embarcador::class, 'embarcador_id', 'id'); 
    }

    public function carga() 
    { 
        return $this->belongsTo(Carga::class, 'carga_id', 'id'); 
    }
}