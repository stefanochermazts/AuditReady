<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to handle tenant context for Filament requests
 * 
 * For central domains, Filament works without tenant context.
 * For tenant domains, tenancy should already be initialized by
 * the tenant route middleware stack.
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
        // Check if we're accessing from a central domain
        // If so, Filament should work without tenant context (for central admin)
        $centralDomains = config('tenancy.central_domains', []);
        $host = $request->getHost();
        
        // If accessing from central domain, skip tenant initialization
        // This allows Filament to work for central admin panel
        if (in_array($host, $centralDomains)) {
            return $next($request);
        }
        
        // For tenant domains, tenancy should already be initialized
        // by the tenant route middleware (InitializeTenancyBySubdomain)
        // If not initialized, let the request continue anyway - Filament
        // will handle authentication and the tenant context will be set
        // by the route middleware if needed
        
        return $next($request);
    }
}
