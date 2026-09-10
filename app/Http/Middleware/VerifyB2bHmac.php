<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyB2bHmac
{
    /**
     * Valida a integridade e autenticidade dos webhooks B2B via HMAC-SHA256.
     */
    public function handle(Request $request, Closure $next, ?string $provider = null)
    {
        $provider = $provider ?? $this->resolveProvider($request);

        // Fail-safe: Se a rota não for um webhook mapeado e o middleware for aplicado acidentalmente,
        // permite o fluxo para não quebrar o estado atual de rotas legadas.
        if (!$provider) {
            return $next($request);
        }

        $secret = config("{$provider}.webhook_secret");

        if (empty($secret)) {
            Log::emergency("[B2B Shield] Segredo HMAC não configurado para o provedor: {$provider}");
            abort(500, 'Configuração criptográfica B2B ausente.');
        }

        // Tenta capturar as chaves de assinatura mais comuns utilizadas por gateways e APIs logísticas
        $signature = $request->header('X-Webhook-Signature') 
                  ?? $request->header('X-Signature') 
                  ?? $request->header('X-Hub-Signature');

        if (empty($signature)) {
            Log::warning("[B2B Shield] Requisição sem assinatura criptográfica.", [
                'ip' => $request->ip(),
                'provider' => $provider
            ]);
            abort(401, 'Assinatura de segurança ausente.');
        }

        // Limpa prefixos comuns como "sha256=" enviados por alguns provedores
        $signatureHash = str_replace('sha256=', '', $signature);
        
        $computedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        // Prevenção estrita contra Timing Attacks
        if (!hash_equals($computedSignature, $signatureHash)) {
            Log::alert("[B2B Shield] Tentativa de intrusão B2B interceptada. Assinatura inválida.", [
                'ip' => $request->ip(),
                'provider' => $provider,
                'payload' => $request->all()
            ]);
            abort(403, 'A assinatura do payload foi rejeitada pela malha de segurança.');
        }

        return $next($request);
    }

    /**
     * Mapeia dinamicamente o serviço correto baseado no nome da rota,
     * garantindo compatibilidade sem precisar alterar os arquivos de rotas.
     */
    private function resolveProvider(Request $request): ?string
    {
        $routeName = $request->route() ? $request->route()->getName() : '';

        return match ($routeName) {
            'webhook.pef'     => 'pef',
            'webhook.gateway' => 'gateway',
            'webhook.iza'     => 'iza',
            'webhook.transat' => 'transat', // Assumindo chave 'transat' no arquivo de config
            default           => null,
        };
    }
}