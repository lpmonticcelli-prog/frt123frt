<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Valida se o utilizador possui um dos slugs (cargos) permitidos para a rota.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // 1. Defesa primária: Autenticação e integridade do modelo
        if (!$user || !$user->role) {
            return response()->json([
                'message' => 'Acesso não autorizado. Autenticação ou perfil ausente.'
            ], 401);
        }

        // 2. Regra de Ouro (Root Bypass): Super Admin tem acesso a qualquer rota do painel
        if ($user->role->slug === 'admin') {
            return $next($request);
        }

        // 3. Validação múltipla: Verifica se o cargo atual está dentro dos permitidos na requisição
        if (!empty($roles) && !in_array($user->role->slug, $roles)) {
            return response()->json([
                'message' => 'Acesso negado. O seu nível de privilégio (' . strtoupper($user->role->slug) . ') não permite realizar esta operação.',
                'error_code' => 'INSUFFICIENT_PRIVILEGES'
            ], 403);
        }

        return $next($request);
    }
}