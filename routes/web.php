<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Central Application Routes
|--------------------------------------------------------------------------
|
| These routes are for the central application (landlord).
| Tenant-specific routes are in routes/tenant.php
|
*/

Route::middleware(['web'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
    
    // Central admin routes (for managing tenants)
    // Will be implemented with Filament in Step 6
});
