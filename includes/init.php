<?php
/**
 * FocusedTube - Initialization File
 * 
 * Loads all required components and sets up the application
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

// Load configuration
require_once __DIR__ . '/config.php';

// Define constants for directories
define('CONTROLLERS_PATH', __DIR__ . '/controllers');

// Load core classes
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/template.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/youtube.php';
require_once __DIR__ . '/functions.php';

// Load database helper functions if needed
if (file_exists(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
}

// Set security headers
Security::setSecurityHeaders();

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically
if (!isset($_SESSION['session_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['session_regenerated'] = time();
} elseif (time() - $_SESSION['session_regenerated'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['session_regenerated'] = time();
}

// Initialize database
global $db, $auth;
$db = new \FocusedTube\Database();
$auth = new \FocusedTube\Auth();

// Load settings
$settings = $db->read('settings.json');

// Check maintenance mode
if (isset($settings['maintenance']['enabled']) && $settings['maintenance']['enabled']) {
    $exemptIps = $settings['maintenance']['exempt_ips'] ?? [];
    $clientIp = Security::getClientIp();
    
    // Check if current IP is exempt
    $isExempt = in_array($clientIp, $exemptIps);
    
    // Check if user is admin
    $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    
    // Check if maintenance mode is enabled and user is not exempt
    $isApiRoute = strpos($_SERVER['REQUEST_URI'] ?? '', '/api') === 0;
    $isAdminRoute = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') === 0;
    $isLoginRoute = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/login') !== false;
    
    if (!$isExempt && !$isAdmin && !$isLoginRoute && !$isApiRoute) {
        if (!strpos($_SERVER['REQUEST_URI'] ?? '', '/api')) {
            include __DIR__ . '/../errors/maintenance.php';
            exit;
        }
    }
}

// Set default timezone
if (isset($settings['general']['timezone'])) {
    date_default_timezone_set($settings['general']['timezone']);
}

// CSRF token for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Define helper function for CSRF
if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
    }
}

if (!function_exists('check_csrf')) {
    function check_csrf($token) {
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Autoloader for classes
spl_autoload_register(function($class) {
    $prefix = 'FocusedTube\\';
    $base_dir = __DIR__ . '/';
    
    if (strpos($class, $prefix) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Log page access (except for API and assets)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$excludePatterns = ['/api/', '/assets/', '/admin/api/'];
$shouldLog = true;
foreach ($excludePatterns as $pattern) {
    if (strpos($requestUri, $pattern) === 0) {
        $shouldLog = false;
        break;
    }
}

if ($shouldLog && !isset($_SESSION['admin_logged_in'])) {
    $log = date('Y-m-d H:i:s') . " - " . Security::getClientIp() . " - " . 
           $_SERVER['REQUEST_METHOD'] . " " . $requestUri . "\n";
    @file_put_contents(LOGS_PATH . '/access.log', $log, FILE_APPEND);
}