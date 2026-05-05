<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Carga extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'embarcador_id',
        'motorista_id',
        'produto',
        'especie',
        'peso_kg',           
        'cubagem_m3',        
        'tipo_veiculo',      
        'tipo_carroceria',   
        'uf_origem',         
        'cidade_origem',     
        'uf_destino',        
        'cidade_destino',    
        'distancia_km',      
        'valor_frete',
        'taxa_plataforma',   
        'status',
        'foto_canhoto',      
        'foto_carga',        
        'data_coleta',       
        'data_entrega_prevista',
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

    public function embarcador()
    {
        return $this->belongsTo(Embarcador::class);
    }

    public function motorista()
    {
        return $this->belongsTo(Motorista::class);
    }

    /**
     * Relacionamento com a Auditoria Jurídica (Recibo Eletrônico do Motorista)
     * Renomeado para snake_case para dar "Match" perfeito com o Frontend Vue.js
     */
    public function aceite_log()
    {
        return $this->hasOne(CargaAceiteLog::class, 'carga_id', 'id');
    }

    /**
     * Relacionamento com a Auditoria Jurídica (Recibo Eletrônico do Embarcador)
     * Renomeado para snake_case para dar "Match" perfeito com o Frontend Vue.js
     */
    public function publicacao_log()
    {
        return $this->hasOne(CargaPublicacaoLog::class, 'carga_id', 'id');
    }
}