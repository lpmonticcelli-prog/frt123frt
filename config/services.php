<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ==========================================
    // LOGÍSTICA: PEF / CIOT (Integração ANTT)
    // ==========================================
    'pef' => [
        'driver' => env('PEF_DRIVER', 'mock'),
        // ZT-DEFENSE: Mapeamento estrito da chave HMAC. Fail-secure se ausente.
        'webhook_secret' => env('PEF_WEBHOOK_SECRET') ?: throw new \RuntimeException('Segredo Webhook PEF ausente no .env.'),
    ],

    // ==========================================
    // RISCO: FEATURE FLAG (Substitui o microsserviço legado)
    // ==========================================
    'gr' => [
        'enabled' => env('FEATURE_GR_ENABLED', true),
    ],

    // ==========================================
    // FINANCEIRO: GATEWAY (Split e Escrow)
    // ==========================================
    'gateway' => [
        'base_url' => env('GATEWAY_BASE_URL'),
        'api_key' => env('GATEWAY_API_KEY'),
        // ZT-DEFENSE: Fail-secure obrigatório.
        'webhook_secret' => env('GATEWAY_WEBHOOK_SECRET') ?: throw new \RuntimeException('Segredo Webhook Gateway Financeiro ausente no .env.'),
        'split_receiver_id' => env('GATEWAY_SPLIT_RECEIVER_ID'),
    ],

];