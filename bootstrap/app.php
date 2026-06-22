<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            if (app()->environment('local', 'testing')) {
                Route::middleware('api')->prefix('api')->group(base_path('routes/api.php'));
                Route::middleware('api')->prefix('api')->group(base_path('routes/mock.php'));
            } else {
                // ZT-DEFENSE: Throttle global restrito a 60 requests por minuto por IP em produção.
                // Limita a eficácia de DDoS L7 de baixo volume e raspagem de dados (Scraping).
                Route::middleware(['api', 'throttle:60,1'])->prefix('api')->group(base_path('routes/api.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: [
            '127.0.0.1',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]); 

        $middleware->trustProxies(headers: 
            Request::HEADER_X_FORWARDED_FOR | 
            Request::HEADER_X_FORWARDED_HOST | 
            Request::HEADER_X_FORWARDED_PORT | 
            Request::HEADER_X_FORWARDED_PROTO | 
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->statefulApi();

        // 1ª Camada: O WAF inspeciona o Payload e a URI contra injeções SQL/XSS
        $middleware->append(\App\Http\Middleware\ZeroTrustWaf::class);

        // 2ª Camada: O Escudo Anti-Stealer inspeciona globalmente a integridade física
        $middleware->appendToGroup('web', \App\Http\Middleware\AntiStealerShield::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\AntiStealerShield::class);

        $middleware->alias([
            'role'        => \App\Http\Middleware\CheckRole::class,
            'abilities'   => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability'     => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'idempotency' => \App\Http\Middleware\IdempotencyKey::class, 
            
            // ZT-DEFENSE: Middleware de Autenticação Server-to-Server B2B (HMAC SHA-256)
            'b2b.hmac'    => \App\Http\Middleware\VerifyB2bHmac::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->expectsJson() || $request->is('api/*') || $request->is('v1/webhooks/*');
        });
        
        $exceptions->dontReport([
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Validation\ValidationException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
        ]);
    })->create();