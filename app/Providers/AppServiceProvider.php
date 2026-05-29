<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // <-- INJETADO PARA O MOCK
use Illuminate\Support\Str; // <-- INJETADO PARA GERAÇÃO DE UUID

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
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost');
            $url = $frontendUrl . "/reset-password?token={$token}&email={$notifiable->getEmailForPasswordReset()}";

            return (new MailMessage)
                ->subject('Recuperação de Acesso - 123fretei')
                ->greeting('Olá, ' . $notifiable->name . '!') 
                ->line('Recebemos um pedido para redefinir a senha da sua conta no portal 123fretei.')
                ->action('Criar Nova Senha', $url)
                ->line('Este link de segurança expira em 60 minutos.')
                ->line('Se você não solicitou esta alteração, por favor ignore este e-mail. Sua conta continua protegida.')
                ->salutation('Equipe de Segurança, 123fretei');
        });

        // ==========================================
        // ZERO TRUST NETWORK: WAF & RATE LIMITING
        // ==========================================
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            // 1. Política Privilegiada (Engenharia / QA / Administração)
            if ($user && $user->role && in_array($user->role->slug, ['admin', 'engenharia'], true)) {
                return Limit::perMinute(300)->by((string) $user->id);
            }

            // 2. Política Base de Nuvem (Motoristas, Embarcadores e Visitantes)
            return Limit::perMinute(60)->by($user ? (string) $user->id : $request->ip());
        });

        // ==========================================
        // ZERO TRUST: MOCK DO HTTP CLIENT (Padrão Ouro)
        // ==========================================
        // Interceta as chamadas reais APENAS se o ambiente for "local" ou "testing".
        // Resolve o gargalo de rede na Memória RAM sem alterar o código de produção.
        if ($this->app->environment(['local', 'testing'])) {
            Http::fake([
                // Finge a resposta do Login Oficial
                '*auth.gr.app.br*' => Http::response([
                    'access_token' => 'mock_token_zero_trust_' . time(),
                    'expires_in' => 3600,
                    'token_type' => 'Bearer',
                ], 200),

                // Finge a resposta da Análise Oficial
                '*api.gr.app.br*' => Http::response([
                    'sucesso' => true,
                    'success' => true, // Para garantir compatibilidade com a verificação do Service
                    'mensagem' => 'MOCK INTERNO: Motorista analisado com sucesso!',
                    // ZT-DEFENSE: Geração de UUID válido para compatibilidade rigorosa com o PostgreSQL
                    'referencia' => (string) Str::uuid(), 
                ], 200),
            ]);
        }
    }
}