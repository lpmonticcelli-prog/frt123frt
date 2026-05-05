<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ciot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'idempotency_key',
        'carga_id',
        'embarcador_id',
        'motorista_id',
        'codigo_ciot',
        'status',
        'valor_frete_bruto',
        'imposto_inss',
        'imposto_sest_senat',
        'imposto_irrf',
        'valor_vale_pedagio',
        'taxa_123fretei',
        'valor_frete_liquido',
        'pef_payload_response',
        'webhook_payload',
    ];

    protected $casts = [
        'valor_frete_bruto' => 'decimal:2',
        'imposto_inss' => 'decimal:2',
        'imposto_sest_senat' => 'decimal:2',
        'imposto_irrf' => 'decimal:2',
        'valor_vale_pedagio' => 'decimal:2',
        'taxa_123fretei' => 'decimal:2',
        'valor_frete_liquido' => 'decimal:2',
        'pef_payload_response' => 'array',
        'webhook_payload' => 'array',
    ];

    public function carga() { return $this->belongsTo(Carga::class); }
    public function embarcador() { return $this->belongsTo(Embarcador::class); }
    public function motorista() { return $this->belongsTo(Motorista::class); }
}
