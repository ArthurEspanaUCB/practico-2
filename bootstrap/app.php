<?php

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
    ->withMiddleware(function (Middleware $middleware): void {
        // Headers de Seguridad Globales
        $middleware->append(App\Http\Middleware\SecurityHeadersMiddleware::class);

        // Stateful para Sanctum (si se usa SPA, pero aquí es API stateless principalmente,
        // aunque sanctum puede gestionar cookie auth. Lo dejaremos por defecto).
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
