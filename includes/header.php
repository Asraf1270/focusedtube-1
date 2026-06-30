<?php
/**
 * FocusedTube - Public Header
 * 
 * Header template for public-facing pages
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;

// Ensure auth is available
global $auth;
if (!isset($auth)) {
    $auth = new \FocusedTube\Auth();
}

$currentUser = $auth->getUser();
$isLoggedIn = $auth->isLoggedIn();
$isAdmin = $auth->hasRole('admin');

// Get theme preference
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : DEFAULT_THEME;

// Generate CSRF token
$csrfToken = $_SESSION['csrf_token'] ?? Security::generateCsrfToken();

// Set default meta if not set
$metaTitle = $metaTitle ?? APP_NAME;
$metaDescription = $metaDescription ?? APP_DESCRIPTION;
$metaImage = $metaImage ?? SITE_URL . '/assets/images/logo.png';
$canonicalUrl = $canonicalUrl ?? SITE_URL . $_SERVER['REQUEST_URI'];

// Get video count
global $db;
$videos = $db->read('videos.json');
$videoCount = count($videos);

// Get categories
$categories = $db->read('categories.json');
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?php echo Security::escapeHtml($metaTitle); ?></title>
    <meta name="description" content="<?php echo Security::escapeHtml($metaDescription); ?>">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo Security::escapeHtml($canonicalUrl); ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo Security::escapeHtml($metaTitle); ?>">
    <meta property="og:description" content="<?php echo Security::escapeHtml($metaDescription); ?>">
    <meta property="og:image" content="<?php echo Security::escapeHtml($metaImage); ?>">
    <meta property="og:url" content="<?php echo Security::escapeHtml($canonicalUrl); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo APP_NAME; ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo Security::escapeHtml($metaTitle); ?>">
    <meta name="twitter:description" content="<?php echo Security::escapeHtml($metaDescription); ?>">
    <meta name="twitter:image" content="<?php echo Security::escapeHtml($metaImage); ?>">
    
    <!-- Icons -->
    <link rel="icon" href="/assets/images/favicon.ico" sizes="any">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <?php if ($theme === 'dark'): ?>
        <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <?php endif; ?>
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://www.youtube.com">
    <link rel="preconnect" href="https://i.ytimg.com">
    <link rel="preconnect" href="https://www.googleapis.com">
    
    <!-- Structured Data -->
    <?php if (isset($structuredData)): ?>
        <script type="application/ld+json">
            <?php echo json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
    <?php endif; ?>
</head>
<body>
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Navigation -->
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="container">
            <div class="navbar-container">
                <!-- Brand/Logo -->
                <a href="/" class="navbar-brand" aria-label="<?php echo APP_NAME; ?> Home">
                    <span style="font-size: 28px;">🎬</span>
                    <span class="brand-text"><?php echo APP_NAME; ?></span>
                </a>
                
                <!-- Search -->
                <div class="navbar-search" role="search">
                    <form action="/search" method="GET" autocomplete="off">
                        <input type="search" name="q" 
                               placeholder="Search videos..." 
                               aria-label="Search for videos"
                               value="<?php echo isset($_GET['q']) ? Security::escapeHtml($_GET['q']) : ''; ?>">
                        <button type="submit" class="search-btn" aria-label="Submit search">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </button>
                    </form>
                    <div class="search-suggestions" style="display: none;"></div>
                </div>
                
                <!-- Actions -->
                <div class="navbar-actions">
                    <button class="theme-toggle" aria-label="Toggle theme" title="Toggle theme">
                        <?php echo $theme === 'dark' ? '🌙' : '☀️'; ?>
                    </button>
                    
                    <?php if ($isLoggedIn): ?>
                        <a href="/watch-later" class="btn-icon" title="Watch Later">⏰</a>
                        <a href="/favorites" class="btn-icon" title="Favorites">⭐</a>
                        <div class="dropdown">
                            <button class="btn-icon" onclick="toggleDropdown('userDropdown')" aria-label="User menu" title="User menu">
                                <div class="user-avatar" style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">
                                    <?php echo strtoupper(substr($currentUser['username'] ?? $currentUser['email'] ?? 'U', 0, 1)); ?>
                                </div>
                            </button>
                            <div id="userDropdown" class="dropdown-menu">
                                <div style="padding: var(--spacing-sm) var(--spacing-md);">
                                    <div style="font-weight: var(--font-semibold);"><?php echo Security::escapeHtml($currentUser['username'] ?? $currentUser['email']); ?></div>
                                    <div style="font-size: var(--font-size-xs); color: var(--text-tertiary);"><?php echo Security::escapeHtml($currentUser['email']); ?></div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="/history">📜 Watch History</a>
                                <a href="/favorites">⭐ Favorites</a>
                                <a href="/watch-later">⏰ Watch Later</a>
                                <a href="/playlists">📋 Playlists</a>
                                <?php if ($isAdmin): ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="/admin/dashboard.php" style="color: var(--primary-color);">⚙️ Admin Panel</a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">🚪 Logout</a>
                                <form id="logout-form" action="/logout" method="POST" style="display: none;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/admin/login" class="btn btn-primary btn-sm">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main id="main-content" class="main-content">