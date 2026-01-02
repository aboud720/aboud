<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - CSRF Protection Middleware
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Middleware;

use Core\Application;
use Core\Response;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class CsrfMiddleware
{
    /**
     * Handle request
     */
    public function handle(Application $app, callable $next)
    {
        $csrf = $app->resolve('csrf');
        
        if (!$csrf->validateRequest()) {
            if ($this->isApiRequest()) {
                Response::error('Invalid CSRF token', 'CSRF_INVALID', 403);
            }
            
            // Redirect back with error
            $app->session()->flash('error', 'CSRF token mismatch. Please try again.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
        
        return $next();
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
