<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\InitializeTenancyByHeader;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Central API routes (no tenant context)
Route::middleware(['api'])->prefix('central')->group(function () {
    // Central admin API routes
    // Will be implemented later
});

// Tenant API routes (with tenant context)
Route::middleware([
    'api',
    InitializeTenancyByHeader::class,
])->prefix('tenant')->group(function () {
    // Tenant-specific API routes
    // External upload API will be here (Step 9)
    
    Route::get('/info', function () {
        return response()->json([
            'tenant_id' => tenant('id'),
            'tenant_name' => tenant()->data['name'] ?? 'Unknown',
        ]);
    });
});
