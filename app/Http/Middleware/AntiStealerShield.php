<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AntiStealerShield
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        // Chave única de cache para rastrear as tentativas do IP
        $cacheKey = "antt_stealer_shield_{$ip}";
        $hits = Cache::get($cacheKey, 0);

        // Limita a 25 simulações por minuto para barrar robôs
        if ($hits > 25) {
            Log::warning("[WAF] Bloqueio Anti-Scraping ANTT acionado para o IP: {$ip}");
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Detectamos tráfego anômalo. Cálculo de frete temporariamente bloqueado para sua segurança.'
            ], 429);
        }

        Cache::put($cacheKey, $hits + 1, now()->addMinute());

        return $next($request);
    }
}