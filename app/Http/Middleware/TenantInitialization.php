<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unified Tenant Initialization Middleware
 * 
 * This middleware MUST run BEFORE StartSession to prevent CSRF token mismatch.
 * 
 * Flow:
 * 1. Request arrives
 * 2. TenantInitialization identifies tenant from subdomain
 * 3. Tenant context is set (database connection, storage disk, etc.)
 * 4. StartSession creates/reads session with correct tenant context
 * 5. CSRF token is generated/validated with stable session ID
 * 
 * This ensures that the session ID does not change during tenant initialization,
 * which would invalidate the CSRF token and cause 419 errors in Livewire/Filament.
 * 
 * @see https://tenancyforlaravel.com/docs/v3/middleware
 */
class TenantInitialization
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
        
        // For tenant domains (subdomains), initialize tenancy BEFORE session starts
        // This is critical: the tenant context must be set before the session cookie
        // is read/written to ensure the session and CSRF token remain stable
        try {
            // Store tenant ID in request attributes for debugging/logging
            $subdomain = $this->extractSubdomain($host);
            $request->attributes->set('tenant_subdomain', $subdomain);
            
            // Delegate to stancl/tenancy's built-in middleware
            // This will:
            // 1. Resolve tenant from subdomain
            // 2. Switch database connection to tenant DB
            // 3. Set tenant context for the request lifecycle
            return $this->tenancyMiddleware->handle($request, $next);
        } catch (\Throwable $e) {
            // Log tenant initialization errors but don't expose details to user
            Log::error('Tenant initialization failed', [
                'host' => $host,
                'path' => $request->path(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return 404 for invalid tenants (don't reveal tenant existence)
            abort(404, 'Tenant not found');
        }
    }

    /**
     * Check if the given host is a central domain
     */
    protected function isCentralDomain(string $host): bool
    {
        // Remove port if present (e.g., "localhost:8000" -> "localhost")
        $hostWithoutPort = explode(':', $host)[0];
        
        foreach ($this->centralDomains as $centralDomain) {
            $centralDomainWithoutPort = explode(':', $centralDomain)[0];
            if ($hostWithoutPort === $centralDomainWithoutPort) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extract subdomain from host
     * 
     * Examples:
     * - "test.localhost" -> "test"
     * - "client1.auditready.com" -> "client1"
     * - "localhost" -> null
     */
    protected function extractSubdomain(string $host): ?string
    {
        $hostWithoutPort = explode(':', $host)[0];
        $parts = explode('.', $hostWithoutPort);
        
        // If we have more than one part and the first part is not a central domain,
        // treat it as a subdomain
        if (count($parts) > 1 && !$this->isCentralDomain($hostWithoutPort)) {
            return $parts[0];
        }
        
        return null;
    }
}
