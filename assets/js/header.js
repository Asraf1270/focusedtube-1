/**
 * FocusedTube - Header JavaScript
 * 
 * Handles mobile hamburger menu and PWA install button
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // HAMBURGER MENU
    // ============================================
    
    const hamburgerToggle = document.getElementById('hamburgerToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const body = document.body;
    
    if (hamburgerToggle && mobileMenu) {
        // Toggle menu on hamburger click
        hamburgerToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = mobileMenu.classList.toggle('open');
            hamburgerToggle.classList.toggle('active');
            hamburgerToggle.setAttribute('aria-expanded', isOpen);
            mobileMenu.setAttribute('aria-hidden', !isOpen);
            body.style.overflow = isOpen ? 'hidden' : '';
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (mobileMenu.classList.contains('open') && 
                !mobileMenu.contains(e.target) && 
                !hamburgerToggle.contains(e.target)) {
                mobileMenu.classList.remove('open');
                hamburgerToggle.classList.remove('active');
                hamburgerToggle.setAttribute('aria-expanded', 'false');
                mobileMenu.setAttribute('aria-hidden', 'true');
                body.style.overflow = '';
            }
        });
        
        // Close menu on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu.classList.contains('open')) {
                mobileMenu.classList.remove('open');
                hamburgerToggle.classList.remove('active');
                hamburgerToggle.setAttribute('aria-expanded', 'false');
                mobileMenu.setAttribute('aria-hidden', 'true');
                body.style.overflow = '';
                hamburgerToggle.focus();
            }
        });
        
        // Close menu on window resize (desktop)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && mobileMenu.classList.contains('open')) {
                mobileMenu.classList.remove('open');
                hamburgerToggle.classList.remove('active');
                hamburgerToggle.setAttribute('aria-expanded', 'false');
                mobileMenu.setAttribute('aria-hidden', 'true');
                body.style.overflow = '';
            }
        });
    }
    
    // ============================================
    // PWA INSTALL BUTTON
    // ============================================
    
    let deferredPrompt;
    const installBtns = document.querySelectorAll('.pwa-install-btn');
    
    // Listen for beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;
        
        // Show install buttons
        installBtns.forEach(btn => {
            btn.classList.add('visible');
            btn.style.display = 'inline-flex';
        });
    });
    
    // Handle install button clicks
    installBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function(choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User installed the app');
                        installBtns.forEach(b => {
                            b.classList.remove('visible');
                            b.style.display = 'none';
                        });
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
        });
    });
    
    // Hide install buttons if already installed
    window.addEventListener('appinstalled', function() {
        installBtns.forEach(btn => {
            btn.classList.remove('visible');
            btn.style.display = 'none';
        });
    });
    
    // Check if already running as PWA
    if (window.matchMedia('(display-mode: standalone)').matches) {
        installBtns.forEach(btn => {
            btn.classList.remove('visible');
            btn.style.display = 'none';
        });
    }
    
    // ============================================
    // DROPDOWN MENU
    // ============================================
    
    // Toggle dropdown
    window.toggleDropdown = function(id) {
        const menu = document.getElementById(id);
        if (menu) {
            const isOpen = menu.classList.toggle('active');
            menu.setAttribute('aria-hidden', !isOpen);
        }
    };
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
            if (!menu.parentElement.contains(e.target)) {
                menu.classList.remove('active');
                menu.setAttribute('aria-hidden', 'true');
            }
        });
    });
    
    // Close dropdown on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown-menu.active').forEach(function(menu) {
                menu.classList.remove('active');
                menu.setAttribute('aria-hidden', 'true');
            });
        }
    });
});