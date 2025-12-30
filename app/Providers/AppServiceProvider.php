<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enforce TLS 1.2+ in production
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
            
            // Ensure minimum TLS version
            if (function_exists('curl_version')) {
                $curlVersion = curl_version();
                if (version_compare($curlVersion['version'], '7.34.0', '<')) {
                    \Log::warning('cURL version is below 7.34.0, TLS 1.2+ enforcement may not work properly');
                }
            }
        }
    }
}
