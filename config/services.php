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
    // LOGÍSTICA: PAMCARD/REPOM (CIOT)
    // ==========================================
    'pef' => [
        'driver' => env('PEF_DRIVER', 'mock'),
    ],

    // ==========================================
    // RISCO: TRANSAT (GR e Biometria)
    // ==========================================
    'transat' => [
        'base_url' => env('TRANSAT_BASE_URL'),
        'auth_url' => env('TRANSAT_AUTH_URL'),
        'username' => env('TRANSAT_USERNAME'),
        'password' => env('TRANSAT_PASSWORD'),
        'cliente_id' => env('TRANSAT_CLIENTE_ID'),
        'empresa_id' => env('TRANSAT_EMPRESA_ID'),
        'webhook_secret' => env('TRANSAT_WEBHOOK_SECRET'),
    ],

    // ==========================================
    // FINANCEIRO: IUGU / STARKBANK (Split e Escrow)
    // ==========================================
    'gateway' => [
        'base_url' => env('GATEWAY_BASE_URL'),
        'api_key' => env('GATEWAY_API_KEY'),
        'webhook_secret' => env('GATEWAY_WEBHOOK_SECRET', 'mock_gateway_secret_zero_trust'),
        'split_receiver_id' => env('GATEWAY_SPLIT_RECEIVER_ID'), // A conta bancária da 123fretei
    ],

];