<?php
/**
 * FocusedTube - Public Footer
 * 
 * Footer template for public-facing pages
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;
?>
    </main>
    
    <!-- Footer -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h4><?php echo APP_NAME; ?></h4>
                    <p class="footer-description">
                        Self-hosted YouTube video library. Watch, organize, and discover videos without distractions.
                    </p>
                    <div class="footer-social">
                        <a href="#" aria-label="GitHub"><span>🐙</span></a>
                        <a href="#" aria-label="Twitter"><span>🐦</span></a>
                        <a href="#" aria-label="YouTube"><span>📺</span></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Browse</h4>
                    <ul>
                        <li><a href="/trending">Trending</a></li>
                        <li><a href="/popular">Popular</a></li>
                        <li><a href="/latest">Latest</a></li>
                        <li><a href="/categories">Categories</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Account</h4>
                    <ul>
                        <?php if ($isLoggedIn ?? false): ?>
                            <li><a href="/history">History</a></li>
                            <li><a href="/favorites">Favorites</a></li>
                            <li><a href="/playlists">Playlists</a></li>
                            <li><a href="/watch-later">Watch Later</a></li>
                        <?php else: ?>
                            <li><a href="/admin">Sign In</a></li>
                            <li><a href="/about">About</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="/about">About</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/privacy">Privacy Policy</a></li>
                        <li><a href="/terms">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                <p>Powered by FocusedTube v<?php echo APP_VERSION; ?></p>
            </div>
        </div>
    </footer>
    
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <!-- JavaScript -->
    <script src="/assets/js/main.js" type="module"></script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
    
    <!-- Service Worker Registration -->
    <?php if (ENVIRONMENT === 'production'): ?>
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js');
                });
            }
        </script>
    <?php endif; ?>
    <?php
/**
 * FocusedTube - Additional Helper Functions
 * 
 * Global helper functions for the application
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

// ... existing functions ...

/**
 * Format duration for display
 * 
 * @param int $seconds
 * @return string
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
    return sprintf('%02d:%02d', $minutes, $seconds);
}

/**
 * Format number with K/M suffixes
 * 
 * @param int $number
 * @return string
 */
function formatNumber($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    }
    if ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return (string)$number;
}

/**
 * Get time ago string
 * 
 * @param string $timestamp
 * @return string
 */
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . 'm ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . 'd ago';
    }
    if ($diff < 2592000) {
        return floor($diff / 604800) . 'w ago';
    }
    if ($diff < 31536000) {
        return floor($diff / 2592000) . 'mo ago';
    }
    return floor($diff / 31536000) . 'y ago';
}

/**
 * Get video embed URL
 * 
 * @param string $videoId
 * @param array $params
 * @return string
 */
function getVideoEmbedUrl($videoId, $params = []) {
    $defaults = [
        'autoplay' => 0,
        'rel' => 0,
        'modestbranding' => 1
    ];
    $params = array_merge($defaults, $params);
    $query = http_build_query($params);
    return "https://www.youtube.com/embed/{$videoId}?{$query}";
}

/**
 * Generate slug from string
 * 
 * @param string $string
 * @return string
 */
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Truncate text with ellipsis
 * 
 * @param string $text
 * @param int $length
 * @return string
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}
?>
</body>
</html>