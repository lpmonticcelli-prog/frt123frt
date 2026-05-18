<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class B2bCredentialMail extends Mailable implements ShouldQueue // ShouldQueue envia em background para não travar a tela
{
    use Queueable, SerializesModels;

    public $nomeParceiro;
    public $token;
    public $scope;

    public function __construct($nomeParceiro, $token, $scope)
    {
        $this->nomeParceiro = $nomeParceiro;
        $this->token = $token;
        $this->scope = $scope;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Credenciais de Produção (API B2B) - 123fretei',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.b2b-credentials',
        );
    }
}