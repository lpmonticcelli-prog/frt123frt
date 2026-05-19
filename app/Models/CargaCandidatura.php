<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargaCandidatura extends Model
{
    use HasFactory;

    protected $table = 'carga_candidaturas';

    protected $fillable = [
        'carga_id',
        'motorista_id',
        'status',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
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