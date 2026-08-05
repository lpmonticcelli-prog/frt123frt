<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyB2bHmac
{
    public function handle(Request $request, Closure $next, ?string $secretType = 'gateway'): Response
    {
        $secretKeyName = strtoupper($secretType) . '_WEBHOOK_SECRET';
        $secret = env($secretKeyName);

        if (!$secret) {
            Log::critical("[WAF] Chave HMAC ausente no ambiente: {$secretType}.");
            return response()->json(['error' => 'Internal Configuration Error'], 500);
        }

        $signature = $request->header('X-Gateway-Signature') ?? $request->header('X-Pef-Signature');

        if ($signature !== $secret) {
            Log::alert("[WAF] Assinatura HMAC invalida interceptada.");
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
