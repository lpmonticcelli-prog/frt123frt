<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    
    // ZT-DEFENSE: Apenas os domínios declarados no seu .env de produção terão acesso
    // Ex: CORS_ALLOWED_ORIGINS=https://123fretei.com.br,https://app.123fretei.com.br
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:8000')),
    
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];