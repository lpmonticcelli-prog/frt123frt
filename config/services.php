<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
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

    // INJEÇÃO DA ARQUITETURA DE PAGAMENTO E CIOT (123FRETEI)
    'pef' => [
        'driver' => env('PEF_DRIVER', 'mock'),
    ],

    // ==========================================
    // INTEGRAÇÃO DE RISCO: TRANSAT (GR)
    // ==========================================
    // ZT-DEFENSE: Mapeamento de variáveis para permitir desvio seguro para Mock Server local
    'transat' => [
        'base_url' => env('TRANSAT_BASE_URL'),
        'auth_url' => env('TRANSAT_AUTH_URL'),
        'username' => env('TRANSAT_USERNAME'),
        'password' => env('TRANSAT_PASSWORD'),
        'cliente_id' => env('TRANSAT_CLIENTE_ID'),
        'empresa_id' => env('TRANSAT_EMPRESA_ID'),
        'webhook_secret' => env('TRANSAT_WEBHOOK_SECRET'),
    ],

];