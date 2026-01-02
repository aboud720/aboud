<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Secure Session Management
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Production-grade session handling
 * @security     
 *   - Secure cookie settings
 *   - Session fixation protection
 *   - Session hijacking prevention
 *   - Idle timeout
 *   - Absolute timeout
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Core;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class Session
{
    /**
     * Configuration
     */
    private array $config;
    
    /**
     * Session started flag
     */
    private bool $started = false;
    
    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Start session with secure settings
     */
    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        
        // Configure session settings BEFORE starting
        $this->configureSession();
        
        // Start session
        if (!session_start()) {
            throw new \RuntimeException('Failed to start session');
        }
        
        $this->started = true;
        
        // Validate session
        $this->validateSession();
    }
    
    /**
     * Configure secure session settings
     */
    private function configureSession(): void
    {
        $lifetime = ($this->config['session_lifetime'] ?? 120) * 60;
        
        // Session cookie settings
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $this->config['session_secure'] ?? true,
            'httponly' => $this->config['session_httponly'] ?? true,
            'samesite' => $this->config['session_samesite'] ?? 'Strict',
        ]);
        
        // Use strict mode (reject uninitialized session IDs)
        ini_set('session.use_strict_mode', '1');
        
        // Only use cookies for session ID
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        
        // Use strong session ID
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        
        // Set session name
        session_name('ABOUD_SESSION');
        
        // Custom session save path
        $savePath = ROOT_PATH . '/storage/sessions';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0700, true);
        }
        session_save_path($savePath);
    }
    
    /**
     * Validate session (prevent hijacking/fixation)
     */
    private function validateSession(): void
    {
        $now = time();
        
        // Check for session initialization
        if (!isset($_SESSION['_initialized'])) {
            $this->regenerate();
            return;
        }
        
        // Check IP binding (optional but recommended)
        if (isset($_SESSION['_ip']) && $_SESSION['_ip'] !== $this->getIpHash()) {
            $this->destroy();
            return;
        }
        
        // Check user agent binding
        if (isset($_SESSION['_ua']) && $_SESSION['_ua'] !== $this->getUserAgentHash()) {
            $this->destroy();
            return;
        }
        
        // Check idle timeout (30 minutes)
        $idleTimeout = 1800;
        if (isset($_SESSION['_last_activity']) && ($now - $_SESSION['_last_activity']) > $idleTimeout) {
            $this->destroy();
            return;
        }
        
        // Check absolute timeout (8 hours)
        $absoluteTimeout = 28800;
        if (isset($_SESSION['_created']) && ($now - $_SESSION['_created']) > $absoluteTimeout) {
            $this->destroy();
            return;
        }
        
        // Update last activity
        $_SESSION['_last_activity'] = $now;
        
        // Regenerate session ID periodically (every 30 minutes)
        if (!isset($_SESSION['_regenerated']) || ($now - $_SESSION['_regenerated']) > 1800) {
            $this->regenerate(false);
        }
    }
    
    /**
     * Regenerate session ID (prevent fixation)
     */
    public function regenerate(bool $deleteOld = true): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        
        session_regenerate_id($deleteOld);
        
        $now = time();
        $_SESSION['_initialized'] = true;
        $_SESSION['_created'] = $_SESSION['_created'] ?? $now;
        $_SESSION['_regenerated'] = $now;
        $_SESSION['_last_activity'] = $now;
        $_SESSION['_ip'] = $this->getIpHash();
        $_SESSION['_ua'] = $this->getUserAgentHash();
    }
    
    /**
     * Get hashed IP (for comparison without storing actual IP)
     */
    private function getIpHash(): string
    {
        $ip = Application::getInstance()->security()->getClientIp();
        return hash('sha256', $ip . Application::getInstance()->config('app.key'));
    }
    
    /**
     * Get hashed user agent
     */
    private function getUserAgentHash(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return hash('sha256', $ua . Application::getInstance()->config('app.key'));
    }
    
    /**
     * Get session value
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Set session value
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session value
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
    
    /**
     * Get and remove value (flash data)
     */
    public function pull(string $key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }
    
    /**
     * Set flash message (available only on next request)
     */
    public function flash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Get flash message
     */
    public function getFlash(string $key, $default = null)
    {
        return $_SESSION['_flash'][$key] ?? $default;
    }
    
    /**
     * Clear all flash messages (called at end of request)
     */
    public function clearFlash(): void
    {
        unset($_SESSION['_flash']);
    }
    
    /**
     * Get all session data
     */
    public function all(): array
    {
        return $_SESSION;
    }
    
    /**
     * Clear all user data (preserve system data)
     */
    public function clear(): void
    {
        $preserve = ['_initialized', '_created', '_regenerated', '_last_activity', '_ip', '_ua'];
        
        foreach ($_SESSION as $key => $value) {
            if (!in_array($key, $preserve)) {
                unset($_SESSION[$key]);
            }
        }
    }
    
    /**
     * Destroy session completely
     */
    public function destroy(): void
    {
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
        $this->started = false;
    }
    
    /**
     * Get session ID
     */
    public function getId(): string
    {
        return session_id();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }
    
    /**
     * Get logged in user ID
     */
    public function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Set logged in user
     */
    public function login(int $userId, array $userData = []): void
    {
        // Regenerate session ID on login (prevent fixation)
        $this->regenerate();
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_data'] = $userData;
        $_SESSION['logged_in_at'] = time();
    }
    
    /**
     * Logout user
     */
    public function logout(): void
    {
        $this->remove('user_id');
        $this->remove('user_data');
        $this->remove('logged_in_at');
        
        // Regenerate session ID
        $this->regenerate();
    }
}
