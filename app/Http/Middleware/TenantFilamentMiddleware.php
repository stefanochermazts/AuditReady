<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to handle tenant context for Filament panel requests
 * 
 * This middleware MUST be the FIRST in the Filament middleware stack
 * to ensure tenant initialization happens BEFORE session starts.
 * 
 * Flow for Filament requests:
 * 1. TenantFilamentMiddleware (this) - identifies tenant, switches DB
 * 2. EncryptCookies - encrypts/decrypts cookies
 * 3. StartSession - starts/reads session with stable tenant context
 * 4. VerifyCsrfToken - validates CSRF with stable session ID
 * 5. ... rest of Filament middleware
 */
class TenantFilamentMiddleware
{
    /**
     * Central domains that should NOT trigger tenant initialization
     */
    protected array $centralDomains;

    /**
     * The tenant initialization middleware from stancl/tenancy
     */
    protected InitializeTenancyBySubdomain $tenancyMiddleware;

    public function __construct()
    {
        $this->centralDomains = config('tenancy.central_domains', []);
        $this->tenancyMiddleware = app(InitializeTenancyBySubdomain::class);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        
        // Skip tenant initialization for central domains
        if ($this->isCentralDomain($host)) {
            return $next($request);
        }
        
        // For tenant domains (subdomains), initialize tenancy
        // This MUST happen before EncryptCookies and StartSession
        try {
            // If tenant is already initialized (by global TenantInitialization),
            // just proceed. Otherwise, initialize it now.
            if (tenancy()->initialized) {
                return $next($request);
            }
            
            // Delegate to stancl/tenancy's built-in middleware
            return $this->tenancyMiddleware->handle($request, $next);
        } catch (\Throwable $e) {
            Log::error('Filament tenant initialization failed', [
                'host' => $host,
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);
            
            abort(404, 'Tenant not found');
        }
    }

    /**
     * Check if the given host is a central domain
     */
    protected function isCentralDomain(string $host): bool
    {
        $hostWithoutPort = explode(':', $host)[0];
        
        foreach ($this->centralDomains as $centralDomain) {
            $centralDomainWithoutPort = explode(':', $centralDomain)[0];
            if ($hostWithoutPort === $centralDomainWithoutPort) {
                return true;
            }
        }
        
        return false;
    }
}
