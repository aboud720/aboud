<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Router
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Clean URL routing with middleware support
 * @pattern      Front Controller Pattern
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Core;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class Router
{
    /**
     * Application instance
     */
    private Application $app;
    
    /**
     * Registered routes
     */
    private array $routes = [];
    
    /**
     * Route groups
     */
    private array $groupStack = [];
    
    /**
     * Named routes
     */
    private array $namedRoutes = [];
    
    /**
     * Current route
     */
    private ?array $currentRoute = null;
    
    /**
     * Constructor
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    /**
     * Register GET route
     */
    public function get(string $path, $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }
    
    /**
     * Register POST route
     */
    public function post(string $path, $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }
    
    /**
     * Register PUT route
     */
    public function put(string $path, $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }
    
    /**
     * Register DELETE route
     */
    public function delete(string $path, $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }
    
    /**
     * Register PATCH route
     */
    public function patch(string $path, $handler): self
    {
        return $this->addRoute('PATCH', $path, $handler);
    }
    
    /**
     * Register route for multiple methods
     */
    public function match(array $methods, string $path, $handler): self
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $path, $handler);
        }
        return $this;
    }
    
    /**
     * Register route for all methods
     */
    public function any(string $path, $handler): self
    {
        return $this->match(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $path, $handler);
    }
    
    /**
     * Add route to registry
     */
    private function addRoute(string $method, string $path, $handler): self
    {
        // Apply group prefix and middleware
        $prefix = '';
        $middleware = [];
        
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'] ?? '';
            $middleware = array_merge($middleware, $group['middleware'] ?? []);
        }
        
        $fullPath = $prefix . $path;
        
        $route = [
            'method'     => $method,
            'path'       => $fullPath,
            'pattern'    => $this->pathToPattern($fullPath),
            'handler'    => $handler,
            'middleware' => $middleware,
            'name'       => null,
        ];
        
        $this->routes[] = $route;
        $this->currentRoute = &$this->routes[count($this->routes) - 1];
        
        return $this;
    }
    
    /**
     * Name the route
     */
    public function name(string $name): self
    {
        if ($this->currentRoute) {
            $this->currentRoute['name'] = $name;
            $this->namedRoutes[$name] = $this->currentRoute;
        }
        return $this;
    }
    
    /**
     * Add middleware to route
     */
    public function middleware($middleware): self
    {
        if ($this->currentRoute) {
            if (is_array($middleware)) {
                $this->currentRoute['middleware'] = array_merge(
                    $this->currentRoute['middleware'],
                    $middleware
                );
            } else {
                $this->currentRoute['middleware'][] = $middleware;
            }
        }
        return $this;
    }
    
    /**
     * Create route group
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }
    
    /**
     * Convert path to regex pattern
     */
    private function pathToPattern(string $path): string
    {
        // Replace named parameters with regex groups
        $pattern = preg_replace(
            [
                '/\{([a-zA-Z_]+)\}/',      // {param} -> named group
                '/\{([a-zA-Z_]+)\?\}/',    // {param?} -> optional
            ],
            [
                '(?P<$1>[^/]+)',
                '(?P<$1>[^/]*)?',
            ],
            $path
        );
        
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Dispatch the request
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();
        
        // Handle OPTIONS for CORS
        if ($method === 'OPTIONS') {
            $this->handleOptions();
            return;
        }
        
        // Find matching route
        $route = $this->findRoute($method, $uri);
        
        if ($route === null) {
            $this->handleNotFound();
            return;
        }
        
        // Run middleware
        $response = $this->runMiddleware($route['middleware'], function() use ($route) {
            return $this->executeHandler($route['handler'], $route['params']);
        });
        
        // Send response
        if (is_string($response)) {
            echo $response;
        } elseif (is_array($response)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
        }
    }
    
    /**
     * Get cleaned URI
     */
    private function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove trailing slash (except for root)
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }
        
        return $uri;
    }
    
    /**
     * Find matching route
     */
    private function findRoute(string $method, string $uri): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                return array_merge($route, ['params' => $params]);
            }
        }
        
        return null;
    }
    
    /**
     * Run middleware chain
     */
    private function runMiddleware(array $middleware, callable $handler)
    {
        if (empty($middleware)) {
            return $handler();
        }
        
        $middlewareName = array_shift($middleware);
        
        $middlewareClass = "Middleware\\{$middlewareName}";
        
        if (!class_exists($middlewareClass)) {
            throw new \RuntimeException("Middleware not found: {$middlewareName}");
        }
        
        $middlewareInstance = new $middlewareClass();
        
        return $middlewareInstance->handle(
            $this->app,
            function() use ($middleware, $handler) {
                return $this->runMiddleware($middleware, $handler);
            }
        );
    }
    
    /**
     * Execute route handler
     */
    private function executeHandler($handler, array $params)
    {
        // Closure handler
        if ($handler instanceof \Closure) {
            return $handler($this->app, $params);
        }
        
        // Controller@method format
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            
            $controllerClass = "Controllers\\{$controller}";
            
            if (!class_exists($controllerClass)) {
                throw new \RuntimeException("Controller not found: {$controller}");
            }
            
            $controllerInstance = new $controllerClass($this->app);
            
            if (!method_exists($controllerInstance, $method)) {
                throw new \RuntimeException("Method not found: {$controller}@{$method}");
            }
            
            return $controllerInstance->$method($params);
        }
        
        // Array handler [Controller::class, 'method']
        if (is_array($handler)) {
            list($class, $method) = $handler;
            $instance = new $class($this->app);
            return $instance->$method($params);
        }
        
        throw new \RuntimeException("Invalid route handler");
    }
    
    /**
     * Handle OPTIONS request (CORS)
     */
    private function handleOptions(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = $this->app->config('security.allowed_origins', []);
        
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }
        
        http_response_code(204);
    }
    
    /**
     * Handle 404 Not Found
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        
        if ($this->isApiRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'The requested resource was not found'
                ]
            ]);
        } else {
            include ROOT_PATH . '/views/errors/404.php';
        }
    }
    
    /**
     * Check if API request
     */
    private function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') === 0;
    }
    
    /**
     * Generate URL for named route
     */
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route not found: {$name}");
        }
        
        $path = $this->namedRoutes[$name]['path'];
        
        // Replace parameters
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
            $path = str_replace('{' . $key . '?}', $value, $path);
        }
        
        // Remove optional parameters that weren't provided
        $path = preg_replace('/\{[a-zA-Z_]+\?\}/', '', $path);
        
        return $path;
    }
}

// Global helper function
function route(string $name, array $params = []): string
{
    return Application::getInstance()->resolve('router')->route($name, $params);
}
