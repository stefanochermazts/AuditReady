<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to require 2FA verification for authenticated users
 * 
 * This middleware checks if:
 * 1. User is authenticated
 * 2. User has 2FA enabled
 * 3. 2FA has been verified in this session
 * 
 * If 2FA is enabled but not verified, redirects to verification page.
 */
class RequireTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if user is not authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip if 2FA is not enabled
        if (!$user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        // Skip if 2FA is already verified in this session
        if (session('2fa_verified')) {
            return $next($request);
        }

        // Skip verification route itself
        if ($request->routeIs('2fa.verify') || $request->routeIs('2fa.verify.post')) {
            return $next($request);
        }

        // Redirect to 2FA verification
        return redirect()->route('2fa.verify')
            ->with('error', 'È necessario verificare il codice 2FA per continuare.');
    }
}
