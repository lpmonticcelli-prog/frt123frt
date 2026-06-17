<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | ZT-DEFENSE: Otimização de I/O.
    | Alterado de 'database' para 'redis' como fallback padrão.
    | Em arquiteturas Cloud Native de alta concorrência, sessões em banco 
    | relacional geram gargalos catastróficos de I/O e lock contention.
    |
    */

    'driver' => env('SESSION_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', true), // ZT-DEFENSE: Sessões financeiras devem morrer ao fechar o browser.

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | ZT-DEFENSE: Payload encryption ativado estritamente. 
    | Impede inspeção e exfiltração de PII no Redis ou Banco de Dados (LGPD Art. 46).
    |
    */

    'encrypt' => true, 

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store
    |--------------------------------------------------------------------------
    */

    'store' => env('SESSION_STORE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery
    |--------------------------------------------------------------------------
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', '123fretei_secure')).'_sid'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | ZT-DEFENSE: HTTPS enforced (Anti-MitM).
    | Removida a tolerância de downgrade via .env. 
    | O cookie NUNCA trafegará via HTTP plano, independentemente do ambiente.
    |
    */

    'secure' => true,

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | ZT-DEFENSE: Bloqueio estrito de acesso XSS via DOM.
    | Hardcoded para TRUE. Scripts client-side (JS) não terão acesso à sessão.
    |
    */

    'http_only' => true,

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | ZT-DEFENSE: Defesa contra CSRF blindada.
    | Hardcoded para 'strict'. Impede categoricamente o vazamento de requisições 
    | cross-origin não intencionais na malha financeira.
    |
    */

    'same_site' => 'strict',

    /*
    |--------------------------------------------------------------------------
    | Partitioned Cookies
    |--------------------------------------------------------------------------
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Serialization
    |--------------------------------------------------------------------------
    |
    | ZT-DEFENSE: Restrito à JSON. Previne vulnerabilidades letais de 
    | PHP Object Injection e "gadget chains" de serialização (CWE-502).
    |
    */

    'serialization' => 'json',

];