<?php

namespace App\Http\Middleware;

use App\Services\JwtTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validate JWT Token Middleware
 * 
 * This middleware validates JWT tokens for external API requests.
 * Token must be provided in Authorization header as: Bearer {token}
 */
class ValidateJwtToken
{
    public function __construct(
        private JwtTokenService $jwtService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing or invalid Authorization header',
            ], 401);
        }

        $token = substr($authHeader, 7); // Remove 'Bearer ' prefix

        try {
            $decoded = $this->jwtService->validateToken($token);
            
            // Store decoded token in request for later use
            $request->merge(['jwt_payload' => $decoded]);
            
            // Set tenant ID from token if not already set
            if (!tenant('id') && isset($decoded->tenant_id)) {
                $request->headers->set('X-Tenant-ID', $decoded->tenant_id);
            }
            
            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $e->getMessage(),
            ], 401);
        }
    }
}
