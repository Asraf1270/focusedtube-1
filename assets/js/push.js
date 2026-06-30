/**
 * FocusedTube - Push Notifications
 * 
 * Handles push notification subscription and management
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

class PushManager {
    constructor() {
        this.swRegistration = null;
        this.vapidPublicKey = null;
        this.subscription = null;
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window;
        
        if (this.isSupported) {
            this.init();
        }
    }
    
    async init() {
        try {
            this.swRegistration = await navigator.serviceWorker.ready;
            this.vapidPublicKey = await this.getVapidPublicKey();
            this.subscription = await this.getSubscription();
            this.updateUI();
        } catch (error) {
            console.error('Push init failed:', error);
        }
    }
    
    async getVapidPublicKey() {
        const response = await fetch('/api/push/vapid');
        const data = await response.json();
        return data.publicKey;
    }
    
    async getSubscription() {
        return await this.swRegistration.pushManager.getSubscription();
    }
    
    async subscribe() {
        if (!this.isSupported) {
            showToast('error', 'Push notifications are not supported on this device.');
            return false;
        }
        
        if (Notification.permission === 'denied') {
            showToast('error', 'Push notifications are blocked. Please enable them in your browser settings.');
            return false;
        }
        
        try {
            if (Notification.permission === 'default') {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    showToast('error', 'Push notifications permission denied.');
                    return false;
                }
            }
            
            const applicationServerKey = this.urlBase64ToUint8Array(this.vapidPublicKey);
            
            this.subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });
            
            // Save subscription to server
            await this.saveSubscription(this.subscription);
            
            showToast('success', 'Push notifications enabled!');
            this.updateUI();
            return true;
            
        } catch (error) {
            console.error('Push subscription failed:', error);
            showToast('error', 'Failed to enable push notifications.');
            return false;
        }
    }
    
    async unsubscribe() {
        if (!this.subscription) {
            return false;
        }
        
        try {
            await this.subscription.unsubscribe();
            await this.deleteSubscription(this.subscription.endpoint);
            this.subscription = null;
            showToast('info', 'Push notifications disabled.');
            this.updateUI();
            return true;
        } catch (error) {
            console.error('Push unsubscription failed:', error);
            return false;
        }
    }
    
    async saveSubscription(subscription) {
        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(subscription)
        });
        
        return response.ok;
    }
    
    async deleteSubscription(endpoint) {
        const response = await fetch('/api/push/unsubscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ endpoint })
        });
        
        return response.ok;
    }
    
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        
        return outputArray;
    }
    
    updateUI() {
        const toggle = document.getElementById('push-toggle');
        const status = document.getElementById('push-status');
        
        if (toggle) {
            toggle.checked = this.subscription !== null;
        }
        
        if (status) {
            status.textContent = this.subscription ? 'Enabled' : 'Disabled';
            status.className = this.subscription ? 'status-enabled' : 'status-disabled';
        }
    }
}

// Initialize push manager
document.addEventListener('DOMContentLoaded', () => {
    window.pushManager = new PushManager();
});

// Handle online/offline status
window.addEventListener('online', () => {
    document.getElementById('online-status').style.display = 'none';
});

window.addEventListener('offline', () => {
    document.getElementById('online-status').style.display = 'block';
});

// Initial check
if (!navigator.onLine) {
    document.getElementById('online-status').style.display = 'block';
}