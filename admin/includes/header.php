<?php
/**
 * FocusedTube - Admin Header
 * 
 * Header template for admin panel
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Get user info
$userEmail = Security::escapeHtml($_SESSION['user_email'] ?? '');
$userName = Security::escapeHtml($_SESSION['user_name'] ?? $userEmail);
$userInitial = strtoupper(substr($userName, 0, 1));

// Generate CSRF token
$csrfToken = $_SESSION['csrf_token'] ?? Security::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo isset($_COOKIE['admin_theme']) ? $_COOKIE['admin_theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? Security::escapeHtml($pageTitle) : 'FocusedTube Admin'; ?></title>
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="icon" href="/assets/images/favicon.ico">
    <?php if (isset($extraStyles)) echo $extraStyles; ?>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar Overlay (mobile) -->
        <div class="admin-sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-sidebar-brand">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1536 1024" style="width: 32px; height: 32px;">
                    <rect x="25" y="20" width="1486" height="984" rx="180" ry="180" fill="#FF0000"/>
                    <circle cx="768" cy="512" r="200" fill="none" stroke="#F2F2F2" stroke-width="55"/>
                    <path fill="#F2F2F2" d="M882 90 C822 90 782 150 782 260 L782 760 C782 870 742 930 672 930 L622 930 L622 855 L672 855 C707 855 727 830 727 760 L727 260 C727 120 802 35 907 35 L977 35 L977 110 L907 110 C887 110 882 115 882 140 Z"/>
                </svg>
                <span><span class="brand-text">Focused</span>Tube</span>
            </div>
            
            <ul class="admin-sidebar-menu">
                <li class="menu-label">Main</li>
                <li class="menu-item">
                    <a href="dashboard.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="menu-item">
                    <a href="videos.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'videos.php' ? 'active' : ''; ?>">
                        <span class="icon">📹</span>
                        Videos
                    </a>
                </li>
                <li class="menu-item">
                    <a href="users.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                        <span class="icon">👥</span>
                        Users
                    </a>
                </li>
                
                <li class="menu-divider"></li>
                <li class="menu-label">Content</li>
                <li class="menu-item">
                    <a href="categories.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
                        <span class="icon">🏷️</span>
                        Categories
                    </a>
                </li>
                <li class="menu-item">
                    <a href="tags.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'tags.php' ? 'active' : ''; ?>">
                        <span class="icon">🔖</span>
                        Tags
                    </a>
                </li>
                <li class="menu-item">
                    <a href="announcements.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'announcements.php' ? 'active' : ''; ?>">
                        <span class="icon">📢</span>
                        Announcements
                    </a>
                </li>
                
                <li class="menu-divider"></li>
                <li class="menu-label">System</li>
                <li class="menu-item">
                    <a href="settings.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                        <span class="icon">⚙️</span>
                        Settings
                    </a>
                </li>
                <li class="menu-item">
                    <a href="api-settings.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'api-settings.php' ? 'active' : ''; ?>">
                        <span class="icon">🔌</span>
                        API Settings
                    </a>
                </li>
                <li class="menu-item">
                    <a href="analytics.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : ''; ?>">
                        <span class="icon">📈</span>
                        Analytics
                    </a>
                </li>
                <li class="menu-item">
                    <a href="reports.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                        <span class="icon">📋</span>
                        Reports
                    </a>
                </li>
                <li class="menu-item">
                    <a href="activity.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'activity.php' ? 'active' : ''; ?>">
                        <span class="icon">📝</span>
                        Activity Log
                    </a>
                </li>
                
                <li class="menu-divider"></li>
                <li class="menu-label">Maintenance</li>
                <li class="menu-item">
                    <a href="backups.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'backups.php' ? 'active' : ''; ?>">
                        <span class="icon">💾</span>
                        Backups
                    </a>
                </li>
                <li class="menu-item">
                    <a href="maintenance.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) === 'maintenance.php' ? 'active' : ''; ?>">
                        <span class="icon">🔧</span>
                        Maintenance
                    </a>
                </li>
                
                <li class="menu-divider"></li>
                <li class="menu-item">
                    <a href="logout.php" class="menu-link">
                        <span class="icon">🚪</span>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="admin-header-left">
                    <button class="toggle-sidebar" id="toggleSidebar" aria-label="Toggle sidebar">
                        ☰
                    </button>
                    <h2 class="admin-header-title">
                        <?php echo isset($pageTitle) ? Security::escapeHtml(str_replace(' - FocusedTube Admin', '', $pageTitle)) : 'Dashboard'; ?>
                    </h2>
                </div>
                
                <div class="admin-header-right">
                    <button class="theme-toggle" aria-label="Toggle theme" onclick="toggleAdminTheme()">
                        <?php echo isset($_COOKIE['admin_theme']) && $_COOKIE['admin_theme'] === 'dark' ? '🌙' : '☀️'; ?>
                    </button>
                    
                    <div class="admin-user">
                        <div class="avatar"><?php echo $userInitial; ?></div>
                        <div>
                            <div class="name"><?php echo $userName; ?></div>
                            <div class="role">Administrator</div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash'])): ?>
                <div class="admin-content" style="padding-bottom: 0;">
                    <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                        <div class="alert alert-<?php echo $type; ?> fade-in">
                            <?php echo Security::escapeHtml($message); ?>
                            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                        </div>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['flash']); ?>
                </div>
            <?php endif; ?>