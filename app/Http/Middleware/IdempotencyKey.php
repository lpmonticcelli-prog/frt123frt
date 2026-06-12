<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyKey
{
    /**
     * ZT-DEFENSE: Motor Atômico Anti-Duplicidade (Double-Spend Prevention).
     * Garante que uma requisição financeira crítica não seja executada duas vezes 
     * em caso de retry acidental do cliente ou instabilidade na conexão.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $key = $request->header('X-Idempotency-Key');
        
        if (empty($key)) {
            Log::warning('[Financeiro] Requisição rejeitada: X-Idempotency-Key ausente.', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Header obrigatório "X-Idempotency-Key" não detectado na camada de borda.'], 400);
        }

        if (strlen($key) > 128) {
            return response()->json(['error' => 'O comprimento do X-Idempotency-Key não pode exceder 128 caracteres.'], 400);
        }

        $user = $request->user();
        $prefix = $user ? "user:{$user->id}" : "ip:{$request->ip()}";
        $cacheKey = "idempotency:response:{$prefix}:{$key}";

        if (Cache::has($cacheKey)) {
            Log::info("[Financeiro] Idempotência ativada. Retornando payload salvo em cache para a key: {$key}");
            $cached = Cache::get($cacheKey);
            return response($cached['content'], $cached['status'], $cached['headers']);
        }

        $lockKey = "idempotency:lock:{$prefix}:{$key}";
        
        // ZT-DEFENSE: Lock Atômico Distribuído
        // Se 10 requests idênticos baterem no mesmo milissegundo, 9 são rejeitados imediatamente.
        $lock = Cache::lock($lockKey, 15); 
        
        if (!$lock->get()) {
            Log::alert("[Financeiro] Race Condition prevenida. Uma transação idêntica está em andamento.", ['ip' => $request->ip()]);
            return response()->json(['error' => 'Transação concorrente detectada. Esta ordem já encontra-se em processamento na fila financeira. Aguarde.'], 409);
        }

        $response = $next($request);

        // Apenas salva a resposta no cache se o status for de SUCESSO (2xx)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'status'  => $response->getStatusCode(),
                'headers' => array_merge($response->headers->all(), ['X-Idempotency-Replayed' => 'true'])
            ], now()->addHours(24));
        }

        $lock->release();

        return $response;
    }
}