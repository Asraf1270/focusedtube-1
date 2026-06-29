/**
 * FocusedTube - Utility Functions
 * 
 * Reusable utility functions for the entire application
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

/**
 * Debounce function to limit the rate of execution
 * 
 * @param {Function} func
 * @param {number} wait
 * @returns {Function}
 */
export function debounce(func, wait = 300) {
    let timeoutId;
    
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeoutId);
            func(...args);
        };
        
        clearTimeout(timeoutId);
        timeoutId = setTimeout(later, wait);
    };
}

/**
 * Throttle function to limit execution rate
 * 
 * @param {Function} func
 * @param {number} limit
 * @returns {Function}
 */
export function throttle(func, limit = 300) {
    let inThrottle;
    
    return function executedFunction(...args) {
        if (!inThrottle) {
            func(...args);
            inThrottle = true;
            setTimeout(() => {
                inThrottle = false;
            }, limit);
        }
    };
}

/**
 * Show toast notification
 * 
 * @param {string} type
 * @param {string} message
 * @param {number} duration
 */
export function showToast(type, message, duration = 5000) {
    const container = document.querySelector('.toast-container');
    if (!container) {
        createToastContainer();
    }
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span>${message}</span>
        <button class="toast-close" onclick="this.closest('.toast').remove()">
            ×
        </button>
    `;
    
    const toastContainer = document.querySelector('.toast-container');
    toastContainer.appendChild(toast);
    
    // Auto remove after duration
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, duration);
}

/**
 * Create toast container if not exists
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
}

/**
 * Get element by selector
 * 
 * @param {string} selector
 * @param {Element} parent
 * @returns {Element|null}
 */
export function getElement(selector, parent = document) {
    return parent.querySelector(selector);
}

/**
 * Get all elements by selector
 * 
 * @param {string} selector
 * @param {Element} parent
 * @returns {NodeList}
 */
export function getElements(selector, parent = document) {
    return parent.querySelectorAll(selector);
}

/**
 * Format duration in seconds to HH:MM:SS
 * 
 * @param {number} seconds
 * @returns {string}
 */
export function formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;
    
    if (hours > 0) {
        return `${padZero(hours)}:${padZero(minutes)}:${padZero(remainingSeconds)}`;
    }
    return `${padZero(minutes)}:${padZero(remainingSeconds)}`;
}

/**
 * Pad number with leading zero
 * 
 * @param {number} num
 * @returns {string}
 */
function padZero(num) {
    return String(num).padStart(2, '0');
}

/**
 * Format number with K/M suffixes
 * 
 * @param {number} num
 * @returns {string}
 */
export function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

/**
 * Format date to relative time
 * 
 * @param {string|Date} date
 * @returns {string}
 */
export function formatRelativeTime(date) {
    const now = new Date();
    const past = new Date(date);
    const diff = Math.floor((now - past) / 1000);
    
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
    if (diff < 2592000) {
        return Math.floor(diff / 604800) + 'w ago';
    }
    if (diff < 31536000) {
        return Math.floor(diff / 2592000) + 'mo ago';
    }
    return Math.floor(diff / 31536000) + 'y ago';
}

/**
 * Escape HTML entities
 * 
 * @param {string} text
 * @returns {string}
 */
export function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, (m) => map[m]);
}

/**
 * Unescape HTML entities
 * 
 * @param {string} text
 * @returns {string}
 */
export function unescapeHtml(text) {
    const map = {
        '&amp;': '&',
        '&lt;': '<',
        '&gt;': '>',
        '&quot;': '"',
        '&#039;': "'"
    };
    return text.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, (m) => map[m]);
}

/**
 * Truncate text with ellipsis
 * 
 * @param {string} text
 * @param {number} maxLength
 * @returns {string}
 */
export function truncateText(text, maxLength = 100) {
    if (text.length <= maxLength) {
        return text;
    }
    return text.substring(0, maxLength) + '...';
}

/**
 * Copy text to clipboard
 * 
 * @param {string} text
 * @returns {Promise}
 */
export function copyToClipboard(text) {
    return navigator.clipboard.writeText(text);
}

/**
 * Download data as file
 * 
 * @param {string} data
 * @param {string} filename
 * @param {string} mimeType
 */
export function downloadFile(data, filename, mimeType = 'text/plain') {
    const blob = new Blob([data], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

/**
 * Parse URL parameters
 * 
 * @returns {Object}
 */
export function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    const result = {};
    for (const [key, value] of params) {
        result[key] = value;
    }
    return result;
}

/**
 * Update URL parameters without reload
 * 
 * @param {Object} params
 */
export function updateUrlParams(params) {
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
 * Check if element is in viewport
 * 
 * @param {Element} element
 * @param {number} offset
 * @returns {boolean}
 */
export function isInViewport(element, offset = 0) {
    const rect = element.getBoundingClientRect();
    const viewHeight = window.innerHeight || document.documentElement.clientHeight;
    const viewWidth = window.innerWidth || document.documentElement.clientWidth;
    
    return (
        rect.bottom >= offset &&
        rect.top <= viewHeight - offset &&
        rect.right >= offset &&
        rect.left <= viewWidth - offset
    );
}

/**
 * Get random ID
 * 
 * @param {number} length
 * @returns {string}
 */
export function getRandomId(length = 12) {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

/**
 * Deep clone object
 * 
 * @param {Object} obj
 * @returns {Object}
 */
export function deepClone(obj) {
    return JSON.parse(JSON.stringify(obj));
}

/**
 * Merge objects deeply
 * 
 * @param {Object} target
 * @param {Object} source
 * @returns {Object}
 */
export function deepMerge(target, source) {
    const result = { ...target };
    for (const key in source) {
        if (source[key] && typeof source[key] === 'object') {
            result[key] = deepMerge(target[key] || {}, source[key]);
        } else {
            result[key] = source[key];
        }
    }
    return result;
}