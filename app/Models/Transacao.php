<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transacao extends Model
{
    protected $table = 'transacoes';
    protected $fillable = ['motorista_id', 'carga_id', 'tipo', 'valor', 'descricao'];

    public function motorista() { return $this->belongsTo(Motorista::class); }
    public function carga() { return $this->belongsTo(Carga::class); }
}