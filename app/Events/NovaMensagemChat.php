<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NovaMensagemChat implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public object $mensagem;
    public int $cargaId;

    public function __construct(object $mensagem, int $cargaId)
    {
        $this->mensagem = $mensagem;
        $this->cargaId = $cargaId;
    }

    public function broadcastOn(): PrivateChannel
    {
        // ZT-DEFENSE: Tranca a sala de chat. O Reverb agora exigirá autenticação do Sanctum
        // validada obrigatoriamente através do routes/channels.php.
        return new PrivateChannel('chat.' . $this->cargaId);
    }

    public function broadcastAs(): string
    {
        return 'NovaMensagem';
    }
}