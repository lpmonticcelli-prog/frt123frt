<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Arquitetura Cloud Native: O disco padrão em produção deve ser 
    | invariavelmente um Object Storage (S3/DO Spaces).
    |
    */

    'default' => env('FILESYSTEM_DISK', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        /*
        |--------------------------------------------------------------------------
        | Discos Locais (Estritamente para Ambiente de Desenvolvimento/Testes)
        |--------------------------------------------------------------------------
        | Em produção, estes discos JAMAIS devem armazenar estado durável.
        */
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'serve'  => true,
            'throw'  => env('APP_DEBUG', false),
            'report' => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => rtrim(env('APP_URL', 'http://localhost'), '/') . '/storage',
            'visibility' => 'public',
            'throw'      => env('APP_DEBUG', false),
            'report'     => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Cloud Native Storage (S3 / DigitalOcean Spaces)
        |--------------------------------------------------------------------------
        */

        // Disco para armazenamento geral em nuvem (PODs, Comprovantes).
        // Leitura apenas via Signed URLs geradas pela aplicação (visibilidade privada).
        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION'),
            'bucket'                  => env('AWS_BUCKET'),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility'              => 'private',
            'throw'                   => true, // Fails-fast obrigatório em Cloud
        ],

        // Disco para assets públicos na nuvem (ex: Avatares, Logos).
        's3_public' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION'),
            'bucket'                  => env('AWS_PUBLIC_BUCKET', env('AWS_BUCKET')),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility'              => 'public',
            'throw'                   => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | 🔒 ZERO TRUST: DARK ROOM (Cloud Vault)
        |--------------------------------------------------------------------------
        | Cofre criptográfico isolado em Object Storage.
        | Focado estritamente na custódia de PKI (Certificados A1) e KYC Forense.
        | O bucket deve possuir configuração de imutabilidade e retenção na AWS/DO.
        */
        'dark_room' => [
            'driver'                  => 's3',
            'key'                     => env('VAULT_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
            'secret'                  => env('VAULT_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'region'                  => env('VAULT_DEFAULT_REGION', env('AWS_DEFAULT_REGION')),
            'bucket'                  => env('VAULT_BUCKET'), // OBRIGATÓRIO: Bucket apartado do tráfego comum
            'url'                     => env('VAULT_URL'),
            'endpoint'                => env('VAULT_ENDPOINT', env('AWS_ENDPOINT')),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility'              => 'private',
            'throw'                   => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    | Mantido apenas para compatibilidade de rotinas legadas e ambiente dev.
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];