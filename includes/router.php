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
            'methods' => $options['methods'] ?? ['GET', 'POST']
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
        
        // Remove trailing slash
        $uri = rtrim($uri, '/');
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
                        header('Location: /admin/login');
                        exit;
                    }
                }
                
                // Check role
                if (isset($route['options']['role'])) {
                    global $auth;
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
        $pattern = '/^' . $pattern . '$\/?/';
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
        list($controller, $method) = explode('@', $handler);
        
        $controllerClass = '\\FocusedTube\\Controllers\\' . $controller;
        
        if (!class_exists($controllerClass)) {
            Template::showError('Controller not found: ' . $controllerClass);
        }
        
        $controllerInstance = new $controllerClass();
        
        if (!method_exists($controllerInstance, $method)) {
            Template::showError('Method not found: ' . $method);
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
?>