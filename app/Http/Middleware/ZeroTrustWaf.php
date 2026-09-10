<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZeroTrustWaf
{
    /**
     * Padrões maliciosos (Blacklist baseada em assinaturas).
     * Focado em não gerar falsos positivos para tráfego logístico legítimo.
     */
    private array $maliciousPatterns = [
        '/(?:\<script.*?\>|\<\/script\>)/i',           // Cross-Site Scripting (XSS)
        '/(?:javascript|vbscript):/i',                 // URIs maliciosas
        '/(\.\.\/|\.\.\\\\)/',                         // Directory Traversal (LFI/RFI)
        '/(?:UNION\s+SELECT|DROP\s+TABLE|EXEC\s*\()/i' // Injeções SQL óbvias
    ];

    public function handle(Request $request, Closure $next)
    {
        $payload = $request->all();

        if ($this->detectAnomaly($payload)) {
            // ZT-DEFENSE: Ofuscação de credenciais no log para evitar vazamento em texto plano
            $safePayload = $request->except(['password', 'password_confirmation', 'senha', 'pin']);

            Log::alert('[WAF] Intrusão bloqueada na borda.', [
                'ip'      => $request->ip(),
                'method'  => $request->method(),
                'url'     => $request->fullUrl(),
                'payload' => $safePayload
            ]);
            
            abort(403, 'Requisição interceptada pela malha de segurança Zero Trust (WAF).');
        }

        return $next($request);
    }

    /**
     * Varre recursivamente o payload em busca de assinaturas maliciosas.
     */
    private function detectAnomaly(mixed $inputs): bool
    {
        if (is_array($inputs)) {
            foreach ($inputs as $input) {
                if ($this->detectAnomaly($input)) {
                    return true;
                }
            }
        } elseif (is_string($inputs)) {
            foreach ($this->maliciousPatterns as $pattern) {
                if (preg_match($pattern, $inputs)) {
                    return true;
                }
            }
        }

        return false;
    }
}