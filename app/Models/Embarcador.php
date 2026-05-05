<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Embarcador extends Model
{
    use SoftDeletes;

    // Força o nome da tabela em português para bater com a Migration
    protected $table = 'embarcadores'; 

    protected $fillable = [
        'user_id',
        'razao_social',
        'cnpj',
        'inscricao_estadual'
    ];

    /**
     * Relacionamento com a tabela de usuários (Autenticação e Dados de Contato)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento Estrutural: 
     * Um embarcador possui VÁRIAS cargas.
     */
    public function cargas()
    {
        return $this->hasMany(Carga::class);
    }
}