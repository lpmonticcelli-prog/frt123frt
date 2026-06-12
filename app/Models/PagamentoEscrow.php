<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagamentoEscrow extends Model
{
    use HasFactory;

    protected $table = 'pagamentos_escrow';

    protected $fillable = [
        'carga_id',
        'embarcador_id',
        'idempotency_key',
        'gateway_tx_id',
        'valor_total',
        'split_plataforma',
        'split_motorista',
        'qr_code_payload',
        'qr_code_url',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'valor_total' => 'decimal:2',
            'split_plataforma' => 'decimal:2',
            'split_motorista' => 'decimal:2',
        ];
    }

    public function carga(): BelongsTo
    {
        return $this->belongsTo(Carga::class);
    }

    public function embarcador(): BelongsTo
    {
        return $this->belongsTo(Embarcador::class);
    }
}