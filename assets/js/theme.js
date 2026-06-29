/**
 * FocusedTube - Theme Manager
 * 
 * Manages light/dark theme switching and persistence
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

export default class ThemeManager {
    constructor() {
        this.currentTheme = this.getThemePreference();
        this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        this.setupThemeToggle();
        this.setupSystemThemeListener();
        this.applyTheme(this.currentTheme);
    }
    
    /**
     * Get user's theme preference
     * 
     * @returns {string}
     */
    getThemePreference() {
        // Check localStorage
        const storedTheme = localStorage.getItem('focusedtube-theme');
        if (storedTheme) {
            return storedTheme;
        }
        
        // Check system preference
        if (this.mediaQuery.matches) {
            return 'dark';
        }
        
        // Default to light
        return 'light';
    }
    
    /**
     * Apply theme to the document
     * 
     * @param {string} theme
     */
    applyTheme(theme) {
        this.currentTheme = theme;
        
        // Set data attribute on HTML element
        document.documentElement.setAttribute('data-theme', theme);
        
        // Update meta theme-color
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.content = theme === 'dark' ? '#0F172A' : '#FFFFFF';
        }
        
        // Update localStorage
        localStorage.setItem('focusedtube-theme', theme);
        
        // Update toggle button icon
        this.updateToggleIcon(theme);
        
        // Dispatch event for other components
        const event = new CustomEvent('theme-change', { 
            detail: { theme: theme } 
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Toggle theme
     */
    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(newTheme);
        
        // Analytics
        this.trackThemeChange(newTheme);
    }
    
    /**
     * Update theme toggle button icon
     * 
     * @param {string} theme
     */
    updateToggleIcon(theme) {
        const toggle = document.querySelector('.theme-toggle');
        if (toggle) {
            const icon = toggle.querySelector('i, svg, span');
            if (icon) {
                if (theme === 'dark') {
                    icon.innerHTML = '🌙';
                } else {
                    icon.innerHTML = '☀️';
                }
            }
        }
    }
    
    /**
     * Setup theme toggle button
     */
    setupThemeToggle() {
        const toggle = document.querySelector('.theme-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => {
                this.toggleTheme();
            });
        }
    }
    
    /**
     * Listen for system theme changes
     */
    setupSystemThemeListener() {
        this.mediaQuery.addEventListener('change', (e) => {
            // Only change if user hasn't explicitly chosen a theme
            if (!localStorage.getItem('focusedtube-theme')) {
                const theme = e.matches ? 'dark' : 'light';
                this.applyTheme(theme);
            }
        });
    }
    
    /**
     * Track theme changes for analytics
     * 
     * @param {string} theme
     */
    trackThemeChange(theme) {
        // Send analytics event
        if (window.gtag) {
            window.gtag('event', 'theme_change', {
                'theme': theme,
                'timestamp': new Date().toISOString()
            });
        }
    }
    
    /**
     * Get current theme
     * 
     * @returns {string}
     */
    getCurrentTheme() {
        return this.currentTheme;
    }
    
    /**
     * Check if dark mode is active
     * 
     * @returns {boolean}
     */
    isDarkMode() {
        return this.currentTheme === 'dark';
    }
    
    /**
     * Get CSS custom properties for the current theme
     * 
     * @returns {Object}
     */
    getThemeProperties() {
        const computedStyle = getComputedStyle(document.documentElement);
        return {
            primary: computedStyle.getPropertyValue('--primary-color').trim(),
            secondary: computedStyle.getPropertyValue('--secondary-color').trim(),
            background: computedStyle.getPropertyValue('--bg-primary').trim(),
            text: computedStyle.getPropertyValue('--text-primary').trim()
        };
    }
}

// Initialize theme manager on import
document.addEventListener('DOMContentLoaded', () => {
    const themeManager = new ThemeManager();
    window.themeManager = themeManager;
});