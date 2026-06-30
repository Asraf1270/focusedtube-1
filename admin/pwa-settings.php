<?php
/**
 * FocusedTube - PWA Settings
 * 
 * Admin panel for PWA configuration
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/init.php';

use FocusedTube\Security;
use FocusedTube\PWA;

// Check authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$pwa = new PWA();
$status = $pwa->getPWAStatus();

// Set page title
$pageTitle = 'PWA Settings - FocusedTube Admin';

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1 class="page-title">📱 PWA Configuration</h1>
        <p class="page-subtitle">Configure Progressive Web App settings for your site</p>
    </div>
    
    <!-- Status Cards -->
    <div class="stats-grid" style="margin-bottom: var(--spacing-lg);">
        <div class="stat-card">
            <div class="stat-icon <?php echo $status['service_worker'] ? 'success' : 'warning'; ?>">
                <?php echo $status['service_worker'] ? '✅' : '❌'; ?>
            </div>
            <div class="stat-number"><?php echo $status['service_worker'] ? 'Active' : 'Missing'; ?></div>
            <div class="stat-label">Service Worker</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon <?php echo $status['manifest'] ? 'success' : 'warning'; ?>">
                <?php echo $status['manifest'] ? '✅' : '❌'; ?>
            </div>
            <div class="stat-number"><?php echo $status['manifest'] ? 'Active' : 'Missing'; ?></div>
            <div class="stat-label">Manifest File</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon <?php echo $status['offline_page'] ? 'success' : 'warning'; ?>">
                <?php echo $status['offline_page'] ? '✅' : '❌'; ?>
            </div>
            <div class="stat-number"><?php echo $status['offline_page'] ? 'Active' : 'Missing'; ?></div>
            <div class="stat-label">Offline Page</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon <?php echo $status['is_installed'] ? 'success' : 'info'; ?>">
                <?php echo $status['is_installed'] ? '📱' : '🌐'; ?>
            </div>
            <div class="stat-number"><?php echo $status['is_installed'] ? 'Installed' : 'Browser'; ?></div>
            <div class="stat-label">Install Status</div>
        </div>
    </div>
    
    <!-- PWA Info -->
    <div class="admin-card">
        <div class="card-title">📊 PWA Status</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
            <div>
                <strong>Cache Size:</strong>
                <span><?php echo $status['cache_size']; ?></span>
            </div>
            <div>
                <strong>Push Support:</strong>
                <span><?php echo $status['has_push_support'] ? '✅' : '❌'; ?></span>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="admin-card">
        <div class="card-title">🔧 Actions</div>
        <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
            <button onclick="clearCache()" class="btn btn-secondary">
                🗑️ Clear Cache
            </button>
            <button onclick="generateIcons()" class="btn btn-accent">
                🎨 Generate Icons
            </button>
            <button onclick="testPush()" class="btn btn-primary">
                🔔 Test Push Notification
            </button>
        </div>
    </div>
    
    <!-- VAPID Keys -->
    <div class="admin-card">
        <div class="card-title">🔑 VAPID Keys</div>
        <form method="POST" action="" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label class="form-label">VAPID Public Key</label>
                <input type="text" name="vapid_public" class="form-input" 
                       value="<?php echo $settings['vapid_public_key'] ?? ''; ?>"
                       placeholder="Enter VAPID public key">
            </div>
            
            <div class="form-group">
                <label class="form-label">VAPID Private Key</label>
                <input type="password" name="vapid_private" class="form-input" 
                       placeholder="Enter VAPID private key">
                <small class="form-hint">Keep this key secure. It's used to send push notifications.</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Keys</button>
                <button type="button" onclick="generateVapid()" class="btn btn-outline">
                    Generate New Keys
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function clearCache() {
    if (confirm('Are you sure you want to clear the PWA cache?')) {
        fetch('/api/pwa/clear-cache', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Cache cleared successfully!');
            } else {
                showToast('error', 'Failed to clear cache.');
            }
        });
    }
}

function generateIcons() {
    fetch('/api/pwa/generate-icons', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Icons generated successfully!');
        } else {
            showToast('error', 'Failed to generate icons.');
        }
    });
}

function testPush() {
    fetch('/api/pwa/test-push', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Test notification sent!');
        } else {
            showToast('error', 'Failed to send test notification.');
        }
    });
}

function generateVapid() {
    fetch('/api/pwa/generate-vapid', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector('[name="vapid_public"]').value = data.publicKey;
            showToast('success', 'VAPID keys generated!');
        } else {
            showToast('error', 'Failed to generate VAPID keys.');
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>