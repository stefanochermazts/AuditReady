<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| These routes are loaded for each tenant when tenancy is initialized.
| Tenant identification happens via middleware (subdomain, header, or path).
|
*/

Route::middleware([
    'web',
    \Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', function () {
        $tenant = tenant();
        return view('tenant-welcome', [
            'tenant' => $tenant,
            'tenantId' => tenant('id'),
            'tenantName' => $tenant->data['name'] ?? 'Unknown',
        ]);
    });
    
    // Tenant-specific routes will be added here
    // Filament admin panel will be accessible at /admin for each tenant
});
