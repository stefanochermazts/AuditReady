<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CRITICAL: Tenant initialization MUST happen BEFORE session starts
        // to prevent CSRF token mismatch (419 errors) in Livewire/Filament.
        //
        // Step 1: Add TenantInitialization to the web middleware group
        $middleware->web(prepend: [
            \App\Http\Middleware\TenantInitialization::class,
        ]);
        
        // Step 2: Ensure it runs BEFORE StartSession using priority list
        $middleware->prependToPriorityList(
            before: \Illuminate\Session\Middleware\StartSession::class,
            prepend: \App\Http\Middleware\TenantInitialization::class,
        );
        
        // NO CSRF EXCLUSIONS: Now that tenant initialization happens before session,
        // the CSRF token remains valid throughout the request. We do NOT need to
        // exclude 'livewire/*' or '2fa/*' routes - this would be a security risk.
        //
        // Laravel's VerifyCsrfToken will work correctly with the stable session.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Exception handling can be customized here if needed
    })->create();
