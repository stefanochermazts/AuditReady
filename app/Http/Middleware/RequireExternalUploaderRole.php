<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require External Uploader Role Middleware
 * 
 * This middleware ensures the authenticated user has the External Uploader role.
 * It should be used after JWT validation middleware.
 */
class RequireExternalUploaderRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get user ID from JWT payload
        $jwtPayload = $request->input('jwt_payload');
        
        if (!$jwtPayload || !isset($jwtPayload->sub)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Invalid token payload',
            ], 403);
        }

        // Find user
        $user = \App\Models\User::find($jwtPayload->sub);
        
        if (!$user) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'User not found',
            ], 403);
        }

        // Check role
        if (!$user->hasRole('External Uploader')) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'User does not have External Uploader role',
            ], 403);
        }

        // Attach user to request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
