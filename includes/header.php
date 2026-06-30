<?php
/**
 * FocusedTube - Public Header
 * 
 * Header template for public-facing pages with mobile hamburger menu and PWA support
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

// Initialize PWA only if class exists
$pwa = null;
if (class_exists('FocusedTube\PWA')) {
    $pwa = new \FocusedTube\PWA();
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
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?php echo Security::escapeHtml($metaTitle); ?></title>
    <meta name="description" content="<?php echo Security::escapeHtml($metaDescription); ?>">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    
    <!-- PWA Meta Tags (only if PWA class exists) -->
    <?php if ($pwa): ?>
        <?php echo $pwa->getManifestLink(); ?>
        <?php echo $pwa->getAppleTouchIcon(); ?>
        <?php echo $pwa->getThemeColor(); ?>
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="FocusedTube">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="application-name" content="FocusedTube">
    <?php endif; ?>
    
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
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="stylesheet" href="/assets/css/header.css">
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
    
    <!-- PWA Service Worker (only if PWA class exists) -->
    <?php if ($pwa): ?>
        <?php echo $pwa->registerServiceWorker(); ?>
    <?php endif; ?>
</head>
<body>
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- PWA Install Banner (only if PWA class exists) -->
    <?php if ($pwa): ?>
        <?php echo $pwa->getAppBanner(); ?>
    <?php endif; ?>
    
    <!-- Navigation -->
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="container">
            <div class="navbar-container">
                <!-- Brand/Logo -->
                <a href="/" class="navbar-brand" aria-label="<?php echo APP_NAME; ?> Home">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1536 1024" style="width: 36px; height: 36px;">
                        <rect x="25" y="20" width="1486" height="984" rx="180" ry="180" fill="#FF0000"/>
                        <circle cx="768" cy="512" r="200" fill="none" stroke="#F2F2F2" stroke-width="55"/>
                        <path fill="#F2F2F2" d="M882 90 C822 90 782 150 782 260 L782 760 C782 870 742 930 672 930 L622 930 L622 855 L672 855 C707 855 727 830 727 760 L727 260 C727 120 802 35 907 35 L977 35 L977 110 L907 110 C887 110 882 115 882 140 Z"/>
                    </svg>
                    <span class="brand-text">
                        <span class="highlight">Focused</span>Tube
                    </span>
                </a>
                
                <!-- Search -->
                <div class="navbar-search" role="search">
                    <form action="/search" method="GET" autocomplete="off">
                        <input type="search" name="q" 
                               placeholder="Search videos..." 
                               aria-label="Search for videos"
                               value="<?php echo isset($_GET['q']) ? Security::escapeHtml($_GET['q']) : ''; ?>">
                        <button type="submit" class="search-btn" aria-label="Submit search">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </button>
                    </form>
                    <div class="search-suggestions" style="display: none;"></div>
                </div>
                
                <!-- Desktop Actions -->
                <div class="navbar-actions desktop-actions">
                    <button class="theme-toggle" aria-label="Toggle theme" title="Toggle theme">
                        <?php echo $theme === 'dark' ? '🌙' : '☀️'; ?>
                    </button>
                    
                    <!-- PWA Install Button (Desktop) -->
                    <button id="pwa-install-btn" class="btn btn-primary btn-sm pwa-install-btn" style="display: none;">
                        📱 Install App
                    </button>
                    
                    <?php if ($isLoggedIn): ?>
                        <a href="/watch-later" class="btn-icon" title="Watch Later" aria-label="Watch Later">⏰</a>
                        <a href="/favorites" class="btn-icon" title="Favorites" aria-label="Favorites">⭐</a>
                        <div class="dropdown">
                            <button class="btn-icon" onclick="toggleDropdown('userDropdown')" aria-label="User menu" title="User menu">
                                <div class="user-avatar">
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
                
                <!-- Mobile Hamburger Menu Toggle -->
                <button class="hamburger-toggle" id="hamburgerToggle" aria-label="Toggle menu" aria-expanded="false">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            </div>
            
            <!-- Mobile Menu (Hidden by default) -->
            <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
                <div class="mobile-menu-content">
                    <!-- Mobile Search -->
                    <div class="mobile-search">
                        <form action="/search" method="GET">
                            <input type="search" name="q" 
                                   placeholder="Search videos..." 
                                   aria-label="Search for videos"
                                   value="<?php echo isset($_GET['q']) ? Security::escapeHtml($_GET['q']) : ''; ?>">
                            <button type="submit" aria-label="Submit search">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Mobile Navigation Links -->
                    <nav class="mobile-nav" aria-label="Mobile navigation">
                        <a href="/" class="mobile-nav-link <?php echo $_SERVER['REQUEST_URI'] === '/' ? 'active' : ''; ?>">
                            🏠 Home
                        </a>
                        <a href="/categories" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/categories') !== false ? 'active' : ''; ?>">
                            🏷️ Categories
                        </a>
                        <a href="/trending" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/trending') !== false ? 'active' : ''; ?>">
                            🔥 Trending
                        </a>
                        <a href="/popular" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/popular') !== false ? 'active' : ''; ?>">
                            📈 Popular
                        </a>
                        <a href="/latest" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/latest') !== false ? 'active' : ''; ?>">
                            🆕 Latest
                        </a>
                    </nav>
                    
                    <!-- Mobile User Actions -->
                    <div class="mobile-user-actions">
                        <?php if ($isLoggedIn): ?>
                            <div class="mobile-user-info">
                                <div class="mobile-user-avatar">
                                    <?php echo strtoupper(substr($currentUser['username'] ?? $currentUser['email'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="mobile-user-name"><?php echo Security::escapeHtml($currentUser['username'] ?? $currentUser['email']); ?></div>
                                    <div class="mobile-user-email"><?php echo Security::escapeHtml($currentUser['email']); ?></div>
                                </div>
                            </div>
                            <div class="mobile-user-links">
                                <a href="/history">📜 Watch History</a>
                                <a href="/favorites">⭐ Favorites</a>
                                <a href="/watch-later">⏰ Watch Later</a>
                                <a href="/playlists">📋 Playlists</a>
                                <?php if ($isAdmin): ?>
                                    <a href="/admin/dashboard.php" style="color: var(--primary-color);">⚙️ Admin Panel</a>
                                <?php endif; ?>
                                <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();">🚪 Logout</a>
                                <form id="logout-form-mobile" action="/logout" method="POST" style="display: none;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                </form>
                            </div>
                        <?php else: ?>
                            <a href="/admin/login" class="btn btn-primary btn-block">Sign In</a>
                            <p class="mobile-signup-text">Don't have an account? Contact the administrator.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mobile Theme Toggle -->
                    <div class="mobile-theme-toggle">
                        <button class="theme-toggle" aria-label="Toggle theme">
                            <?php echo $theme === 'dark' ? '🌙 Dark Mode' : '☀️ Light Mode'; ?>
                        </button>
                    </div>
                    
                    <!-- PWA Install Button (Mobile) -->
                    <button id="pwa-install-btn-mobile" class="btn btn-primary btn-block pwa-install-btn" style="display: none;">
                        📱 Install App
                    </button>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main id="main-content" class="main-content">