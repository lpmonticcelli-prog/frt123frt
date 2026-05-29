<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // =========================================================
            // 🔒 ZERO TRUST: Mocks Dinâmicos e Rate Limit Contextual
            // =========================================================
            if (app()->environment('local', 'testing')) {
                // Em dev: Carrega a API LIVRE de Rate Limit e Injeta os Mocks
                Route::middleware('api')
                    ->prefix('api')
                    ->group(base_path('routes/api.php'));

                Route::middleware('api')
                    ->prefix('api')
                    ->group(base_path('routes/mock.php'));
            } else {
                // Em Produção: Carrega a API COM PROTEÇÃO MÁXIMA (throttle:api)
                Route::middleware(['api', 'throttle:api'])
                    ->prefix('api')
                    ->group(base_path('routes/api.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // ==========================================
        // ZERO TRUST: CONFIANÇA DE PROXY (WAF/ELB)
        // ==========================================
        // Garante que o Rate Limiting (Throttle) bloqueie o IP REAL do atacante,
        // e não o IP do Cloudflare, AWS Load Balancer ou Nginx.
        $middleware->trustProxies(at: '*'); 

        // ==========================================
        // SANCTUM: HABILITA SESSÃO/COOKIES NA API
        // ==========================================
        $middleware->statefulApi();

        // ==========================================
        // REGISTRO DE MIDDLEWARES CUSTOMIZADOS
        // ==========================================
        $middleware->alias([
            // Controle de Acesso Base (Role Baseado em Tabela Própria)
            'role' => \App\Http\Middleware\CheckRole::class,
            
            // Controle de Acesso Sanctum (Token Abilities) - Restauração
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Tratamento de exceções customizadas
    })->create();