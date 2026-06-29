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

// Load core classes
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/template.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/youtube.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/validator.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/pagination.php';

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

// Load settings
global $db;
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
    if (!$isExempt && !$isAdmin && !(isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin') === 0)) {
        include ERRORS_PATH . '/maintenance.php';
        exit;
    }
}

// Set default timezone
if (isset($settings['general']['timezone'])) {
    date_default_timezone_set($settings['general']['timezone']);
}

// Load language file
$language = $settings['general']['language'] ?? DEFAULT_LANGUAGE;
$langFile = INCLUDES_PATH . '/lang/' . $language . '.php';
if (file_exists($langFile)) {
    include $langFile;
}

// Register error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (error_reporting() & $errno) {
        $error = date('Y-m-d H:i:s') . " - Error: [$errno] $errstr in $errfile on line $errline\n";
        file_put_contents(LOGS_PATH . '/errors.log', $error, FILE_APPEND);
        
        if (ENVIRONMENT === 'development') {
            echo "<pre>$error</pre>";
        }
    }
});

// Register exception handler
set_exception_handler(function($exception) {
    $error = date('Y-m-d H:i:s') . " - Exception: " . $exception->getMessage() . 
             " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    file_put_contents(LOGS_PATH . '/errors.log', $error, FILE_APPEND);
    
    if (ENVIRONMENT === 'development') {
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    } else {
        Template::showError('An error occurred. Please try again later.', 500);
    }
});

// Register shutdown handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $log = date('Y-m-d H:i:s') . " - Fatal Error: " . $error['message'] . 
               " in " . $error['file'] . " on line " . $error['line'] . "\n";
        file_put_contents(LOGS_PATH . '/errors.log', $log, FILE_APPEND);
    }
});

// CSRF token for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Define helper function for CSRF
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

// Define helper function for CSRF check
function check_csrf($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Load database helper functions
require_once __DIR__ . '/helpers.php';

// Autoloader for classes
spl_autoload_register(function($class) {
    $prefix = 'FocusedTube\\';
    $base_dir = INCLUDES_PATH . '/';
    
    if (strpos($class, $prefix) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Log page access
if (!defined('LOG_ACCESS')) {
    define('LOG_ACCESS', true);
}

if (LOG_ACCESS && !isset($_SESSION['admin_logged_in'])) {
    $log = date('Y-m-d H:i:s') . " - " . Security::getClientIp() . " - " . 
           $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . "\n";
    file_put_contents(LOGS_PATH . '/access.log', $log, FILE_APPEND);
}
?>