<?php

namespace App\Events;

use App\Models\Carga;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ShouldBroadcastNow: Garante que a atualização seja enviada instantaneamente
 * ignorando a fila de processamento para latência mínima.
 */
class CargaAtualizada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Carga $carga;

    public function __construct(Carga $carga)
    {
        // Eager Loading preventivo: Garante que o frontend receba os dados do dono da carga
        // sem disparar novas queries no servidor de WebSockets.
        $this->carga = $carga->loadMissing([
            'embarcador.user:id,name,email', 
            'motorista.user:id,name,email'
        ]);
    }

    /**
     * Canais de Transmissão:
     * 1. mural.fretes: Público para todos os motoristas logados.
     * 2. embarcador.{id}: Privado para a indústria que gerou a carga.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('mural.fretes'),
            new Channel('embarcador.' . $this->carga->embarcador_id)
        ];
    }

    /**
     * Nome do evento capturado pelo Laravel Echo no frontend.
     */
    public function broadcastAs(): string
    {
        return 'CargaAtualizada';
    }
}