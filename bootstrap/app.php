<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'b2b.hmac' => \App\Http\Middleware\VerifyB2bHmac::class,
            'idempotency' => \App\Http\Middleware\IdempotencyMiddleware::class,
        ]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ZT-DEFENSE: Mapeamento de falhas ACID para HTTP 409 Conflict.
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                $sqlState = $e->errorInfo[0] ?? '';
                // 40P01 = Deadlock Detected | 55P03 = Lock Not Available
                if ($sqlState === '40P01' || $sqlState === '55P03') {
                    return response()->json([
                        'error' => 'Recurso em contenção atômica. Múltiplos lances detectados. Tente novamente em instantes.'
                    ], Response::HTTP_CONFLICT);
                }
            }
        });
    })->create();