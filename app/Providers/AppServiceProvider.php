<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage; // <== Importação mandatória adicionada

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mantém a otimização de assets do seu Laravel 11
        Vite::prefetch(concurrency: 3);

        // ==========================================
        // CUSTOMIZAÇÃO DO E-MAIL DE RECUPERAÇÃO DE SENHA
        // ==========================================
        // Intercepta o e-mail padrão do Laravel e constrói a mensagem da 123fretei
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            
            // Pega a URL do Vue no arquivo .env (ajustado sem a porta 5173)
            $frontendUrl = env('FRONTEND_URL', 'http://localhost');
            
            // Monta o link final que vai no botão do e-mail
            $url = $frontendUrl . "/reset-password?token={$token}&email={$notifiable->getEmailForPasswordReset()}";

            // Constrói o e-mail customizado e traduzido
            return (new MailMessage)
                ->subject('Recuperação de Acesso - 123fretei')
                ->greeting('Olá, ' . $notifiable->name . '!') 
                ->line('Recebemos um pedido para redefinir a senha da sua conta no portal 123fretei.')
                ->action('Criar Nova Senha', $url)
                ->line('Este link de segurança expira em 60 minutos.')
                ->line('Se você não solicitou esta alteração, por favor ignore este e-mail. Sua conta continua protegida.')
                ->salutation('Equipe de Segurança, 123fretei');
        });
    }
}