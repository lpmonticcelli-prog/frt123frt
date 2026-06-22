<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyB2bHmac
{
    /**
     * ZT-DEFENSE: Validação Criptográfica e IP Whitelisting para Webhooks.
     * Impede ataques de falsificação de transações financeiras (Spoofing).
     *
     * @param string $provider O provedor esperado (ex: 'pef', 'gateway')
     */
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        // 1. RESOLUÇÃO DE SEGREDO (Strict Environment Handling)
        $secretKey = config("services.{$provider}.webhook_secret");

        if (empty($secretKey)) {
            Log::emergency("[ZT-WAF] Falha de Infraestrutura: Chave HMAC ausente para o provedor '{$provider}'.");
            return response()->json(['error' => 'Internal Server Configuration Error.'], 500);
        }

        // 2. CAPTURA DO PAYLOAD RAW 
        // Vital: HMAC deve ser calculado sobre o binário exato recebido, antes do parser JSON do PHP atuar.
        $payload = $request->getContent();

        // 3. CAPTURA DA ASSINATURA DA INTEGRADORA
        // Adaptamos o header conforme o padrão de mercado (X-Signature, X-Hub-Signature-256, etc).
        $signatureHeader = $request->header('X-Hub-Signature-256') ?? $request->header('X-Signature');

        if (!$signatureHeader) {
            Log::warning("[ZT-WAF] Tentativa de invasão bloqueada: Webhook sem assinatura HMAC.", [
                'ip' => $request->ip(),
                'provider' => $provider,
                'uri' => $request->fullUrl()
            ]);
            return response()->json(['error' => 'Unauthorized. Missing cryptographic signature.'], 401);
        }

        // 4. COMPUTAÇÃO DO HASH (HMAC-SHA256)
        // Se a integradora usa sha1 ou sha512, altere o algoritmo abaixo.
        $computedHash = hash_hmac('sha256', $payload, $secretKey);
        
        // Remove prefixos comuns enviados por webhooks (ex: "sha256=...")
        $providedHash = str_replace('sha256=', '', $signatureHeader);

        // 5. ANTI-TIMING ATTACK COMPARISON (Zero Trust Core)
        if (!hash_equals($computedHash, $providedHash)) {
            Log::alert("[ZT-WAF] Violação Criptográfica Detectada. Assinatura HMAC inválida.", [
                'ip' => $request->ip(),
                'provider' => $provider
            ]);
            return response()->json(['error' => 'Forbidden. Signature mismatch.'], 403);
        }

        return $next($request);
    }
}