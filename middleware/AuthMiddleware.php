<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Authentication Middleware
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Middleware;

use Core\Application;
use Core\Response;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class AuthMiddleware
{
    /**
     * Handle request
     */
    public function handle(Application $app, callable $next)
    {
        $session = $app->session();
        
        if (!$session->isLoggedIn()) {
            // Check remember token
            $authService = new \Services\AuthService();
            if (!$authService->loginFromRememberToken()) {
                return $this->unauthorized();
            }
        }
        
        // Update last activity
        $user = $authService->getCurrentUser();
        if ($user) {
            $user->touch();
        }
        
        return $next();
    }
    
    /**
     * Return unauthorized response
     */
    private function unauthorized()
    {
        if ($this->isApiRequest()) {
            Response::unauthorized('Authentication required');
        }
        
        Response::redirect('/login');
    }
    
    /**
     * Check if API request
     */
    private function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') === 0;
    }
}
