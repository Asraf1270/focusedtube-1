<?php
/**
 * FocusedTube - PWA Helper Functions
 * 
 * Handles PWA manifest, service worker registration, and push notifications
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube;

class PWA
{
    private $db;
    private $auth;
    
    public function __construct()
    {
        global $db, $auth;
        $this->db = $db;
        $this->auth = $auth;
    }
    
    /**
     * Register service worker
     * 
     * @return string
     */
    public function registerServiceWorker()
    {
        return <<<HTML
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js')
                        .then(function(registration) {
                            console.log('Service Worker registered successfully');
                        })
                        .catch(function(error) {
                            console.error('Service Worker registration failed:', error);
                        });
                });
            }
        </script>
        HTML;
    }
    
    /**
     * Get PWA manifest link
     * 
     * @return string
     */
    public function getManifestLink()
    {
        if (file_exists(__DIR__ . '/../manifest.json')) {
            return '<link rel="manifest" href="/manifest.json">';
        }
        return '';
    }
    
    /**
     * Get Apple touch icon
     * 
     * @return string
     */
    public function getAppleTouchIcon()
    {
        return '<link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">';
    }
    
    /**
     * Get theme color meta tag
     * 
     * @return string
     */
    public function getThemeColor()
    {
        $theme = $this->getCurrentTheme();
        $color = $theme === 'dark' ? '#0F172A' : '#FFFFFF';
        return '<meta name="theme-color" content="' . $color . '">';
    }
    
    /**
     * Get current theme
     * 
     * @return string
     */
    private function getCurrentTheme()
    {
        return isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
    }
    
    /**
     * Get app install banner
     * 
     * @return string
     */
    public function getAppBanner()
    {
        // Check if PWA is already installed
        if ($this->isPWAInstalled()) {
            return '';
        }
        
        // Check if banner was dismissed
        if (isset($_COOKIE['pwa_banner_dismissed'])) {
            return '';
        }
        
        return <<<HTML
        <div id="pwa-banner" class="pwa-banner" style="display:none;">
            <div class="pwa-banner-content">
                <img src="/assets/icons/icon-192x192.png" alt="FocusedTube" class="pwa-icon" 
                     onerror="this.style.display='none'">
                <div class="pwa-info">
                    <strong>FocusedTube</strong>
                    <span>Install app for better experience</span>
                </div>
                <button onclick="installPWA()" class="btn btn-primary btn-sm">Install</button>
                <button onclick="closePWA()" class="pwa-close">×</button>
            </div>
        </div>
        <style>
            .pwa-banner {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: var(--bg-primary);
                border-top: 1px solid var(--border-color);
                padding: var(--spacing-md);
                z-index: var(--z-toast);
                box-shadow: 0 -4px 12px var(--shadow-color);
                display: none;
                animation: slideUp 0.3s ease;
            }
            .pwa-banner.show {
                display: block !important;
            }
            .pwa-banner-content {
                display: flex;
                align-items: center;
                gap: var(--spacing-md);
                max-width: var(--container-xl);
                margin: 0 auto;
            }
            .pwa-icon {
                width: 48px;
                height: 48px;
                border-radius: var(--radius-sm);
            }
            .pwa-info {
                flex: 1;
            }
            .pwa-info strong {
                display: block;
                font-size: var(--font-size-md);
                color: var(--text-primary);
            }
            .pwa-info span {
                font-size: var(--font-size-sm);
                color: var(--text-secondary);
            }
            .pwa-close {
                background: none;
                border: none;
                font-size: var(--font-size-xl);
                cursor: pointer;
                color: var(--text-tertiary);
                padding: 0 var(--spacing-sm);
            }
            .pwa-close:hover {
                color: var(--text-primary);
            }
            @keyframes slideUp {
                from { transform: translateY(100%); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            @media (max-width: 640px) {
                .pwa-banner { padding: var(--spacing-sm); }
                .pwa-icon { width: 36px; height: 36px; }
                .pwa-info strong { font-size: var(--font-size-sm); }
                .pwa-info span { font-size: var(--font-size-xs); }
            }
        </style>
        <script>
            let deferredPrompt;
            
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                const banner = document.getElementById('pwa-banner');
                if (banner) {
                    banner.classList.add('show');
                }
            });
            
            window.installPWA = function() {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                            document.getElementById('pwa-banner').classList.remove('show');
                        }
                        deferredPrompt = null;
                    });
                }
            };
            
            window.closePWA = function() {
                document.getElementById('pwa-banner').classList.remove('show');
                document.cookie = 'pwa_banner_dismissed=true; path=/; max-age=2592000';
            };
            
            // Check if already installed
            if (window.matchMedia('(display-mode: standalone)').matches) {
                document.getElementById('pwa-banner').classList.remove('show');
            }
        </script>
        HTML;
    }
    
    /**
     * Check if PWA is installed
     * 
     * @return bool
     */
    private function isPWAInstalled()
    {
        // Check if running in standalone mode
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
            // Check for standalone mode indicators
            if (strpos($ua, 'standalone') !== false || 
                strpos($ua, 'mobile-app') !== false) {
                return true;
            }
        }
        
        // Check for display mode via JavaScript (will be handled client-side)
        return false;
    }
    
    /**
     * Get PWA status
     * 
     * @return array
     */
    public function getPWAStatus()
    {
        return [
            'service_worker' => file_exists(__DIR__ . '/../sw.js'),
            'manifest' => file_exists(__DIR__ . '/../manifest.json'),
            'offline_page' => file_exists(__DIR__ . '/../offline.html'),
            'cache_size' => $this->formatBytes($this->getCacheSize()),
            'is_installed' => $this->isPWAInstalled(),
            'has_push_support' => $this->hasPushSupport()
        ];
    }
    
    /**
     * Get cache size
     * 
     * @return int
     */
    private function getCacheSize()
    {
        $cachePath = CACHE_PATH;
        $size = 0;
        
        if (!is_dir($cachePath)) {
            return 0;
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cachePath)
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    /**
     * Check push support
     * 
     * @return bool
     */
    private function hasPushSupport()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) && 
               strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false;
    }
    
    /**
     * Format bytes
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}