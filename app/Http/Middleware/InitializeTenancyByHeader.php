<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Tenant;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initialize tenancy by X-Tenant-ID header
 * 
 * This middleware allows API requests to specify the tenant via header:
 * X-Tenant-ID: {tenant_uuid}
 */
class InitializeTenancyByHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process if X-Tenant-ID header is present
        $tenantId = $request->header('X-Tenant-ID');
        
        if (!$tenantId) {
            return $next($request);
        }

        // Find tenant by ID
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        // Initialize tenancy
        tenancy()->initialize($tenant);

        return $next($request);
    }
}
