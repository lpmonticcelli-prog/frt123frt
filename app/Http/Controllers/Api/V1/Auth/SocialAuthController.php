<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    /**
     * Redireciona o usuário para a tela de login do Google.
     */
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Recebe o retorno do Google com os dados do usuário.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Procura se o e-mail já existe na base
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Usuário 100% novo (Primeiro Acesso)
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => null, // O Google gerencia a senha
                    'status' => 'active',
                ]);
            } else {
                // Usuário já existia (atualiza o ID do Google e a foto)
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            }

            // Injeta o Cookie de Sessão Segura (Sanctum) no navegador
            Auth::login($user, true);

            // Redireciona de volta para a tela inicial do seu site. 
            // Como o Cookie já está no navegador, o Vue.js vai logar ele automaticamente!
            return redirect(env('FRONTEND_URL', 'https://beta.123fretei.com.br'));

        } catch (\Exception $e) {
            Log::error('Falha no Login do Google', ['erro' => $e->getMessage()]);
            return redirect(env('FRONTEND_URL', 'https://beta.123fretei.com.br') . '/login?error=google_falhou');
        }
    }
}