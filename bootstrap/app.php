<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureTenantConfigured;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registrar alias de middleware
        $middleware->alias([
            'tenant' => EnsureTenantConfigured::class,
        ]);

        // Opcional: adicionar à pilha do grupo 'web' depois do 'auth'
        // Mantemos a aplicação por rota no routes/web.php
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
