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

// Ensure auth is available
global $auth;
if (!isset($auth)) {
    $auth = new \FocusedTube\Auth();
}
$isLoggedIn = $auth->isLoggedIn();
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
                        <a href="#" aria-label="GitHub">🐙</a>
                        <a href="#" aria-label="Twitter">🐦</a>
                        <a href="#" aria-label="YouTube">📺</a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Browse</h4>
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="/categories">Categories</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Account</h4>
                    <ul>
                        <?php if ($isLoggedIn): ?>
                            <li><a href="/history">History</a></li>
                            <li><a href="/favorites">Favorites</a></li>
                            <li><a href="/playlists">Playlists</a></li>
                            <li><a href="/watch-later">Watch Later</a></li>
                        <?php else: ?>
                            <li><a href="/admin/login">Sign In</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="/about">About</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                <p>Powered by FocusedTube v<?php echo APP_VERSION; ?></p>
                <p><?php echo $videoCount ?? 0; ?> videos in library</p>
            </div>
        </div>
    </footer>
    
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <!-- JavaScript -->
     <!-- JavaScript -->
<script src="/assets/js/main.js" type="module"></script>
<script src="/assets/js/header.js"></script>
<?php if (isset($extraScripts)) echo $extraScripts; ?>
    <script src="/assets/js/main.js" type="module"></script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
    
    <script>
    // Toggle dropdown
    function toggleDropdown(id) {
        const menu = document.getElementById(id);
        if (menu) {
            menu.classList.toggle('active');
        }
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
            if (!menu.parentElement.contains(e.target)) {
                menu.classList.remove('active');
            }
        });
    });
    </script>
</body>
</html>