<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avaliacao extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';

    protected $fillable = [
        'carga_id',
        'embarcador_id',
        'motorista_id',
        'nota_pontualidade',
        'nota_cuidado',
        'nota_comunicacao',
        'houve_avaria',
        'nota_final',
        'comentarios'
    ];

    protected $casts = [
        'houve_avaria' => 'boolean',
        'nota_final' => 'decimal:2',
    ];

    public function carga()
    {
        return $this->belongsTo(Carga::class);
    }

    public function embarcador()
    {
        return $this->belongsTo(Embarcador::class);
    }

    public function motorista()
    {
        return $this->belongsTo(Motorista::class);
    }
}