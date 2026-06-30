<?php
/**
 * FocusedTube - Router Handler
 * 
 * Handles URL routing and request dispatching
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube;

class Router
{
    /**
     * @var array $routes Route definitions
     */
    private $routes = [];
    
    /**
     * @var array $middleware Middleware stack
     */
    private $middleware = [];
    
    /**
     * Add route
     * 
     * @param string $path
     * @param string $handler
     * @param array $options
     */
    public function add($path, $handler, $options = [])
    {
        $this->routes[] = [
            'path' => $path,
            'handler' => $handler,
            'options' => $options,
            'methods' => $options['methods'] ?? ['GET']
        ];
    }
    
    /**
     * Add middleware
     * 
     * @param string $name
     * @param callable $callback
     */
    public function middleware($name, $callback)
    {
        $this->middleware[$name] = $callback;
    }
    
    /**
     * Dispatch route
     * 
     * @param string $uri
     * @param string $method
     */
    public function dispatch($uri, $method)
    {
        // Remove query string
        $uri = strtok($uri, '?');
        
        // Remove trailing slash (except for root)
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }
        if (empty($uri)) {
            $uri = '/';
        }
        
        // Find matching route
        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'])) {
                continue;
            }
            
            $pattern = $this->convertPathToRegex($route['path']);
            
            if (preg_match($pattern, $uri, $matches)) {
                // Remove numeric indices from matches
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key)) {
                        $params[$key] = $value;
                    }
                }
                
                // Check authentication
                if (isset($route['options']['auth']) && $route['options']['auth']) {
                    if (!isset($_SESSION['user_id'])) {
                        if (strpos($uri, '/admin') === 0) {
                            header('Location: /admin/login');
                        } else {
                            header('Location: /admin/login');
                        }
                        exit;
                    }
                }
                
                // Check role
                if (isset($route['options']['role'])) {
                    global $auth;
                    if (!isset($auth)) {
                        $auth = new Auth();
                    }
                    if (!$auth->hasRole($route['options']['role'])) {
                        Template::show403('Insufficient permissions');
                    }
                }
                
                // Execute handler
                $this->executeHandler($route['handler'], $params);
                return;
            }
        }
        
        // No route found
        Template::show404('Page not found');
    }
    
    /**
     * Convert path to regex
     * 
     * @param string $path
     * @return string
     */
    private function convertPathToRegex($path)
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';
        return $pattern;
    }
    
    /**
     * Execute handler
     * 
     * @param string $handler
     * @param array $params
     */
    private function executeHandler($handler, $params)
    {
        // Handle API routes specially
        if ($handler === 'ApiController@handle') {
            // Forward to API router
            $apiPath = '/api' . ($params['path'] ? '/' . $params['path'] : '');
            $_GET['path'] = $params['path'] ?? '';
            
            // Include the API router
            require_once __DIR__ . '/../api/index.php';
            return;
        }
        
        list($controller, $method) = explode('@', $handler);
        
        // Try different namespace variations
        $controllerClasses = [
            '\\FocusedTube\\Controllers\\' . $controller,
            '\\FocusedTube\\' . $controller,
            $controller
        ];
        
        $controllerInstance = null;
        $usedClass = null;
        
        foreach ($controllerClasses as $class) {
            if (class_exists($class)) {
                $controllerInstance = new $class();
                $usedClass = $class;
                break;
            }
        }
        
        if (!$controllerInstance) {
            Template::showError('Controller not found: ' . $controller);
        }
        
        if (!method_exists($controllerInstance, $method)) {
            Template::showError('Method not found: ' . $method . ' in ' . $usedClass);
        }
        
        // Execute middleware
        $this->executeMiddleware('before', $controllerInstance, $method);
        
        // Execute controller method
        $result = $controllerInstance->$method($params);
        
        // Execute middleware
        $this->executeMiddleware('after', $controllerInstance, $method);
        
        // Render result
        if ($result instanceof Template) {
            $result->render();
        } elseif (is_array($result)) {
            Template::json($result);
        }
    }
    
    /**
     * Execute middleware
     * 
     * @param string $type
     * @param object $controller
     * @param string $method
     */
    private function executeMiddleware($type, $controller, $method)
    {
        foreach ($this->middleware as $name => $callback) {
            if (is_callable($callback)) {
                $callback($type, $controller, $method);
            }
        }
    }
    
    /**
     * Get all routes
     * 
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}