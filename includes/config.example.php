<?php
/**
 * FocusedTube - Configuration File
 * 
 * Central configuration for the entire application
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

// Define application environment
define('ENVIRONMENT', 'development'); // development, testing, production

// Error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Application paths
define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('API_PATH', ROOT_PATH . '/api');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('DATA_PATH', ROOT_PATH . '/data');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('BACKUPS_PATH', ROOT_PATH . '/backups');
define('ERRORS_PATH', ROOT_PATH . '/errors'); // <-- ADD THIS LINE

// Application URLs
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('ASSETS_URL', SITE_URL . '/assets');
define('ADMIN_URL', SITE_URL . '/admin');
define('API_URL', SITE_URL . '/api');

// Application settings
define('APP_NAME', 'FocusedTube');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Self-hosted YouTube video library');

// Security settings
define('SALT', 'your-secure-salt-here-change-in-production');
define('SESSION_NAME', 'focusedtube_session');
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LIFETIME', 3600);

// Rate limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_TIME_WINDOW', 3600); // 1 hour

// API settings
define('YOUTUBE_API_KEY', ''); // Set in admin panel
define('YOUTUBE_API_URL', 'https://www.googleapis.com/youtube/v3/');

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour

// Pagination settings
define('ITEMS_PER_PAGE', 20);
define('ADMIN_ITEMS_PER_PAGE', 50);

// Upload settings
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);

// Default settings
define('DEFAULT_THEME', 'light');
define('DEFAULT_LANGUAGE', 'en');
define('DEFAULT_TIMEZONE', 'UTC');

// Timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Session configuration
ini_set('session.name', SESSION_NAME);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Set session save path (create directory if it doesn't exist)
$sessionPath = ROOT_PATH . '/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables from .env file if exists
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Ensure all required directories exist
$directories = [
    DATA_PATH,
    LOGS_PATH,
    CACHE_PATH,
    BACKUPS_PATH,
    ERRORS_PATH,
    UPLOADS_PATH,
    UPLOADS_PATH . '/logos',
    UPLOADS_PATH . '/banners',
    UPLOADS_PATH . '/thumbnails',
    $sessionPath
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}