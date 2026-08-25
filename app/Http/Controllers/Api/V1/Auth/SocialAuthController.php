<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Trazemos o usuário com a 'role' para montar a permissão do Token corretamente
            $user = User::with('role')->where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => null, 
                    'status' => 'pending',
                ]);
            } else {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            }

            // GERAÇÃO DO TOKEN SANCTUM
            $ability = $user->role ? "ability:{$user->role->slug}" : 'ability:none';
            $deviceName = 'google_oauth_device';
            
            // Limpa tokens antigos do Google para este usuário
            $user->tokens()->where('name', $deviceName)->delete();
            $token = $user->createToken($deviceName, [$ability], now()->addHours(12))->plainTextToken;

            // REDIRECIONA PARA O LOGIN DO VUE PASSANDO O TOKEN NA URL
            $frontendUrl = env('FRONTEND_URL', 'https://beta.123fretei.com.br');
            return redirect("{$frontendUrl}/login?token={$token}");

        } catch (\Exception $e) {
            Log::error('Falha no Login do Google', ['erro' => $e->getMessage()]);
            return redirect(env('FRONTEND_URL', 'https://beta.123fretei.com.br') . '/login?error=google_falhou');
        }
    }
}