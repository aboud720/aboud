<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - PSR-4 Autoloader
 * ═══════════════════════════════════════════════════════════════════════════
 */

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

spl_autoload_register(function (string $class): bool {
    // Namespace to directory mapping
    $namespaceMap = [
        'Core\\'        => ROOT_PATH . '/core/',
        'Models\\'      => ROOT_PATH . '/models/',
        'Controllers\\' => ROOT_PATH . '/controllers/',
        'Middleware\\'  => ROOT_PATH . '/middleware/',
        'Services\\'    => ROOT_PATH . '/services/',
        'Api\\'         => ROOT_PATH . '/api/',
    ];
    
    foreach ($namespaceMap as $namespace => $directory) {
        $namespaceLength = strlen($namespace);
        
        if (strncmp($namespace, $class, $namespaceLength) === 0) {
            $relativeClass = substr($class, $namespaceLength);
            $file = $directory . str_replace('\\', '/', $relativeClass) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    }
    
    return false;
});
