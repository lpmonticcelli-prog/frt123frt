<?php

namespace App\Events;

use App\Models\Carga;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CargaAtualizada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Carga $carga;

    public function __construct(Carga $carga)
    {
        $this->carga = $carga->loadMissing([
            'embarcador.user:id,name,email', 
            'motorista.user:id,name,email'
        ]);
    }

    public function broadcastOn(): array
    {
        $canais = [
            new Channel('mural.fretes'),
            new Channel('embarcador.' . $this->carga->embarcador_id)
        ];

        // CORREÇÃO: Se a carga tiver um motorista atrelado (aprovado no Bidding),
        // ele ganha um canal dedicado para ver a carga se mover no ecrã "Meus Fretes"
        if ($this->carga->motorista_id) {
            $canais[] = new Channel('motorista.' . $this->carga->motorista_id);
        }

        return $canais;
    }

    public function broadcastAs(): string
    {
        return 'CargaAtualizada';
    }
}