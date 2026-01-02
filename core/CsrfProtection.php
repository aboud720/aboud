<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - CSRF Protection
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Cross-Site Request Forgery protection
 * @security     
 *   - Token per session (simpler but still secure)
 *   - Timing-safe comparison
 *   - Token regeneration
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Core;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class CsrfProtection
{
    /**
     * Configuration
     */
    private array $config;
    
    /**
     * Session key for CSRF token
     */
    private const SESSION_KEY = '_csrf_token';
    private const SESSION_KEY_TIME = '_csrf_token_time';
    
    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Get or generate CSRF token
     */
    public function getToken(): string
    {
        $session = Application::getInstance()->session();
        
        // Check if token exists and is not expired
        if ($session->has(self::SESSION_KEY)) {
            $tokenTime = $session->get(self::SESSION_KEY_TIME, 0);
            $expireTime = $this->config['csrf_token_expire'] ?? 3600;
            
            if ((time() - $tokenTime) < $expireTime) {
                return $session->get(self::SESSION_KEY);
            }
        }
        
        // Generate new token
        return $this->regenerateToken();
    }
    
    /**
     * Generate new CSRF token
     */
    public function regenerateToken(): string
    {
        $session = Application::getInstance()->session();
        $length = $this->config['csrf_token_length'] ?? 64;
        
        $token = bin2hex(random_bytes($length / 2));
        
        $session->set(self::SESSION_KEY, $token);
        $session->set(self::SESSION_KEY_TIME, time());
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public function validate(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }
        
        $session = Application::getInstance()->session();
        $storedToken = $session->get(self::SESSION_KEY);
        
        if ($storedToken === null) {
            return false;
        }
        
        // Check expiration
        $tokenTime = $session->get(self::SESSION_KEY_TIME, 0);
        $expireTime = $this->config['csrf_token_expire'] ?? 3600;
        
        if ((time() - $tokenTime) > $expireTime) {
            return false;
        }
        
        // Timing-safe comparison
        return hash_equals($storedToken, $token);
    }
    
    /**
     * Get token from request
     */
    public function getTokenFromRequest(): ?string
    {
        // Check POST data
        if (isset($_POST['_csrf_token'])) {
            return $_POST['_csrf_token'];
        }
        
        // Check headers (for AJAX)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        return null;
    }
    
    /**
     * Validate request has valid CSRF token
     */
    public function validateRequest(): bool
    {
        // Skip for safe HTTP methods
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }
        
        $token = $this->getTokenFromRequest();
        return $this->validate($token);
    }
    
    /**
     * Generate hidden input field with token
     */
    public function field(): string
    {
        $token = $this->getToken();
        return sprintf(
            '<input type="hidden" name="_csrf_token" value="%s">',
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
    
    /**
     * Generate meta tag for AJAX requests
     */
    public function meta(): string
    {
        $token = $this->getToken();
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
}
