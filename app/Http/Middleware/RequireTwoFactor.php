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
     * Roles that require mandatory 2FA
     */
    protected array $mandatoryRoles = [
        'Organization Owner',
        'Audit Manager',
        'Contributor',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip authentication-related routes (login, logout, password reset, etc.)
        // Also check path to be safe in case route name is not available
        $path = $request->path();
        $routeName = $request->route()?->getName();
        
        if ($request->routeIs('filament.admin.auth.login') ||
            $request->routeIs('filament.admin.auth.logout') ||
            $request->routeIs('filament.admin.auth.password.request') ||
            $request->routeIs('filament.admin.auth.password.reset') ||
            $request->routeIs('2fa.verify') ||
            $request->routeIs('2fa.verify.post') ||
            $request->routeIs('2fa.setup') ||
            $request->routeIs('2fa.enable') ||
            $request->routeIs('2fa.recovery-codes') ||
            $request->routeIs('2fa.disable') ||
            str_starts_with($path, 'admin/login') ||
            str_starts_with($path, '2fa/verify')) {
            return $next($request);
        }

        // Skip if user is not authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Check if user has a role that requires mandatory 2FA
        $requires2FA = $user->hasAnyRole($this->mandatoryRoles);

        // If user has mandatory role but 2FA is not enabled, redirect to setup
        if ($requires2FA && !$user->hasTwoFactorEnabled()) {
            // Skip 2FA setup page itself
            if ($request->routeIs('filament.admin.pages.two-factor-settings')) {
                return $next($request);
            }
            
            return redirect()->route('filament.admin.pages.two-factor-settings')
                ->with('error', 'È necessario configurare 2FA per continuare. Il tuo ruolo richiede l\'autenticazione a due fattori.');
        }

        // Skip if 2FA is not enabled (and not required)
        if (!$user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        // Skip if 2FA is already verified in this session or cookie
        // Check cookie first (persists across tenant initialization), then session
        $isVerified = session('2fa_verified') || $request->cookie('2fa_verified') === '1';
        
        if ($isVerified) {
            // If verified via cookie but not session, sync to session
            if (!session('2fa_verified') && $request->cookie('2fa_verified') === '1') {
                session(['2fa_verified' => true]);
            }
            
            return $next($request);
        }

        // Skip 2FA settings page
        if ($request->routeIs('filament.admin.pages.two-factor-settings')) {
            return $next($request);
        }

        // Save user ID in both session and cookie before redirecting to 2FA verification
        // Cookie persists across tenant initialization, session might not
        $userId = Auth::id();
        session(['2fa_user_id' => $userId]);
        
        // Redirect with cookie containing user ID
        return redirect()->route('2fa.verify')
            ->withCookie(cookie('2fa_user_id', $userId, 5)) // 5 minutes expiry
            ->with('error', 'È necessario verificare il codice 2FA per continuare.');
    }
}
