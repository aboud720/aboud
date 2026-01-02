<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Base Controller
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Controllers;

use Core\Application;
use Core\Response;
use Core\Validator;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

abstract class BaseController
{
    /**
     * Application instance
     */
    protected Application $app;
    
    /**
     * Constructor
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    /**
     * Get current user
     */
    protected function user(): ?\Models\User
    {
        $userId = $this->app->session()->userId();
        return $userId ? \Models\User::find($userId) : null;
    }
    
    /**
     * Get request input
     */
    protected function input(string $key = null, $default = null)
    {
        $input = array_merge($_GET, $_POST);
        
        // Parse JSON body
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $json = json_decode(file_get_contents('php://input'), true);
            if ($json) {
                $input = array_merge($input, $json);
            }
        }
        
        if ($key === null) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }
    
    /**
     * Validate request
     */
    protected function validate(array $rules, array $messages = []): Validator
    {
        $validator = Validator::make($this->input(), $rules, $messages);
        
        if (!$validator->validate()) {
            Response::validationError($validator->firstErrors());
        }
        
        return $validator;
    }
    
    /**
     * Render view
     */
    protected function view(string $view, array $data = []): string
    {
        $viewPath = ROOT_PATH . '/views/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        
        // Add common data
        $data['app'] = $this->app;
        $data['user'] = $this->user();
        $data['csrf'] = $this->app->resolve('csrf');
        
        extract($data);
        
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
    
    /**
     * JSON response
     */
    protected function json(array $data, int $status = 200): void
    {
        Response::json($data, $status);
    }
    
    /**
     * Success response
     */
    protected function success($data = null, string $message = 'Success'): void
    {
        Response::success($data, $message);
    }
    
    /**
     * Error response
     */
    protected function error(string $message, string $code = 'ERROR', int $status = 400): void
    {
        Response::error($message, $code, $status);
    }
    
    /**
     * Redirect
     */
    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }
}
