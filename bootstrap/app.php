<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // ==========================================
        // SANCTUM: HABILITA SESSÃO/COOKIES NA API
        // ==========================================
        $middleware->statefulApi();

        // ==========================================
        // REGISTRO DE MIDDLEWARES CUSTOMIZADOS
        // ==========================================
        // Conecta o alias 'role' ao guardião de papéis (RBAC)
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Tratamento de exceções customizadas
    })->create();