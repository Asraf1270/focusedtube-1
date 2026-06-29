/**
 * FocusedTube - Main JavaScript
 * 
 * Core functionality for the entire application
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

import ThemeManager from './theme.js';
import { showToast, debounce, getElement, formatDuration } from './utils.js';
import { API } from './api.js';

class FocusedTube {
    constructor() {
        this.theme = new ThemeManager();
        this.api = new API();
        this.videos = [];
        this.currentPage = 1;
        this.loading = false;
        this.hasMore = true;
        this.infiniteScrollEnabled = true;
        this.searchDebounce = debounce(this.handleSearch.bind(this), 300);
        
        this.init();
    }
    
    /**
     * Initialize the application
     */
    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupNavigation();
            this.setupSearch();
            this.setupInfiniteScroll();
            this.setupKeyboardShortcuts();
            this.setupLazyLoading();
            this.setupVideoCards();
            this.setupComments();
            this.loadInitialVideos();
        });
    }
    
    /**
     * Setup navigation
     */
    setupNavigation() {
        const navbar = document.querySelector('.navbar');
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', () => {
                const menu = document.querySelector('.navbar-menu');
                if (menu) {
                    menu.classList.toggle('active');
                }
            });
        }
        
        // Close mobile menu on resize
        window.addEventListener('resize', () => {
            const menu = document.querySelector('.navbar-menu');
            if (menu && window.innerWidth > 768) {
                menu.classList.remove('active');
            }
        });
        
        // Close mobile menu on click outside
        document.addEventListener('click', (e) => {
            const menu = document.querySelector('.navbar-menu');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (menu && toggle && 
                !menu.contains(e.target) && 
                !toggle.contains(e.target)) {
                menu.classList.remove('active');
            }
        });
    }
    
    /**
     * Setup search functionality
     */
    setupSearch() {
        const searchInput = document.querySelector('.navbar-search input');
        const searchForm = document.querySelector('.navbar-search form');
        
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchDebounce(e.target.value);
            });
            
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.performSearch(searchInput.value);
                }
            });
        }
        
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const input = searchForm.querySelector('input');
                if (input) {
                    this.performSearch(input.value);
                }
            });
        }
    }
    
    /**
     * Handle search with debouncing
     * 
     * @param {string} query
     */
    handleSearch(query) {
        if (query.length < 2) {
            return;
        }
        
        // Show search suggestions
        this.showSearchSuggestions(query);
    }
    
    /**
     * Perform search
     * 
     * @param {string} query
     */
    async performSearch(query) {
        if (!query || query.length < 2) {
            return;
        }
        
        try {
            const results = await this.api.searchVideos(query);
            this.displayVideos(results);
            this.updateUrlParams({ search: query });
        } catch (error) {
            console.error('Search error:', error);
            showToast('error', 'Failed to perform search');
        }
    }
    
    /**
     * Show search suggestions
     * 
     * @param {string} query
     */
    async showSearchSuggestions(query) {
        try {
            const suggestions = await this.api.getSearchSuggestions(query);
            this.renderSuggestions(suggestions);
        } catch (error) {
            console.error('Failed to get suggestions:', error);
        }
    }
    
    /**
     * Render search suggestions
     * 
     * @param {Array} suggestions
     */
    renderSuggestions(suggestions) {
        const container = document.querySelector('.search-suggestions');
        if (!container) {
            return;
        }
        
        if (!suggestions || suggestions.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.innerHTML = suggestions.map(s => `
            <div class="suggestion-item" data-query="${s}">
                <i data-feather="search"></i>
                <span>${s}</span>
            </div>
        `).join('');
        
        container.style.display = 'block';
        
        // Add click handlers
        container.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                const query = item.dataset.query;
                const searchInput = document.querySelector('.navbar-search input');
                if (searchInput) {
                    searchInput.value = query;
                }
                this.performSearch(query);
                container.style.display = 'none';
            });
        });
    }
    
    /**
     * Setup infinite scroll
     */
    setupInfiniteScroll() {
        if (!this.infiniteScrollEnabled) {
            return;
        }
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.loading && this.hasMore) {
                    this.loadMoreVideos();
                }
            });
        }, {
            rootMargin: '200px'
        });
        
        const sentinel = document.querySelector('#infinite-scroll-sentinel');
        if (sentinel) {
            observer.observe(sentinel);
        }
    }
    
    /**
     * Load more videos for infinite scroll
     */
    async loadMoreVideos() {
        if (this.loading || !this.hasMore) {
            return;
        }
        
        this.loading = true;
        this.currentPage++;
        
        try {
            const data = await this.api.getVideos(this.currentPage);
            
            if (data.items && data.items.length > 0) {
                this.appendVideos(data.items);
                this.hasMore = data.hasMore;
            } else {
                this.hasMore = false;
            }
        } catch (error) {
            console.error('Failed to load more videos:', error);
            showToast('error', 'Failed to load more videos');
        } finally {
            this.loading = false;
        }
    }
    
    /**
     * Load initial videos
     */
    async loadInitialVideos() {
        try {
            const data = await this.api.getVideos(1);
            if (data.items) {
                this.displayVideos(data.items);
                this.hasMore = data.hasMore;
            }
        } catch (error) {
            console.error('Failed to load videos:', error);
            showToast('error', 'Failed to load videos');
        }
    }
    
    /**
     * Display videos in the grid
     * 
     * @param {Array} videos
     */
    displayVideos(videos) {
        const container = document.querySelector('.videos-grid');
        if (!container) {
            return;
        }
        
        container.innerHTML = videos.map(video => this.createVideoCard(video)).join('');
        this.setupVideoCards();
    }
    
    /**
     * Append videos to existing grid
     * 
     * @param {Array} videos
     */
    appendVideos(videos) {
        const container = document.querySelector('.videos-grid');
        if (!container) {
            return;
        }
        
        videos.forEach(video => {
            container.insertAdjacentHTML('beforeend', this.createVideoCard(video));
        });
        
        this.setupVideoCards();
    }
    
    /**
     * Create video card HTML
     * 
     * @param {Object} video
     * @returns {string}
     */
    createVideoCard(video) {
        const thumbnail = video.thumbnail_url || '/assets/images/default-thumbnail.jpg';
        const duration = video.duration ? formatDuration(video.duration) : '';
        const views = video.view_count ? this.formatNumber(video.view_count) : '0';
        const published = video.published_at ? this.formatDate(video.published_at) : '';
        
        return `
            <div class="video-card" data-id="${video.id}">
                <div class="thumbnail">
                    <img src="${thumbnail}" alt="${this.escapeHtml(video.title)}" loading="lazy">
                    ${duration ? `<span class="duration">${duration}</span>` : ''}
                </div>
                <div class="info">
                    <div class="title">${this.escapeHtml(video.title)}</div>
                    <div class="channel">${this.escapeHtml(video.channel_name)}</div>
                    <div class="meta">
                        <span>${views} views</span>
                        ${published ? `<span>• ${published}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Setup video card click handlers
     */
    setupVideoCards() {
        document.querySelectorAll('.video-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const videoId = card.dataset.id;
                if (videoId) {
                    window.location.href = `/watch?id=${videoId}`;
                }
            });
        });
    }
    
    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // '/' for search focus
            if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                const searchInput = document.querySelector('.navbar-search input');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // '?' for keyboard shortcuts help
            if (e.key === '?' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                this.showKeyboardShortcuts();
            }
            
            // Escape to close modals or blur search
            if (e.key === 'Escape') {
                this.closeModals();
                const searchInput = document.querySelector('.navbar-search input');
                if (searchInput && document.activeElement === searchInput) {
                    searchInput.blur();
                }
            }
        });
    }
    
    /**
     * Show keyboard shortcuts help
     */
    showKeyboardShortcuts() {
        const shortcuts = [
            { key: '/', description: 'Focus search' },
            { key: '?', description: 'Show keyboard shortcuts' },
            { key: 'Escape', description: 'Close modals' },
            { key: 'Space', description: 'Play/Pause video' },
            { key: 'f', description: 'Fullscreen' },
            { key: 'l', description: 'Seek forward 10s' },
            { key: 'j', description: 'Seek backward 10s' },
            { key: 'k', description: 'Play/Pause' }
        ];
        
        const modal = document.getElementById('shortcuts-modal');
        if (!modal) {
            this.createShortcutsModal(shortcuts);
        } else {
            this.updateShortcutsModal(shortcuts);
        }
        
        this.openModal('shortcuts-modal');
    }
    
    /**
     * Create shortcuts modal
     * 
     * @param {Array} shortcuts
     */
    createShortcutsModal(shortcuts) {
        const html = `
            <div id="shortcuts-modal" class="modal-overlay">
                <div class="modal">
                    <div class="modal-header">
                        <h3>Keyboard Shortcuts</h3>
                        <button class="modal-close" onclick="document.getElementById('shortcuts-modal').classList.remove('active')">
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="shortcuts-grid">
                            ${shortcuts.map(s => `
                                <div class="shortcut-item">
                                    <span class="shortcut-key">${s.key}</span>
                                    <span class="shortcut-description">${s.description}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', html);
    }
    
    /**
     * Update shortcuts modal
     * 
     * @param {Array} shortcuts
     */
    updateShortcutsModal(shortcuts) {
        const modal = document.getElementById('shortcuts-modal');
        const body = modal.querySelector('.modal-body');
        
        body.innerHTML = `
            <div class="shortcuts-grid">
                ${shortcuts.map(s => `
                    <div class="shortcut-item">
                        <span class="shortcut-key">${s.key}</span>
                        <span class="shortcut-description">${s.description}</span>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    /**
     * Setup lazy loading for images
     */
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.dataset.src;
                        if (src) {
                            img.src = src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for older browsers
            document.querySelectorAll('img[data-src]').forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        }
    }
    
    /**
     * Setup comments
     */
    setupComments() {
        const commentForm = document.querySelector('.comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const textarea = commentForm.querySelector('textarea');
                const videoId = commentForm.dataset.videoId;
                
                if (!textarea || !textarea.value.trim()) {
                    showToast('warning', 'Please enter a comment');
                    return;
                }
                
                try {
                    await this.api.postComment(videoId, textarea.value.trim());
                    showToast('success', 'Comment posted successfully');
                    textarea.value = '';
                    this.loadComments(videoId);
                } catch (error) {
                    console.error('Failed to post comment:', error);
                    showToast('error', 'Failed to post comment');
                }
            });
        }
    }
    
    /**
     * Load comments for a video
     * 
     * @param {string} videoId
     */
    async loadComments(videoId) {
        try {
            const comments = await this.api.getComments(videoId);
            this.renderComments(comments);
        } catch (error) {
            console.error('Failed to load comments:', error);
        }
    }
    
    /**
     * Render comments
     * 
     * @param {Array} comments
     */
    renderComments(comments) {
        const container = document.querySelector('.comments-list');
        if (!container) {
            return;
        }
        
        if (!comments || comments.length === 0) {
            container.innerHTML = '<p class="no-comments">No comments yet. Be the first to comment!</p>';
            return;
        }
        
        container.innerHTML = comments.map(comment => `
            <div class="comment">
                <div class="comment-avatar">
                    ${comment.author ? comment.author.charAt(0).toUpperCase() : '?'}
                </div>
                <div class="comment-content">
                    <div class="comment-author">
                        ${this.escapeHtml(comment.author)}
                        <span class="comment-time">${this.formatDate(comment.created_at)}</span>
                    </div>
                    <div class="comment-text">${this.escapeHtml(comment.text)}</div>
                    <div class="comment-actions">
                        <button onclick="window.likeComment('${comment.id}')">
                            <i data-feather="thumbs-up"></i> ${comment.likes || 0}
                        </button>
                        <button onclick="window.replyComment('${comment.id}')">
                            <i data-feather="message-circle"></i> Reply
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    /**
     * Open modal
     * 
     * @param {string} modalId
     */
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    /**
     * Close all modals
     */
    closeModals() {
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
    
    /**
     * Update URL parameters
     * 
     * @param {Object} params
     */
    updateUrlParams(params) {
        const url = new URL(window.location.href);
        Object.keys(params).forEach(key => {
            if (params[key]) {
                url.searchParams.set(key, params[key]);
            } else {
                url.searchParams.delete(key);
            }
        });
        window.history.pushState({}, '', url.toString());
    }
    
    /**
     * Format number with K, M suffixes
     * 
     * @param {number} number
     * @returns {string}
     */
    formatNumber(number) {
        if (number >= 1000000) {
            return (number / 1000000).toFixed(1) + 'M';
        }
        if (number >= 1000) {
            return (number / 1000).toFixed(1) + 'K';
        }
        return number.toString();
    }
    
    /**
     * Format date
     * 
     * @param {string} dateString
     * @returns {string}
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) {
            return 'Just now';
        }
        if (diff < 3600) {
            return Math.floor(diff / 60) + 'm ago';
        }
        if (diff < 86400) {
            return Math.floor(diff / 3600) + 'h ago';
        }
        if (diff < 604800) {
            return Math.floor(diff / 86400) + 'd ago';
        }
        
        return date.toLocaleDateString();
    }
    
    /**
     * Escape HTML
     * 
     * @param {string} text
     * @returns {string}
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize the application
const app = new FocusedTube();

// Export for global use
window.app = app;
window.showToast = showToast;
window.openModal = app.openModal.bind(app);
window.closeModal = app.closeModals.bind(app);

// Handle video watch history
window.updateWatchHistory = async (videoId) => {
    try {
        await app.api.updateHistory(videoId);
    } catch (error) {
        console.error('Failed to update watch history:', error);
    }
};

// Handle likes
window.likeVideo = async (videoId) => {
    try {
        const result = await app.api.toggleLike(videoId);
        showToast('success', result.message || 'Video liked');
        // Update like button UI
        const likeBtn = document.querySelector('.like-btn');
        if (likeBtn) {
            const count = likeBtn.querySelector('.count');
            if (count) {
                count.textContent = result.likes;
            }
            likeBtn.classList.toggle('liked');
        }
    } catch (error) {
        console.error('Failed to like video:', error);
        showToast('error', 'Failed to like video');
    }
};

// Handle favorites
window.toggleFavorite = async (videoId) => {
    try {
        const result = await app.api.toggleFavorite(videoId);
        showToast('success', result.message || 'Favorite updated');
        const favBtn = document.querySelector('.favorite-btn');
        if (favBtn) {
            favBtn.classList.toggle('favorited');
        }
    } catch (error) {
        console.error('Failed to toggle favorite:', error);
        showToast('error', 'Failed to update favorite');
    }
};

// Handle watch later
window.toggleWatchLater = async (videoId) => {
    try {
        const result = await app.api.toggleWatchLater(videoId);
        showToast('success', result.message || 'Watch later updated');
        const wlBtn = document.querySelector('.watch-later-btn');
        if (wlBtn) {
            wlBtn.classList.toggle('added');
        }
    } catch (error) {
        console.error('Failed to update watch later:', error);
        showToast('error', 'Failed to update watch later');
    }
};