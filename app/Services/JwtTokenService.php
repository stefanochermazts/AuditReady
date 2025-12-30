<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;

/**
 * JWT Token Service - Generate and validate JWT tokens for external API
 * 
 * This service handles JWT token generation and validation for third-party
 * upload API. Tokens are signed with HS256 algorithm.
 */
class JwtTokenService
{
    /**
     * Algorithm used for JWT signing
     */
    private const ALGORITHM = 'HS256';

    /**
     * Default token expiration (1 hour)
     */
    private const DEFAULT_EXPIRATION = 3600;

    /**
     * Generate JWT token for external upload
     *
     * @param User $user User with External Uploader role
     * @param string $tenantId Tenant UUID
     * @param int|null $expiration Expiration time in seconds (default: 1 hour)
     * @return string JWT token
     */
    public function generateToken(User $user, string $tenantId, ?int $expiration = null): string
    {
        // Verify user has External Uploader role
        if (!$user->hasRole('External Uploader')) {
            throw new \RuntimeException('User must have External Uploader role to generate upload token');
        }

        $expiration = $expiration ?? self::DEFAULT_EXPIRATION;
        $now = time();
        
        $payload = [
            'iss' => config('app.url'), // Issuer
            'sub' => $user->id, // Subject (user ID)
            'aud' => config('app.url'), // Audience
            'iat' => $now, // Issued at
            'exp' => $now + $expiration, // Expiration
            'tenant_id' => $tenantId,
            'scope' => 'upload:evidence', // Scope for upload permission
        ];

        $secret = $this->getSecret();
        
        return JWT::encode($payload, $secret, self::ALGORITHM);
    }

    /**
     * Validate and decode JWT token
     *
     * @param string $token JWT token
     * @return object Decoded token payload
     * @throws \Exception If token is invalid
     */
    public function validateToken(string $token): object
    {
        try {
            $secret = $this->getSecret();
            $decoded = JWT::decode($token, new Key($secret, self::ALGORITHM));
            
            // Verify scope
            if (!isset($decoded->scope) || $decoded->scope !== 'upload:evidence') {
                throw new \Exception('Invalid token scope');
            }
            
            // Verify tenant_id is present
            if (!isset($decoded->tenant_id)) {
                throw new \Exception('Missing tenant_id in token');
            }
            
            return $decoded;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Get JWT secret key
     *
     * @return string
     */
    private function getSecret(): string
    {
        $secret = config('app.key');
        
        // Remove 'base64:' prefix if present
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }
        
        return $secret;
    }
}
