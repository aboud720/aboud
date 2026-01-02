<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Admin Authorization Middleware
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Middleware;

use Core\Application;
use Core\Response;
use Models\User;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class AdminMiddleware
{
    /**
     * Handle request
     */
    public function handle(Application $app, callable $next)
    {
        $session = $app->session();
        
        if (!$session->isLoggedIn()) {
            return $this->forbidden();
        }
        
        $user = User::find($session->userId());
        
        if (!$user || !$user->isAdmin()) {
            return $this->forbidden();
        }
        
        // Log admin access
        $user->logActivity('admin.access', null, null, [
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? ''
        ]);
        
        return $next();
    }
    
    /**
     * Return forbidden response
     */
    private function forbidden()
    {
        if ($this->isApiRequest()) {
            Response::forbidden('Admin access required');
        }
        
        Response::redirect('/');
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
