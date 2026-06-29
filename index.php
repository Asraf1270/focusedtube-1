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

// Create router instance
$router = new Router();

// Define public routes
$router->add('/', 'HomeController@index');
$router->add('/watch', 'WatchController@index');
$router->add('/search', 'SearchController@index');
$router->add('/categories', 'CategoryController@index');
$router->add('/categories/{slug}', 'CategoryController@show');
$router->add('/tags', 'TagController@index');
$router->add('/tags/{slug}', 'TagController@show');
$router->add('/trending', 'TrendingController@index');
$router->add('/featured', 'FeaturedController@index');
$router->add('/popular', 'PopularController@index');
$router->add('/latest', 'LatestController@index');
$router->add('/channel/{id}', 'ChannelController@show');
$router->add('/playlists', 'PlaylistController@index');
$router->add('/playlist/{id}', 'PlaylistController@show');
$router->add('/watch-later', 'WatchLaterController@index');
$router->add('/favorites', 'FavoritesController@index');
$router->add('/history', 'HistoryController@index');
$router->add('/notifications', 'NotificationController@index');
$router->add('/about', 'AboutController@index');

// Admin routes (protected)
$router->add('/admin', 'AdminController@index', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/login', 'AdminController@login');
$router->add('/admin/logout', 'AdminController@logout');
$router->add('/admin/dashboard', 'AdminController@dashboard', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/users', 'AdminController@users', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/videos', 'AdminController@videos', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/categories', 'AdminController@categories', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/tags', 'AdminController@tags', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/settings', 'AdminController@settings', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/analytics', 'AdminController@analytics', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/reports', 'AdminController@reports', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/maintenance', 'AdminController@maintenance', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/backups', 'AdminController@backups', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/activity', 'AdminController@activity', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/api-settings', 'AdminController@apiSettings', ['auth' => true, 'role' => 'admin']);
$router->add('/admin/announcements', 'AdminController@announcements', ['auth' => true, 'role' => 'admin']);

// API routes
$router->add('/api/youtube', 'ApiController@youtube');
$router->add('/api/videos', 'ApiController@videos');
$router->add('/api/auth', 'ApiController@auth');
$router->add('/api/comments', 'ApiController@comments');
$router->add('/api/likes', 'ApiController@likes');
$router->add('/api/history', 'ApiController@history');
$router->add('/api/favorites', 'ApiController@favorites');
$router->add('/api/playlists', 'ApiController@playlists');
$router->add('/api/search', 'ApiController@search');

// Dispatch the request
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
?>