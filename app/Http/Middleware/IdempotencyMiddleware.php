<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Tratamento de Idempotência via Redis para APIs B2B Críticas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ignora verbos seguros (REST)
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        // Fallback: Hash determinístico do payload se o cliente não enviar a chave
        if (empty($idempotencyKey)) {
            $idempotencyKey = hash('sha256', $request->url() . $request->getContent() . ($request->user()?->id ?? 'guest'));
        }

        $cacheKey = 'idempotency_res:' . $idempotencyKey;
        $lockKey  = 'idempotency_lock:' . $idempotencyKey;

        // OBRIGATÓRIO: Bypass de driver local, forçando Redis para clusterização
        $redis = Cache::store('redis');

        if ($redis->has($cacheKey)) {
            $cachedResponse = $redis->get($cacheKey);
            return response($cachedResponse['content'], $cachedResponse['status'], $cachedResponse['headers']);
        }

        $lock = $redis->lock($lockKey, 15);

        if ($lock->get() === false) {
            return response()->json([
                'error' => 'Transacao em andamento. Uma requisicao identica ja esta sendo processada.'
            ], Response::HTTP_CONFLICT);
        }

        try {
            /** @var Response $response */
            $response = $next($request);

            if ($response->isSuccessful()) {
                $redis->put($cacheKey, [
                    'content' => $response->getContent(),
                    'status'  => $response->getStatusCode(),
                    'headers' => $response->headers->all(),
                ], now()->addHours(24));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}