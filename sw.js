/**
 * FocusedTube - Service Worker
 * 
 * Provides offline support, caching, and push notifications
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

const CACHE_NAME = 'focusedtube-v1';
const OFFLINE_URL = '/offline.html';

// Assets to cache on install
const STATIC_ASSETS = [
    '/',
    '/offline.html',
    '/assets/css/style.css',
    '/assets/css/animations.css',
    '/assets/css/dark-mode.css',
    '/assets/js/main.js',
    '/assets/js/utils.js',
    '/assets/js/api.js',
    '/assets/js/theme.js',
    '/assets/images/logo.svg',
    '/assets/images/favicon.ico',
    '/assets/images/default-thumbnail.jpg',
    '/manifest.json'
];

// API endpoints to cache
const API_CACHE = [
    '/api/videos',
    '/api/categories',
    '/api/search'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[Service Worker] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                console.log('[Service Worker] Skip waiting');
                return self.skipWaiting();
            })
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[Service Worker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('[Service Worker] Claiming clients');
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Skip cross-origin requests
    if (url.origin !== location.origin) {
        return;
    }
    
    // Handle API requests
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(handleApiRequest(event.request));
        return;
    }
    
    // Handle HTML navigation
    if (event.request.mode === 'navigate') {
        event.respondWith(handleNavigationRequest(event.request));
        return;
    }
    
    // Handle static assets
    event.respondWith(handleStaticRequest(event.request));
});

// Handle API requests with cache-first strategy
async function handleApiRequest(request) {
    const url = new URL(request.url);
    const cacheKey = url.pathname + url.search;
    
    // Check if this API endpoint should be cached
    const shouldCache = API_CACHE.some(endpoint => 
        url.pathname.startsWith(endpoint)
    );
    
    if (!shouldCache) {
        // Network first for non-cacheable APIs
        try {
            const response = await fetch(request);
            return response;
        } catch (error) {
            return new Response(
                JSON.stringify({ error: 'Network error' }),
                {
                    status: 503,
                    headers: { 'Content-Type': 'application/json' }
                }
            );
        }
    }
    
    // Cache first for cacheable APIs
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        // Return cached response but update cache in background
        fetch(request).then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
        }).catch(() => {});
        
        return cachedResponse;
    }
    
    // Fetch from network
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return new Response(
            JSON.stringify({ error: 'Network error' }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

// Handle navigation requests with offline fallback
async function handleNavigationRequest(request) {
    const cache = await caches.open(CACHE_NAME);
    
    try {
        // Try network first
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
            return response;
        }
        throw new Error('Network response was not ok');
    } catch (error) {
        // Network failed - try cache
        const cachedResponse = await cache.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Offline fallback
        return cache.match(OFFLINE_URL);
    }
}

// Handle static assets with cache-first strategy
async function handleStaticRequest(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        // Return cached response and update in background
        fetch(request).then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
        }).catch(() => {});
        
        return cachedResponse;
    }
    
    // Fetch from network
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        // If it's an image, return default
        if (request.url.match(/\.(jpg|jpeg|png|gif|webp|svg)$/)) {
            return cache.match('/assets/images/default-thumbnail.jpg');
        }
        return new Response('Resource not found', { status: 404 });
    }
}

// Push notification event
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'FocusedTube';
    const options = {
        body: data.body || 'New video available!',
        icon: '/assets/images/icon-192x192.png',
        badge: '/assets/images/icon-72x72.png',
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/'
        },
        actions: [
            {
                action: 'open',
                title: 'Open',
                icon: '/assets/images/open-icon.png'
            },
            {
                action: 'close',
                title: 'Dismiss',
                icon: '/assets/images/close-icon.png'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'open') {
        const url = event.notification.data.url;
        event.waitUntil(
            clients.openWindow(url)
        );
    } else if (event.action === 'close') {
        // Just close notification
    } else {
        // Default: open app
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Sync event - background sync
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-comments') {
        event.waitUntil(syncComments());
    }
});

// Background sync function
async function syncComments() {
    try {
        const cache = await caches.open(CACHE_NAME);
        const requests = await cache.matchAll('/api/comments/pending');
        
        for (const request of requests) {
            try {
                const response = await fetch(request);
                if (response.ok) {
                    await cache.delete(request);
                }
            } catch (error) {
                console.error('Failed to sync comment:', error);
            }
        }
    } catch (error) {
        console.error('Sync failed:', error);
    }
}

// Periodic background sync (for updates)
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'update-videos') {
        event.waitUntil(updateVideos());
    }
});

async function updateVideos() {
    try {
        const response = await fetch('/api/videos?page=1');
        if (response.ok) {
            const data = await response.json();
            // Cache videos for offline viewing
            const cache = await caches.open(CACHE_NAME);
            const requests = data.items.map(video => 
                new Request(`/api/videos/${video.id}`)
            );
            await cache.addAll(requests);
        }
    } catch (error) {
        console.error('Failed to update videos:', error);
    }
}