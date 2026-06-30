<?php
/**
 * FocusedTube - Initialization File
 * 
 * Loads all required components and sets up the application
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

// Load configuration FIRST
require_once __DIR__ . '/config.php';

// Define constants for directories
define('CONTROLLERS_PATH', __DIR__ . '/controllers');

// Set error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set default timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// AUTOLOADER - Load classes on demand
// ============================================
spl_autoload_register(function($class) {
    // Only handle FocusedTube namespace
    if (strpos($class, 'FocusedTube\\') !== 0) {
        return;
    }
    
    // Remove namespace prefix
    $relative_class = substr($class, strlen('FocusedTube\\'));
    
    // Convert namespace separators to directory separators
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative_class) . '.php';
    
    // If not found, check in controllers directory (includes/controllers/)
    if (!file_exists($file)) {
        $file = __DIR__ . '/controllers/' . str_replace('\\', '/', $relative_class) . '.php';
    }
    
    // If still not found, try removing the Controllers namespace part
    if (!file_exists($file)) {
        $parts = explode('\\', $relative_class);
        // If the first part is 'Controllers', remove it
        if ($parts[0] === 'Controllers') {
            array_shift($parts);
            $newClass = implode('/', $parts);
            $file = __DIR__ . '/controllers/' . $newClass . '.php';
        }
    }
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

// ============================================
// LOAD CORE CLASSES (in correct order)
// ============================================

// First load Security class (no dependencies)
require_once __DIR__ . '/security.php';

// Then load Database class (depends on Security for some methods)
require_once __DIR__ . '/database.php';

// Load Cache class (depends on config constants)
require_once __DIR__ . '/cache.php';

// Load Auth class (depends on Database and Security)
require_once __DIR__ . '/auth.php';

// Load Template class (depends on Security)
require_once __DIR__ . '/template.php';

// Load Router class (depends on Template)
require_once __DIR__ . '/router.php';

// Load YouTubeAPI class (depends on Cache)
require_once __DIR__ . '/youtube.php';

// Load functions (global helper functions)
require_once __DIR__ . '/functions.php';

// Load database helper functions if needed
if (file_exists(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
}

// ============================================
// INITIALIZE CORE COMPONENTS
// ============================================

use FocusedTube\Security;
use FocusedTube\Database;
use FocusedTube\Auth;

// Set security headers (now Security class is loaded)
Security::setSecurityHeaders();

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
$db = new Database();
$auth = new Auth();

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

// Set default timezone from settings
if (isset($settings['general']['timezone'])) {
    date_default_timezone_set($settings['general']['timezone']);
}

// CSRF token for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Security::generateCsrfToken();
}

// ============================================
// DEFINE GLOBAL HELPER FUNCTIONS
// ============================================

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

// ============================================
// LOGGING
// ============================================

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

// At the end of the file, add this:
$extraScripts = [
    '/assets/js/header.js'
];