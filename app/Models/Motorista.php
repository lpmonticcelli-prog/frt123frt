<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motorista extends Model
{
    use HasFactory;

    // Força o nome da tabela em português
    protected $table = 'motoristas';

    protected $fillable = [
        'user_id',
        'cpf',
        'cnh',
        'validade_cnh',
        'rntrc',
        'is_disponivel',
        'doc_cnh',                  // KYC: Caminho da imagem da CNH
        'doc_selfie_cnh',           // KYC: Nova Prova de Vida (Selfie + CNH)
        'doc_rntrc',                // KYC: Caminho do documento RNTRC
        'doc_comprovante_endereco', // KYC: Caminho do comprovante de endereço
        'status_verificacao',       // KYC: Status da análise (pendente, em_analise, aprovado, rejeitado)
    ];

    // Casts garantem que o Laravel converta os dados para os tipos corretos na API
    protected $casts = [
        'validade_cnh' => 'date',
        'is_disponivel' => 'boolean',
    ];

    /**
     * Relacionamento com a tabela de usuários (Autenticação e Dados Básicos)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento Estrutural: 
     * Um motorista possui/realiza VÁRIAS cargas (histórico e frete atual).
     */
    public function cargas()
    {
        return $this->hasMany(Carga::class);
    }
}