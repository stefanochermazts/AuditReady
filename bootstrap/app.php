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
        // Require 2FA for authenticated users with 2FA enabled
        $middleware->web(append: [
            \App\Http\Middleware\RequireTwoFactor::class,
        ]);
        
        // Note: Tenancy middleware is handled by TenancyServiceProvider
        // and applied only to tenant routes, not global routes
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
