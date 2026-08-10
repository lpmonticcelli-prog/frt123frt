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
        'coeficiente_deslocamento',
        'coeficiente_carga_descarga',
    ];
}