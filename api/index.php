<?php
/**
 * FocusedTube - API Router
 * 
 * Main API entry point handling all REST endpoints
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/init.php';

use FocusedTube\Security;
use FocusedTube\Template;
use FocusedTube\Auth;

// Set JSON content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request path
$path = isset($_GET['path']) ? $_GET['path'] : '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Validate CSRF token for POST, PUT, DELETE
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    if (!Security::validateCsrfToken($csrfToken)) {
        Template::json(['error' => 'Invalid CSRF token'], 403);
        exit;
    }
}

// Rate limiting
$clientIp = Security::getClientIp();
$rateLimitKey = 'api_' . md5($clientIp . $path);
if (!Security::rateLimit($rateLimitKey, RATE_LIMIT_REQUESTS, RATE_LIMIT_TIME_WINDOW)) {
    Template::json(['error' => 'Rate limit exceeded. Please try again later.'], 429);
    exit;
}

// Route the request
$routes = [
    // Auth routes
    'POST /auth/login' => 'AuthController@login',
    'POST /auth/register' => 'AuthController@register',
    'POST /auth/logout' => 'AuthController@logout',
    'GET /auth/me' => 'AuthController@me',
    
    // Video routes
    'GET /videos' => 'VideoController@index',
    'GET /videos/{id}' => 'VideoController@show',
    'GET /videos/{id}/related' => 'VideoController@related',
    'POST /videos/import' => 'VideoController@import',
    'PUT /videos/{id}' => 'VideoController@update',
    'DELETE /videos/{id}' => 'VideoController@delete',
    
    // Search routes
    'GET /search' => 'SearchController@search',
    'GET /search/suggestions' => 'SearchController@suggestions',
    
    // Comment routes
    'GET /comments/{videoId}' => 'CommentController@index',
    'POST /comments' => 'CommentController@store',
    'DELETE /comments/{id}' => 'CommentController@delete',
    'PUT /comments/{id}' => 'CommentController@update',
    
    // Like routes
    'POST /likes/{videoId}' => 'LikeController@toggle',
    'GET /likes/{videoId}' => 'LikeController@get',
    
    // Favorite routes
    'POST /favorites/{videoId}' => 'FavoriteController@toggle',
    'GET /favorites' => 'FavoriteController@index',
    'DELETE /favorites/{videoId}' => 'FavoriteController@remove',
    
    // Watch Later routes
    'POST /watch-later/{videoId}' => 'WatchLaterController@toggle',
    'GET /watch-later' => 'WatchLaterController@index',
    'DELETE /watch-later/{videoId}' => 'WatchLaterController@remove',
    
    // History routes
    'POST /history' => 'HistoryController@add',
    'GET /history' => 'HistoryController@index',
    'DELETE /history' => 'HistoryController@clear',
    
    // Playlist routes
    'GET /playlists' => 'PlaylistController@index',
    'POST /playlists' => 'PlaylistController@store',
    'GET /playlists/{id}' => 'PlaylistController@show',
    'PUT /playlists/{id}' => 'PlaylistController@update',
    'DELETE /playlists/{id}' => 'PlaylistController@delete',
    'POST /playlists/{id}/videos' => 'PlaylistController@addVideo',
    'DELETE /playlists/{id}/videos/{videoId}' => 'PlaylistController@removeVideo',
    
    // YouTube routes
    'POST /youtube/import' => 'YouTubeController@import',
    'GET /youtube/metadata/{videoId}' => 'YouTubeController@metadata',
    
    // Category routes
    'GET /categories' => 'CategoryController@index',
    'GET /categories/{id}' => 'CategoryController@show',
    
    // Tag routes
    'GET /tags' => 'TagController@index',
    'GET /tags/{tag}' => 'TagController@show',
    
    // User routes
    'GET /user/profile' => 'UserController@profile',
    'PUT /user/profile' => 'UserController@update',
    
    // Notification routes
    'GET /notifications' => 'NotificationController@index',
    'POST /notifications/{id}/read' => 'NotificationController@markRead',
    'POST /notifications/read-all' => 'NotificationController@markAllRead'
];

// Find matching route
$routeFound = false;
$params = [];

foreach ($routes as $routePattern => $handler) {
    list($routeMethod, $routePath) = explode(' ', $routePattern, 2);
    
    if ($routeMethod !== $method) {
        continue;
    }
    
    // Convert route pattern to regex
    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
    $pattern = str_replace('/', '\/', $pattern);
    $pattern = '/^' . $pattern . '$/';
    
    if (preg_match($pattern, $path, $matches)) {
        $routeFound = true;
        $params = array_filter($matches, function($key) {
            return !is_numeric($key);
        }, ARRAY_FILTER_USE_KEY);
        
        // Execute handler
        list($controller, $method) = explode('@', $handler);
        $controllerClass = '\\FocusedTube\\Api\\' . $controller;
        
        if (!class_exists($controllerClass)) {
            Template::json(['error' => 'Controller not found'], 500);
            exit;
        }
        
        $controllerInstance = new $controllerClass();
        
        if (!method_exists($controllerInstance, $method)) {
            Template::json(['error' => 'Method not found'], 500);
            exit;
        }
        
        // Execute with input and params
        $result = $controllerInstance->$method($input, $params);
        Template::json($result['data'] ?? [], $result['status'] ?? 200);
        exit;
    }
}

// No route found
if (!$routeFound) {
    Template::json(['error' => 'Endpoint not found'], 404);
}