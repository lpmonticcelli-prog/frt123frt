<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        $isFallback = false;

        // Fallback: Hash determinístico do payload se o cliente não enviar a chave
        if (empty($idempotencyKey)) {
            // CORREÇÃO: Adicionado o verbo HTTP ($request->method()) à entropia do hash.
            // Impede colisões catastróficas caso o mesmo payload seja enviado via POST e PUT para a mesma URL.
            $idempotencyKey = hash('sha256', $request->method() . $request->url() . $request->getContent() . ($request->user()?->id ?? 'guest'));
            $isFallback = true;
        }

        $cacheKey = 'idempotency_res:' . $idempotencyKey;
        $lockKey  = 'idempotency_lock:' . $idempotencyKey;

        // OBRIGATÓRIO: Bypass de driver local, forçando Redis para clusterização
        $redis = Cache::store('redis');

        if ($redis->has($cacheKey)) {
            // ZT-DEFENSE: Visibilidade de auditoria. Registra tentativas de "Double-Spending" (re-play de pagamentos/saques).
            Log::info('[Idempotency] Requisição duplicada interceptada. Retornando resposta do cache.', [
                'key' => $idempotencyKey,
                'ip'  => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            
            $cachedResponse = $redis->get($cacheKey);
            return response($cachedResponse['content'], $cachedResponse['status'], $cachedResponse['headers']);
        }

        $lock = $redis->lock($lockKey, 15);

        if ($lock->get() === false) {
            // ZT-DEFENSE: Alerta de concorrência. Ajuda a mapear gargalos de rede ou scripts de força-bruta tentando burlar a trava.
            Log::warning('[Idempotency] Colisão de concorrência. Lock atômico já em uso.', [
                'key' => $idempotencyKey,
                'ip'  => $request->ip(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'error' => 'Transacao em andamento. Uma requisicao identica ja esta sendo processada.'
            ], Response::HTTP_CONFLICT);
        }

        try {
            if ($isFallback) {
                Log::debug('[Idempotency] Header Idempotency-Key ausente. Utilizando fallback hash.', ['key' => $idempotencyKey]);
            }

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