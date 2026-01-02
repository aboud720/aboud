<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Application Entry Point
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Single entry point for all requests (Front Controller Pattern)
 * @security     All requests pass through this file for centralized security
 * ═══════════════════════════════════════════════════════════════════════════
 */

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════
// Define Application Constant (prevents direct file access)
// ═══════════════════════════════════════════════════════════════════════════
define('ABOUD_STORE', true);
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);
define('START_TIME', microtime(true));
define('START_MEMORY', memory_get_usage());

// ═══════════════════════════════════════════════════════════════════════════
// Security Headers (Set before any output)
// ═══════════════════════════════════════════════════════════════════════════
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Content Security Policy (CSP) - Strict
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self';");

// Remove PHP version header
header_remove('X-Powered-By');

// ═══════════════════════════════════════════════════════════════════════════
// Error Handling Configuration
// ═══════════════════════════════════════════════════════════════════════════
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/storage/logs/php_errors.log');

// ═══════════════════════════════════════════════════════════════════════════
// Load Autoloader
// ═══════════════════════════════════════════════════════════════════════════
require_once ROOT_PATH . '/core/Autoloader.php';

// ═══════════════════════════════════════════════════════════════════════════
// Initialize Application
// ═══════════════════════════════════════════════════════════════════════════
try {
    // Load core components
    $config = require ROOT_PATH . '/config/app.php';
    
    // Set timezone
    date_default_timezone_set($config['app']['timezone']);
    
    // Initialize core services
    \Core\Application::getInstance()
        ->loadConfiguration($config)
        ->initializeDatabase()
        ->initializeSecurity()
        ->initializeSession()
        ->handleRequest();
        
} catch (\Throwable $e) {
    // Log the error
    error_log(sprintf(
        "[%s] %s in %s:%d\nStack trace:\n%s",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));
    
    // Show user-friendly error page
    http_response_code(500);
    
    if (defined('APP_DEBUG') && APP_DEBUG === true) {
        echo '<h1>Application Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        include ROOT_PATH . '/views/errors/500.php';
    }
    
    exit(1);
}
