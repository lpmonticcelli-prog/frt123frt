<?php

declare(strict_types=1);

namespace App\Services\Security;

class BlindIndexService
{
    /**
     * Aplica derivação criptográfica One-Way (HMAC-SHA256).
     * Complexidade O(1) de busca SQL. Impenetrável para reversão, resolvendo
     * a falha arquitetural do Eloquent na busca de dados castados como AES-256-CBC.
     */
    public static function make(string $payload): string
    {
        $pepper = config('app.key');

        if (empty($pepper)) {
            abort(500, 'Security Exception: A chave criptográfica global (APP_KEY) não está provisionada no ambiente.');
        }
        
        // Padronização rigorosa garante que a assinatura matemática não mude devido a pontuações ou espaços da interface
        $cleanPayload = preg_replace('/[^a-zA-Z0-9]/', '', $payload);
        
        return hash_hmac('sha256', strtoupper($cleanPayload), $pepper);
    }
}