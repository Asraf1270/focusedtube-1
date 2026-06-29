/**
 * FocusedTube - API Client
 * 
 * Handles all API requests with caching and error handling
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

import { showToast } from './utils.js';

export class API {
    constructor() {
        this.baseUrl = '/api';
        this.cache = new Map();
        this.pendingRequests = new Map();
    }
    
    /**
     * Make API request
     * 
     * @param {string} endpoint
     * @param {Object} options
     * @returns {Promise}
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const cacheKey = this.getCacheKey(url, options);
        
        // Check cache for GET requests
        if (options.method === 'GET' || !options.method) {
            const cached = this.getCache(cacheKey);
            if (cached) {
                return cached;
            }
        }
        
        // Check for pending request
        if (this.pendingRequests.has(cacheKey)) {
            return this.pendingRequests.get(cacheKey);
        }
        
        const request = this.executeRequest(url, options);
        this.pendingRequests.set(cacheKey, request);
        
        try {
            const response = await request;
            this.pendingRequests.delete(cacheKey);
            
            // Cache GET requests
            if (options.method === 'GET' || !options.method) {
                this.setCache(cacheKey, response);
            }
            
            return response;
        } catch (error) {
            this.pendingRequests.delete(cacheKey);
            throw error;
        }
    }
    
    /**
     * Execute the actual request
     * 
     * @param {string} url
     * @param {Object} options
     * @returns {Promise}
     */
    async executeRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };
        
        // Add CSRF token
        const csrfToken = this.getCsrfToken();
        if (csrfToken) {
            defaultOptions.headers['X-CSRF-Token'] = csrfToken;
        }
        
        const finalOptions = { ...defaultOptions, ...options };
        
        // Handle body data
        if (finalOptions.body && typeof finalOptions.body === 'object') {
            finalOptions.body = JSON.stringify(finalOptions.body);
        }
        
        try {
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                let errorMessage = `HTTP Error ${response.status}`;
                try {
                    const data = await response.json();
                    errorMessage = data.message || data.error || errorMessage;
                } catch (e) {
                    // If response is not JSON
                    errorMessage = await response.text() || errorMessage;
                }
                throw new Error(errorMessage);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return await response.text();
        } catch (error) {
            if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                throw new Error('Network error - please check your connection');
            }
            throw error;
        }
    }
    
    /**
     * Get CSRF token from meta tag
     * 
     * @returns {string|null}
     */
    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : null;
    }
    
    /**
     * Get cache key
     * 
     * @param {string} url
     * @param {Object} options
     * @returns {string}
     */
    getCacheKey(url, options) {
        return `${url}_${JSON.stringify(options)}`;
    }
    
    /**
     * Get cached response
     * 
     * @param {string} key
     * @returns {any|null}
     */
    getCache(key) {
        if (!this.cache.has(key)) {
            return null;
        }
        
        const entry = this.cache.get(key);
        if (Date.now() > entry.expires) {
            this.cache.delete(key);
            return null;
        }
        
        return entry.data;
    }
    
    /**
     * Set cached response
     * 
     * @param {string} key
     * @param {any} data
     * @param {number} ttl
     */
    setCache(key, data, ttl = 300000) { // 5 minutes default
        this.cache.set(key, {
            data: data,
            expires: Date.now() + ttl
        });
    }
    
    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
    }
    
    /**
     * Videos API
     */
    async getVideos(page = 1, perPage = 20) {
        return this.request(`/videos?page=${page}&perPage=${perPage}`);
    }
    
    async getVideo(id) {
        return this.request(`/videos/${id}`);
    }
    
    async getRelatedVideos(videoId) {
        return this.request(`/videos/${videoId}/related`);
    }
    
    /**
     * Search API
     */
    async searchVideos(query, filters = {}) {
        const params = new URLSearchParams({
            q: query,
            ...filters
        });
        return this.request(`/search?${params.toString()}`);
    }
    
    async getSearchSuggestions(query) {
        return this.request(`/search/suggestions?q=${encodeURIComponent(query)}`);
    }
    
    /**
     * Comments API
     */
    async getComments(videoId, page = 1) {
        return this.request(`/comments/${videoId}?page=${page}`);
    }
    
    async postComment(videoId, text) {
        return this.request('/comments', {
            method: 'POST',
            body: {
                video_id: videoId,
                text: text
            }
        });
    }
    
    async deleteComment(commentId) {
        return this.request(`/comments/${commentId}`, {
            method: 'DELETE'
        });
    }
    
    /**
     * Likes API
     */
    async toggleLike(videoId) {
        return this.request(`/likes/${videoId}`, {
            method: 'POST'
        });
    }
    
    async getLikes(videoId) {
        return this.request(`/likes/${videoId}`);
    }
    
    /**
     * Favorites API
     */
    async toggleFavorite(videoId) {
        return this.request(`/favorites/${videoId}`, {
            method: 'POST'
        });
    }
    
    async getFavorites() {
        return this.request('/favorites');
    }
    
    /**
     * Watch Later API
     */
    async toggleWatchLater(videoId) {
        return this.request(`/watch-later/${videoId}`, {
            method: 'POST'
        });
    }
    
    async getWatchLater() {
        return this.request('/watch-later');
    }
    
    /**
     * History API
     */
    async updateHistory(videoId) {
        return this.request('/history', {
            method: 'POST',
            body: { video_id: videoId }
        });
    }
    
    async getHistory() {
        return this.request('/history');
    }
    
    async clearHistory() {
        return this.request('/history', {
            method: 'DELETE'
        });
    }
    
    /**
     * Playlists API
     */
    async getPlaylists() {
        return this.request('/playlists');
    }
    
    async createPlaylist(data) {
        return this.request('/playlists', {
            method: 'POST',
            body: data
        });
    }
    
    async getPlaylist(id) {
        return this.request(`/playlists/${id}`);
    }
    
    async updatePlaylist(id, data) {
        return this.request(`/playlists/${id}`, {
            method: 'PUT',
            body: data
        });
    }
    
    async deletePlaylist(id) {
        return this.request(`/playlists/${id}`, {
            method: 'DELETE'
        });
    }
    
    async addToPlaylist(playlistId, videoId) {
        return this.request(`/playlists/${playlistId}/videos`, {
            method: 'POST',
            body: { video_id: videoId }
        });
    }
    
    async removeFromPlaylist(playlistId, videoId) {
        return this.request(`/playlists/${playlistId}/videos/${videoId}`, {
            method: 'DELETE'
        });
    }
    
    /**
     * Auth API
     */
    async login(email, password) {
        return this.request('/auth/login', {
            method: 'POST',
            body: { email, password }
        });
    }
    
    async logout() {
        return this.request('/auth/logout', {
            method: 'POST'
        });
    }
    
    async register(data) {
        return this.request('/auth/register', {
            method: 'POST',
            body: data
        });
    }
    
    async getCurrentUser() {
        return this.request('/auth/me');
    }
    
    /**
     * YouTube API
     */
    async importVideo(url) {
        return this.request('/youtube/import', {
            method: 'POST',
            body: { url }
        });
    }
    
    async getVideoMetadata(videoId) {
        return this.request(`/youtube/metadata/${videoId}`);
    }
}

// Create and export a singleton instance
export const api = new API();