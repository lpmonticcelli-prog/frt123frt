<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class ZeroTrustWaf
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) $request->ip();

        if (Redis::exists("waf:ban:{$ip}")) {
            abort(403, 'Acesso bloqueado pelo WAF. IP em quarentena.');
        }

        // ZT-DEFENSE: Mitigação estrita contra Memory Exhaustion (OOM) e ataques Volumétricos.
        // Intercepta requisições com payloads massivos antes do parsing do PHP/json_encode.
        $contentLength = (int) $request->header('Content-Length', '0');
        if ($contentLength > 10485760) { // Trava dura em 10MB
            Log::alert("[WAF BLOCK] Volume de Payload Anômalo Rejeitado na Borda.", ['ip' => $ip, 'size' => $contentLength]);
            abort(413, 'O volume transitado excede os limites de segurança.');
        }

        // Prevenção ReDoS: Regex rodado exclusivamente na URI e de forma leve.
        $uri = urldecode($request->getRequestUri());
        
        $patterns = [
            '/(?:<script.*?>.*?<\/script>)/is',
            '/(?:javascript:.*?;)/is',
            '/(?:\.\.\/|\.\.\\\\)/',
            '/(?:UNION\s+ALL\s+SELECT)/is',
            '/(?:NULL\s+BYTE|%00|\0)/i',
            '/(?:etc\/passwd)/i' // ZT-DEFENSE: LFI Block
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                Log::alert("[WAF BLOCK] Assinatura maliciosa interceptada na URI.", ['ip' => $ip, 'target' => $uri]);
                Redis::setex("waf:ban:{$ip}", 3600, 'banned'); 
                abort(403, 'Requisição interceptada pelo WAF.');
            }
        }

        // ZT-DEFENSE: Deep Inspection Iterativo.
        // Substitui json_encode($request->all()) para evitar OOM e ReDoS na CPU do Worker.
        $this->inspectPayload($request->all(), $patterns, $ip);

        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            $response->headers->set('Content-Security-Policy', "default-src 'self'; connect-src 'self' ws: wss: https: http:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https: blob:; script-src 'self' 'unsafe-inline' 'unsafe-eval';");
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->remove('X-Powered-By');
        }

        return $response;
    }

    private function inspectPayload(array $payload, array $patterns, string $ip): void
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $this->inspectPayload($value, $patterns, $ip);
            } elseif (is_string($value)) {
                // Trava clínica contra ReDoS (Ignora varredura em campos longos como Base64 ou JWT)
                if (strlen($value) > 10000) {
                    continue; 
                }
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        Log::alert("[WAF BLOCK] Mutação maliciosa em Payload.", ['ip' => $ip, 'key' => $key]);
                        Redis::setex("waf:ban:{$ip}", 3600, 'banned'); 
                        abort(403, 'Requisição interceptada pelo WAF.');
                    }
                }
            }
        }
    }
}