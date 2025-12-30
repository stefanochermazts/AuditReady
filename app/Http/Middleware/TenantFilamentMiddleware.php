<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to initialize tenancy for Filament requests
 * 
 * This middleware ensures that tenant context is resolved before
 * any Filament resource or page is accessed.
 */
class TenantFilamentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use the existing subdomain initialization middleware logic
        // The tenancy is already initialized by InitializeTenancyBySubdomain
        // in the tenant routes, but we ensure it's set for Filament
        
        // If tenancy is not initialized, try to initialize it
        if (!tenancy()->initialized) {
            // Try to resolve tenant from subdomain
            $subdomainMiddleware = new InitializeTenancyBySubdomain();
            $subdomainMiddleware->handle($request, $next);
        }

        return $next($request);
    }
}
