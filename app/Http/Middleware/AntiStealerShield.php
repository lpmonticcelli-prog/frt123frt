<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AntiStealerShield
{
    /**
     * ZT-DEFENSE: Proteção contra Session Hijacking e Malware Stealers (Pass-the-Cookie).
     * Invalida sumariamente sessões que sofreram mutação de hardware ou saltos impossíveis de provedor.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $session = $request->session();
        $user = Auth::user();
        
        $userAgent = (string) ($request->userAgent() ?? 'unknown_agent');
        $currentIp = (string) $request->ip();

        // Extrai a Sub-rede (Ex: 177.45.12.3 -> 177.45). Tolera leves mudanças de Load Balancer, mas barra Hackers de outros países.
        $ipSubnet = str_contains($currentIp, ':') 
            ? implode(':', array_slice(explode(':', $currentIp), 0, 3)) 
            : implode('.', array_slice(explode('.', $currentIp), 0, 2)); 

        $currentUaHash = hash('sha256', $userAgent);
        $contextHash = hash_hmac('sha256', $ipSubnet . '|' . $userAgent, config('app.key'));

        // Assinatura imutável gravada no primeiro milissegundo de sessão
        if (!$session->has('_zt_session_fingerprint')) {
            $session->put('_zt_session_fingerprint', $contextHash);
            $session->put('_zt_ua_hash', $currentUaHash);
            return $next($request);
        }

        // 1. Verificação de Mutação de Navegador (Alguém copiou o cookie para outro browser)
        if (!hash_equals((string) $session->get('_zt_ua_hash'), $currentUaHash)) {
            $this->ejectSession($request, 'Hijack L7: Mutação de User-Agent detectada (Possível Pass-the-Cookie).');
        }

        // 2. Verificação de Salto de Sub-Rede (Exclusivo para Escritório/Admins)
        // Motoristas usam 4G e trocam de antena, por isso não avaliamos a rede deles, apenas o hardware (acima).
        $isMobileRole = $user->role && in_array($user->role->slug, ['motorista'], true);
        
        if (!$isMobileRole) {
            if (!hash_equals((string) $session->get('_zt_session_fingerprint'), $contextHash)) {
                $this->ejectSession($request, "Hijack L3: Salto Geográfico/Rede detectado. Assinatura matemática rejeitada.");
            }
        }

        return $next($request);
    }

    private function ejectSession(Request $request, string $motivo): void
    {
        Log::alert("[ANTI-STEALER] Sessão Ejetada. Violação de integridade do Client/Cookie roubado.", [
            'user_id' => Auth::id() ?? 'unknown',
            'ip_atacante' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'motivo' => $motivo
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        abort(401, 'Anomalia de contexto de rede ou dispositivo. Por razões de segurança corporativa contra roubo de contas, a sua sessão foi ejetada. Faça login novamente.');
    }
}