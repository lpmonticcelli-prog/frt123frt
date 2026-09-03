<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckTermosAceitos
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Se o usuário está logado mas NUNCA aceitou os termos (coluna NULL)
        if ($user && is_null($user->termo_aceite_em)) {
            
            // BLINDAGEM JURÍDICA: Registra a tentativa de acesso sem aceite
            Log::warning('[LEGAL SHIELD] Requisição barrada. Termos pendentes.', [
                'user_id' => $user->id, 
                'ip'      => $request->ip(),
                'url'     => $request->fullUrl()
            ]);

            return response()->json([
                'error'   => 'TERMOS_PENDENTES',
                'message' => 'Acesso bloqueado. Você precisa aceitar os novos Termos de Uso e Política de Privacidade para continuar utilizando a plataforma.'
            ], 403);
        }

        return $next($request);
    }
}