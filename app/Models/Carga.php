<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Carga extends Model
{
    use HasFactory, SoftDeletes;

    // CIRURGIA: fatura_id liberado para o Mass Assignment
    protected $fillable = [
        'embarcador_id', 'motorista_id', 'fatura_id',
        'produto', 'especie', 'peso_kg', 'cubagem_m3', 'tipo_veiculo',      
        'tipo_carroceria', 'uf_origem', 'cidade_origem', 'uf_destino',        
        'cidade_destino', 'distancia_km', 'valor_frete', 'taxa_plataforma',   
        'status', 'foto_canhoto', 'foto_carga', 'data_coleta', 'data_entrega_prevista',
    ];

    protected $casts = [
        'data_coleta' => 'date',
        'data_entrega_prevista' => 'datetime',
        'peso_kg' => 'decimal:2',
        'cubagem_m3' => 'decimal:2',
        'distancia_km' => 'decimal:2',
        'valor_frete' => 'decimal:2',
        'taxa_plataforma' => 'decimal:2',
    ];

    public function embarcador() { return $this->belongsTo(Embarcador::class); }
    public function motorista() { return $this->belongsTo(Motorista::class); }
    public function aceite_log() { return $this->hasOne(CargaAceiteLog::class, 'carga_id', 'id'); }
    public function publicacao_log() { return $this->hasOne(CargaPublicacaoLog::class, 'carga_id', 'id'); }
    
    // CIRURGIA: Relacionamento de Pagamento criado para evitar Fatal Error
    public function ciot() { return $this->hasOne(Ciot::class, 'carga_id', 'id'); }
}