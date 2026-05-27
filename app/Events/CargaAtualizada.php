<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Carga;
use Illuminate\Broadcasting\PrivateChannel;
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
        // ZT-DEFENSE: Projeção de Dados Estrita.
        // Omissão cirúrgica do campo "email" na consulta de relacionamento para 
        // bloquear o vazamento de PII na transmissão do WebSocket.
        $this->carga = $carga->loadMissing([
            'embarcador.user:id,name', 
            'motorista.user:id,name'
        ]);
    }

    public function broadcastOn(): array
    {
        // ZT-DEFENSE: Fechamento de perímetro.
        // Substituição do Channel público pelo PrivateChannel, forçando a autorização
        // pelo middleware Sanctum antes da liberação do tráfego TCP/Websocket.
        $canais = [
            new PrivateChannel('mural.fretes'),
            new PrivateChannel('embarcador.' . $this->carga->embarcador_id)
        ];

        // Se a carga tiver um motorista atrelado (aprovado no Bidding),
        // ele ganha um canal dedicado para ver a carga se mover na tela "Meus Fretes"
        if ($this->carga->motorista_id) {
            $canais[] = new PrivateChannel('motorista.' . $this->carga->motorista_id);
        }

        return $canais;
    }

    public function broadcastAs(): string
    {
        return 'CargaAtualizada';
    }
}