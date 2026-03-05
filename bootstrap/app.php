<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // In produzione (APP_DEBUG=false) Laravel non espone stack trace di default.
        // Registriamo AppException come 503 JSON per tutti gli ambienti. [AGENTS.md §5]
        $exceptions->render(function (\App\Exceptions\AppException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(
                    ['error' => 'Servizio temporaneamente non disponibile'],
                    503
                );
            }
        });
    })->create();
