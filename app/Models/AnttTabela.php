<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnttTabela extends Model
{
    use HasFactory;

    protected $table = 'antt_tabelas';

    protected $fillable = [
        'tipo_carga', 
        'eixos', 
        'coeficiente_deslocamento_km', 
        'coeficiente_carga_descarga'
    ];

    // Escopo limpo para buscar a tarifa exata sem sujar o controller
    public function scopeBuscarTarifa($query, $tipoCarga, $eixos)
    {
        return $query->where('tipo_carga', $tipoCarga)
                     ->where('eixos', $eixos);
    }
}