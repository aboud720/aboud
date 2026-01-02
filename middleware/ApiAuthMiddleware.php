<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - API Authentication Middleware (JWT)
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Middleware;

use Core\Application;
use Core\Response;
use Core\JWT;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class ApiAuthMiddleware
{
    /**
     * Handle request
     */
    public function handle(Application $app, callable $next)
    {
        $token = JWT::fromRequest();
        
        if (!$token) {
            Response::unauthorized('No token provided');
        }
        
        $jwtConfig = $app->config('security.jwt');
        $jwt = new JWT($jwtConfig);
        
        $payload = $jwt->validate($token);
        
        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
        }
        
        // Check if it's not a refresh token
        if (isset($payload['type']) && $payload['type'] === 'refresh') {
            Response::unauthorized('Cannot use refresh token for API access');
        }
        
        // Get user
        $userId = $payload['sub'] ?? null;
        if (!$userId) {
            Response::unauthorized('Invalid token payload');
        }
        
        $user = \Models\User::find($userId);
        
        if (!$user || !$user->isActive()) {
            Response::unauthorized('User not found or inactive');
        }
        
        // Check rate limiting for API
        $rateLimiter = $app->resolve('rateLimit');
        if (!$rateLimiter->checkApi($userId)) {
            Response::error(
                'Too many requests',
                'RATE_LIMIT_EXCEEDED',
                429,
                ['retry_after' => $rateLimiter->getRetryAfter()]
            );
        }
        
        // Store user in container for later use
        $app->container['api_user'] = $user;
        
        // Update activity
        $user->touch();
        
        return $next();
    }
}
