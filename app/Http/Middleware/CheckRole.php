<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Valida se o utilizador possui um dos slugs (cargos) permitidos para a rota.
     * Operação sob diretriz Zero Trust: Negação por padrão e Auditoria Contínua.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // 1. Defesa primária: Autenticação e integridade do modelo
        if (!$user || !$user->role) {
            
            // Auditoria de Segurança: Tentativa de acesso anônimo/corrompido em rota protegida
            Log::warning('Security Audit (CheckRole): Tentativa de acesso rejeitada (401).', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'user_id' => $user->id ?? 'anonymous'
            ]);

            return response()->json([
                'message' => 'Acesso não autorizado. Autenticação ou perfil ausente.'
            ], 401);
        }

        // 2. Regra de Ouro (Root Bypass): Super Admin tem acesso a qualquer rota do painel
        if ($user->role->slug === 'admin') {
            return $next($request);
        }

        // 3. Validação múltipla estrita: Verifica se o cargo atual está dentro dos permitidos na requisição
        if (!empty($roles) && !in_array($user->role->slug, $roles, true)) {
            
            // Auditoria Zero Trust: Registro de tentativa de escalonamento de privilégios (Possível IDOR)
            Log::alert('Security Audit (CheckRole): Tentativa de violação de privilégios (403 Forbidden).', [
                'ip' => $request->ip(),
                'user_id' => $user->id,
                'email' => $user->email,
                'role_atual' => $user->role->slug,
                'roles_exigidas' => $roles,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'message' => 'Acesso negado. O seu nível de privilégio (' . strtoupper($user->role->slug) . ') não permite realizar esta operação.',
                'error_code' => 'INSUFFICIENT_PRIVILEGES'
            ], 403);
        }

        return $next($request);
    }
}