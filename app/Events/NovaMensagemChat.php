<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NovaMensagemChat implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mensagem;
    public $cargaId;

    public function __construct($mensagem, $cargaId)
    {
        $this->mensagem = $mensagem;
        $this->cargaId = $cargaId;
    }

    public function broadcastOn()
    {
        // Cria uma sala de chat exclusiva para o ID desta carga
        return new Channel('chat.' . $this->cargaId);
    }

    public function broadcastAs()
    {
        return 'NovaMensagem';
    }
}