<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Events\CargaAtualizada;

class Carga extends Model
{
    use HasFactory, SoftDeletes;

    // ZT-DEFENSE: Higienização estrita de Payload. 
    // Atributos de telemetria/rastreio nativo foram expurgados para fechar vetor de ataque.
    protected $fillable = [
        'embarcador_id', 'motorista_id', 'fatura_id',
        'produto', 'especie', 'peso_kg', 'cubagem_m3', 'tipo_veiculo',      
        'tipo_carroceria', 'uf_origem', 'cidade_origem', 'uf_destino',        
        'cidade_destino', 'distancia_km', 'valor_frete', 'taxa_plataforma',   
        'status', 'foto_canhoto', 'foto_carga', 'data_coleta', 'data_entrega_prevista',
        'pedagio', // <--- MANTIDO
        'piso_antt' // <--- ADICIONADO: Permissão para salvar o piso congelado da ANTT
    ];

    protected function casts(): array
    {
        return [
            'data_coleta' => 'date',
            'data_entrega_prevista' => 'datetime',
            'peso_kg' => 'decimal:2',
            'cubagem_m3' => 'decimal:2',
            'distancia_km' => 'decimal:2',
            'valor_frete' => 'decimal:2',
            'taxa_plataforma' => 'decimal:2',
            'pedagio' => 'decimal:2', // <--- MANTIDO
            'piso_antt' => 'decimal:2', // <--- ADICIONADO: Tipagem correta para o banco
        ];
    }

    public function embarcador() 
    { 
        return $this->belongsTo(Embarcador::class); 
    }

    public function motorista() 
    { 
        return $this->belongsTo(Motorista::class); 
    }

    public function aceite_log() { return $this->hasOne(CargaAceiteLog::class, 'carga_id', 'id'); }
    public function publicacao_log() { return $this->hasOne(CargaPublicacaoLog::class, 'carga_id', 'id'); }

    // Hack: Relação "fantasma" para evitar Erro 500
    public function ciot() 
    { 
        return $this->hasOne(Carga::class, 'id', 'id')->where('id', '<', 0); 
    }

    public function aceitesLog() { return $this->hasMany(CargaAceiteLog::class, 'carga_id', 'id'); }
    public function publicacoesLog() { return $this->hasMany(CargaPublicacaoLog::class, 'carga_id', 'id'); }

    public function candidaturas() 
    { 
        return $this->hasMany(CargaCandidatura::class, 'carga_id', 'id'); 
    }

    public function avaliacao() 
    { 
        return $this->hasOne(Avaliacao::class, 'carga_id', 'id'); 
    }

    protected static function booted()
    {
        static::saved(function ($carga) {
            CargaAtualizada::dispatch($carga);
        });
    }
}