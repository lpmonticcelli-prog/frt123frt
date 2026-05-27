<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargaTelemetria extends Model
{
    // Otimização letal: Desativa o motor de timestamps de atualização para poupar I/O. 
    public $timestamps = false; 

    protected $table = 'carga_telemetria_logs';

    protected $fillable = [
        'carga_id', 
        'motorista_id', 
        'lat', 
        'lng', 
        'heading', 
        'registrado_em',
        'created_at'
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'heading' => 'float',
        'registrado_em' => 'datetime',
    ];

    public function carga()
    {
        return $this->belongsTo(Carga::class);
    }

    public function motorista()
    {
        return $this->belongsTo(Motorista::class);
    }
}