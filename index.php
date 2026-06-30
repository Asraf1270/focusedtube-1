<?php
/**
 * FocusedTube - Main Entry Point
 * 
 * This file handles all incoming requests and routes them to the appropriate handler
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

// Initialize the application
require_once __DIR__ . '/includes/init.php';

use FocusedTube\Router;
use FocusedTube\Security;
use FocusedTube\Template;
use FocusedTube\Auth;

try {
    // Create router instance
    $router = new Router();
    
    // Define public routes
    $router->add('/', 'HomeController@index', ['methods' => ['GET']]);
    $router->add('/watch', 'WatchController@index', ['methods' => ['GET']]);
    $router->add('/search', 'SearchController@index', ['methods' => ['GET']]);
    $router->add('/categories', 'CategoryController@index', ['methods' => ['GET']]);
    $router->add('/categories/{slug}', 'CategoryController@show', ['methods' => ['GET']]);
    $router->add('/tags', 'TagController@index', ['methods' => ['GET']]);
    $router->add('/tags/{slug}', 'TagController@show', ['methods' => ['GET']]);
    $router->add('/trending', 'TrendingController@index', ['methods' => ['GET']]);
    $router->add('/featured', 'FeaturedController@index', ['methods' => ['GET']]);
    $router->add('/popular', 'PopularController@index', ['methods' => ['GET']]);
    $router->add('/latest', 'LatestController@index', ['methods' => ['GET']]);
    $router->add('/channel/{id}', 'ChannelController@show', ['methods' => ['GET']]);
    $router->add('/playlists', 'PlaylistController@index', ['methods' => ['GET']]);
    $router->add('/playlist/{id}', 'PlaylistController@show', ['methods' => ['GET']]);
    $router->add('/watch-later', 'WatchLaterController@index', ['methods' => ['GET']]);
    $router->add('/favorites', 'FavoritesController@index', ['methods' => ['GET']]);
    $router->add('/history', 'HistoryController@index', ['methods' => ['GET']]);
    $router->add('/notifications', 'NotificationController@index', ['methods' => ['GET']]);
    $router->add('/about', 'AboutController@index', ['methods' => ['GET']]);
    $router->add('/logout', 'AuthController@logout', ['methods' => ['GET', 'POST']]);
    
    // Admin routes (protected)
    $router->add('/admin', 'AdminController@index', ['methods' => ['GET']]);
    $router->add('/admin/login', 'AdminController@login', ['methods' => ['GET', 'POST']]);
    $router->add('/admin/logout', 'AdminController@logout', ['methods' => ['GET', 'POST']]);
    $router->add('/admin/dashboard', 'AdminController@dashboard', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/users', 'AdminController@users', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/videos', 'AdminController@videos', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/categories', 'AdminController@categories', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/tags', 'AdminController@tags', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/settings', 'AdminController@settings', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/analytics', 'AdminController@analytics', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/reports', 'AdminController@reports', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/maintenance', 'AdminController@maintenance', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/backups', 'AdminController@backups', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/activity', 'AdminController@activity', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/api-settings', 'AdminController@apiSettings', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    $router->add('/admin/announcements', 'AdminController@announcements', ['methods' => ['GET'], 'auth' => true, 'role' => 'admin']);
    
    // API routes (all API endpoints go through api/index.php)
    $router->add('/api/{path}', 'ApiController@handle', ['methods' => ['GET', 'POST', 'PUT', 'DELETE']]);
    
    // Dispatch the request
    $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
    
} catch (Exception $e) {
    // Log the error
    error_log('FocusedTube Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    // Show error page
    if (ENVIRONMENT === 'development') {
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        // Show user-friendly error
        http_response_code(500);
        include __DIR__ . '/errors/500.php';
    }
}