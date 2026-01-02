<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Main Application Class
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Central application container using Singleton pattern
 * @pattern      Singleton, Dependency Injection Container
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Core;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class Application
{
    /**
     * Singleton instance
     */
    private static ?Application $instance = null;
    
    /**
     * Application configuration
     */
    private array $config = [];
    
    /**
     * Service container
     */
    private array $container = [];
    
    /**
     * Private constructor (Singleton)
     */
    private function __construct()
    {
        // Initialize error handler
        $this->initializeErrorHandler();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
    
    /**
     * Initialize custom error handler
     */
    private function initializeErrorHandler(): void
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        
        set_exception_handler(function (\Throwable $e) {
            $this->handleException($e);
        });
        
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
                $this->handleException(new \ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                ));
            }
        });
    }
    
    /**
     * Handle uncaught exceptions
     */
    private function handleException(\Throwable $e): void
    {
        // Log error
        $this->logError($e);
        
        // Return JSON for API requests
        if ($this->isApiRequest()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $this->config['app']['debug'] ?? false
                        ? $e->getMessage()
                        : 'An internal error occurred'
                ]
            ]);
            exit;
        }
        
        // Show error page
        http_response_code(500);
        if (($this->config['app']['debug'] ?? false) === true) {
            echo '<h1>Error</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            include ROOT_PATH . '/views/errors/500.php';
        }
        exit;
    }
    
    /**
     * Check if current request is API
     */
    private function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') === 0 || 
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
    
    /**
     * Log error to file
     */
    private function logError(\Throwable $e): void
    {
        $logFile = ROOT_PATH . '/storage/logs/error_' . date('Y-m-d') . '.log';
        $message = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        
        @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Load configuration
     */
    public function loadConfiguration(array $config): self
    {
        $this->config = $config;
        
        // Define debug constant
        define('APP_DEBUG', $config['app']['debug'] ?? false);
        
        return $this;
    }
    
    /**
     * Get configuration value
     */
    public function config(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Initialize database connection
     */
    public function initializeDatabase(): self
    {
        $this->container['database'] = function () {
            return Database::getInstance($this->config['database']);
        };
        
        return $this;
    }
    
    /**
     * Initialize security services
     */
    public function initializeSecurity(): self
    {
        $this->container['security'] = function () {
            return new Security($this->config['security']);
        };
        
        $this->container['csrf'] = function () {
            return new CsrfProtection($this->config['security']);
        };
        
        $this->container['rateLimit'] = function () {
            return new RateLimiter($this->config['security']['rate_limit']);
        };
        
        return $this;
    }
    
    /**
     * Initialize session management
     */
    public function initializeSession(): self
    {
        $this->container['session'] = function () {
            return new Session($this->config['security']);
        };
        
        // Start session
        $this->resolve('session')->start();
        
        return $this;
    }
    
    /**
     * Resolve service from container
     */
    public function resolve(string $name)
    {
        if (!isset($this->container[$name])) {
            throw new \RuntimeException("Service '{$name}' not found in container");
        }
        
        // Lazy loading - resolve only when needed
        if (is_callable($this->container[$name])) {
            $this->container[$name] = $this->container[$name]();
        }
        
        return $this->container[$name];
    }
    
    /**
     * Handle incoming request
     */
    public function handleRequest(): void
    {
        // Check rate limiting
        $rateLimit = $this->resolve('rateLimit');
        if (!$rateLimit->check()) {
            http_response_code(429);
            header('Retry-After: ' . $rateLimit->getRetryAfter());
            
            if ($this->isApiRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'message' => 'Too many requests. Please try again later.',
                        'retry_after' => $rateLimit->getRetryAfter()
                    ]
                ]);
            } else {
                echo 'Too many requests. Please try again later.';
            }
            exit;
        }
        
        // Initialize router
        $router = new Router($this);
        
        // Load routes
        require ROOT_PATH . '/config/routes.php';
        
        // Dispatch request
        $router->dispatch();
    }
    
    /**
     * Get database instance
     */
    public function db(): Database
    {
        return $this->resolve('database');
    }
    
    /**
     * Get session instance
     */
    public function session(): Session
    {
        return $this->resolve('session');
    }
    
    /**
     * Get security instance
     */
    public function security(): Security
    {
        return $this->resolve('security');
    }
}
