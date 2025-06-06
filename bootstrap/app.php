<?php

use App\Services\ExceptionHandlerService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\RequestLoggingMiddleware::class);

        $middleware->alias([
            'log-requests' => \App\Http\Middleware\RequestLoggingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        // Single exception handler that routes to service
        $exceptions->render(function (Throwable $e) {
            return app(ExceptionHandlerService::class)->handle($e);
        });
    })->create();
