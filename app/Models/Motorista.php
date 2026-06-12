<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\Security\BlindIndexService;

class Motorista extends Model
{
    use HasFactory;

    protected $table = 'motoristas';

    protected $fillable = [
        'user_id',
        'cpf',
        'cpf_bidx',
        'cnh',
        'cnh_bidx',
        'validade_cnh',
        'rntrc',
        'rntrc_bidx',
        'is_disponivel',
        'doc_cnh',                  
        'doc_selfie_cnh',           
        'doc_rntrc',                
        'doc_comprovante_endereco', 
        'status_verificacao',       
        'score_geral',
        'total_viagens',
        'tier_reputacao',
        'suspenso_ate',
        'gr_status',
        'gr_referencia',
        'gr_referencia_bidx',
        'gr_biometria_url'          
    ];

    protected $hidden = [
        'cpf',
        'cpf_bidx',
        'cnh',
        'cnh_bidx',
        'rntrc',
        'rntrc_bidx',
        'doc_cnh',
        'doc_selfie_cnh',
        'doc_rntrc',
        'doc_comprovante_endereco',
        'gr_referencia',
        'gr_referencia_bidx',
        'gr_biometria_url'
    ];

    protected function casts(): array
    {
        return [
            'validade_cnh' => 'date',
            'is_disponivel' => 'boolean',
            'score_geral' => 'decimal:2',
            'suspenso_ate' => 'datetime',
            'cpf' => 'encrypted',
            'cnh' => 'encrypted',
            'rntrc' => 'encrypted',
            'doc_cnh' => 'encrypted',
            'doc_selfie_cnh' => 'encrypted',
            'doc_rntrc' => 'encrypted',
            'doc_comprovante_endereco' => 'encrypted',
            'gr_referencia' => 'encrypted',
            'gr_biometria_url' => 'encrypted',
        ];
    }

    /**
     * Motor de Indexação Criptográfica Acionado Automaticamente.
     */
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->isDirty('cpf') && !empty($model->cpf)) {
                $model->cpf_bidx = BlindIndexService::make($model->cpf);
            }
            if ($model->isDirty('cnh') && !empty($model->cnh)) {
                $model->cnh_bidx = BlindIndexService::make($model->cnh);
            }
            if ($model->isDirty('rntrc') && !empty($model->rntrc)) {
                $model->rntrc_bidx = BlindIndexService::make($model->rntrc);
            }
            if ($model->isDirty('gr_referencia') && !empty($model->gr_referencia)) {
                $model->gr_referencia_bidx = BlindIndexService::make($model->gr_referencia);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cargas()
    {
        return $this->hasMany(Carga::class);
    }

    public function candidaturas()
    {
        return $this->hasMany(CargaCandidatura::class);
    }

    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class);
    }

    public function isAprovadoGr(): bool
    {
        return $this->gr_status === 'aprovado';
    }

    public function aguardaBiometriaGr(): bool
    {
        return $this->gr_status === 'aguardando_biometria';
    }

    public function podeAceitarFrete(): bool
    {
        $semSuspensao = is_null($this->suspenso_ate) || $this->suspenso_ate->isPast();
        $kycAprovado = $this->status_verificacao === 'aprovado';
        
        return $kycAprovado && $this->isAprovadoGr() && $semSuspensao;
    }
}