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

// Filament admin panel (central, accessible from central domains)
// Note: Filament routes are automatically registered by AdminPanelProvider

// 2FA Routes
Route::middleware(['web', 'auth'])->prefix('2fa')->name('2fa.')->group(function () {
    Route::get('/setup', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'showSetupForm'])->name('setup');
    Route::post('/enable', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'enable'])->name('enable');
    Route::get('/recovery-codes', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'showRecoveryCodes'])->name('recovery-codes');
    Route::post('/disable', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'disable'])->name('disable');
});

Route::middleware(['web'])->prefix('2fa')->name('2fa.')->group(function () {
    Route::get('/verify', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'showVerificationForm'])->name('verify');
    Route::post('/verify', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'verify'])->name('verify.post');
});
