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
    PreventAccessFromCentralDomains::class, // Check central domains FIRST
    \Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::class,
])->group(function () {
    // Tenant welcome page (only accessible from tenant subdomains)
    Route::get('/welcome', function () {
        $tenant = tenant();
        return view('tenant-welcome', [
            'tenant' => $tenant,
            'tenantId' => tenant('id'),
            'tenantName' => $tenant->data['name'] ?? 'Unknown',
        ]);
    })->name('tenant.welcome');
    
    // Export routes
    Route::post('audits/{audit}/export', [\App\Http\Controllers\ExportController::class, 'requestExport'])->name('audits.export');
    Route::get('exports/download/{file}', [\App\Http\Controllers\ExportController::class, 'download'])->name('exports.download');
    
    // Tenant-specific routes will be added here
    // Filament admin panel will be accessible at /admin for each tenant
});

// Tenant API routes (with tenant context)
Route::middleware([
    'api',
    \App\Http\Middleware\InitializeTenancyByHeader::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api/tenant')->group(function () {
    // Audit logs API (requires authentication and proper role)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/audit-logs', [App\Http\Controllers\AuditLogController::class, 'index'])->name('api.tenant.audit-logs.index');
        Route::get('/audit-logs/{id}', [App\Http\Controllers\AuditLogController::class, 'show'])->name('api.tenant.audit-logs.show');
        Route::get('/audit-logs/export/csv', [App\Http\Controllers\AuditLogController::class, 'exportCsv'])->name('api.tenant.audit-logs.export.csv');
        Route::get('/audit-logs/export/json', [App\Http\Controllers\AuditLogController::class, 'exportJson'])->name('api.tenant.audit-logs.export.json');
    });
});
