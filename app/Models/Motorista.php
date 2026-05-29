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
        'status_verificacao',       // KYC: Status da análise interna
        'score_geral',
        'total_viagens',
        'tier_reputacao',
        'suspenso_ate',
        // ZT-DEFENSE: Colunas da Gerenciadora de Risco (Trans Sat)
        'gr_status',
        'gr_referencia',
        'gr_biometria_url'          // <-- ADICIONADO: URL dinâmica para o QR Code da Biometria Facial
    ];

    protected $casts = [
        'validade_cnh' => 'date',
        'is_disponivel' => 'boolean',
        'score_geral' => 'decimal:2',
        'suspenso_ate' => 'datetime',
    ];

    /**
     * Relacionamento com a tabela de usuários
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com as Cargas
     */
    public function cargas()
    {
        return $this->hasMany(Carga::class);
    }

    /**
     * Relacionamentos de Marketplace e Reputação
     */
    public function candidaturas()
    {
        return $this->hasMany(CargaCandidatura::class);
    }

    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class);
    }

    // =========================================================
    // REGRAS DE NEGÓCIO: GERENCIADORA DE RISCO (ZERO TRUST)
    // =========================================================
    
    /**
     * Verifica se o motorista está com status liberado pela GR.
     */
    public function isAprovadoGr(): bool
    {
        return $this->gr_status === 'aprovado';
    }

    /**
     * Verifica se o motorista está pendente de biometria facial na GR.
     */
    public function aguardaBiometriaGr(): bool
    {
        return $this->gr_status === 'aguardando_biometria';
    }

    /**
     * Regra de bloqueio absoluto para candidaturas.
     * O motorista só pode se candidatar se:
     * 1. O KYC interno estiver aprovado.
     * 2. A Gerenciadora de Risco (Trans Sat) estiver como 'aprovado'.
     * 3. Não estiver cumprindo suspensão disciplinar.
     */
    public function podeAceitarFrete(): bool
    {
        $semSuspensao = is_null($this->suspenso_ate) || $this->suspenso_ate->isPast();
        $kycAprovado = $this->status_verificacao === 'aprovado';
        
        return $kycAprovado && $this->isAprovadoGr() && $semSuspensao;
    }
}