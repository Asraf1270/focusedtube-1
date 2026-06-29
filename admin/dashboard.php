<?php
/**
 * FocusedTube - Admin Dashboard
 * 
 * Main admin dashboard with statistics and overview
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/functions.php';

use FocusedTube\Security;
use FocusedTube\Template;
use FocusedTube\Auth;

// Check authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Get admin functions
$admin = new AdminFunctions();

// Get statistics
$stats = $admin->getDashboardStats();

// Get recent activity
$recentActivity = $admin->getRecentActivity(10);

// Get recent videos
$recentVideos = $admin->getRecentVideos(5);

// Set page title
$pageTitle = 'Dashboard - FocusedTube Admin';

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?php echo Security::escapeHtml($_SESSION['user_email']); ?></p>
    </div>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">📹</div>
            <div class="stat-number"><?php echo number_format($stats['total_videos']); ?></div>
            <div class="stat-label">Total Videos</div>
            <?php if ($stats['videos_change'] != 0): ?>
                <div class="stat-change <?php echo $stats['videos_change'] > 0 ? 'up' : 'down'; ?>">
                    <?php echo $stats['videos_change'] > 0 ? '↑' : '↓'; ?>
                    <?php echo abs($stats['videos_change']); ?>% from last month
                </div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">👥</div>
            <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
            <div class="stat-label">Total Users</div>
            <?php if ($stats['users_change'] != 0): ?>
                <div class="stat-change <?php echo $stats['users_change'] > 0 ? 'up' : 'down'; ?>">
                    <?php echo $stats['users_change'] > 0 ? '↑' : '↓'; ?>
                    <?php echo abs($stats['users_change']); ?>% from last month
                </div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon warning">💬</div>
            <div class="stat-number"><?php echo number_format($stats['total_comments']); ?></div>
            <div class="stat-label">Total Comments</div>
            <?php if ($stats['comments_change'] != 0): ?>
                <div class="stat-change <?php echo $stats['comments_change'] > 0 ? 'up' : 'down'; ?>">
                    <?php echo $stats['comments_change'] > 0 ? '↑' : '↓'; ?>
                    <?php echo abs($stats['comments_change']); ?>% from last month
                </div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon info">👁️</div>
            <div class="stat-number"><?php echo number_format($stats['total_views']); ?></div>
            <div class="stat-label">Total Views</div>
            <?php if ($stats['views_change'] != 0): ?>
                <div class="stat-change <?php echo $stats['views_change'] > 0 ? 'up' : 'down'; ?>">
                    <?php echo $stats['views_change'] > 0 ? '↑' : '↓'; ?>
                    <?php echo abs($stats['views_change']); ?>% from last month
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Chart Section -->
    <div class="chart-container">
        <div class="chart-header">
            <h3>Video Views Overview</h3>
            <div class="chart-controls">
                <select id="chart-period" class="form-select" style="width: auto;">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                </select>
            </div>
        </div>
        <div class="chart-wrapper">
            <canvas id="viewsChart"></canvas>
        </div>
    </div>
    
    <!-- Recent Activity & Videos -->
    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
        <!-- Recent Activity -->
        <div class="admin-card">
            <div class="card-title">Recent Activity</div>
            <div class="activity-list">
                <?php if (empty($recentActivity)): ?>
                    <p class="text-secondary">No recent activity</p>
                <?php else: ?>
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon"><?php echo $admin->getActivityIcon($activity['action']); ?></div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    <strong><?php echo Security::escapeHtml($activity['description']); ?></strong>
                                </div>
                                <div class="activity-meta">
                                    <span><?php echo Security::escapeHtml($activity['user_email'] ?? 'System'); ?></span>
                                    <span>•</span>
                                    <span><?php echo $admin->timeAgo($activity['timestamp']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Videos -->
        <div class="admin-card">
            <div class="card-title">Recent Videos</div>
            <?php if (empty($recentVideos)): ?>
                <p class="text-secondary">No videos uploaded yet</p>
            <?php else: ?>
                <div class="video-list">
                    <?php foreach ($recentVideos as $video): ?>
                        <div class="video-item">
                            <div class="video-thumbnail">
                                <img src="<?php echo Security::escapeHtml($video['thumbnail_url']); ?>" alt="<?php echo Security::escapeHtml($video['title']); ?>">
                                <span class="video-duration"><?php echo $admin->formatDuration($video['duration']); ?></span>
                            </div>
                            <div class="video-info">
                                <div class="video-title"><?php echo Security::escapeHtml($video['title']); ?></div>
                                <div class="video-meta">
                                    <span><?php echo number_format($video['view_count'] ?? 0); ?> views</span>
                                    <span>•</span>
                                    <span><?php echo $admin->timeAgo($video['created_at']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="admin-card">
        <div class="card-title">Quick Actions</div>
        <div class="quick-actions">
            <a href="videos.php?action=add" class="btn btn-primary">
                <span>➕</span> Add Video
            </a>
            <a href="users.php" class="btn btn-secondary">
                <span>👥</span> Manage Users
            </a>
            <a href="backups.php" class="btn btn-accent">
                <span>💾</span> Backup Data
            </a>
            <a href="analytics.php" class="btn btn-outline">
                <span>📊</span> View Analytics
            </a>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize views chart
    const ctx = document.getElementById('viewsChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($stats['views_data'] ?? [], 'date')); ?>,
            datasets: [{
                label: 'Views',
                data: <?php echo json_encode(array_column($stats['views_data'] ?? [], 'views')); ?>,
                borderColor: '#FF3D00',
                backgroundColor: 'rgba(255, 61, 0, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + 'M';
                            }
                            if (value >= 1000) {
                                return (value / 1000).toFixed(1) + 'K';
                            }
                            return value;
                        }
                    }
                }
            }
        }
    });
    
    // Handle period change
    document.getElementById('chart-period').addEventListener('change', function() {
        // Reload chart data
        fetch(`/admin/api/chart-data.php?period=${this.value}`)
            .then(response => response.json())
            .then(data => {
                chart.data.labels = data.labels;
                chart.data.datasets[0].data = data.values;
                chart.update();
            });
    });
});
</script>

<style>
.page-header {
    margin-bottom: var(--spacing-lg);
}

.page-title {
    margin-bottom: var(--spacing-xs);
}

.page-subtitle {
    color: var(--text-secondary);
    margin: 0;
}

.quick-actions {
    display: flex;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.activity-item {
    display: flex;
    gap: var(--spacing-md);
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--border-color);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: var(--font-size-xl);
    width: 36px;
    text-align: center;
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-size: var(--font-size-sm);
}

.activity-meta {
    font-size: var(--font-size-xs);
    color: var(--text-tertiary);
    display: flex;
    gap: var(--spacing-xs);
    align-items: center;
}

.video-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.video-item {
    display: flex;
    gap: var(--spacing-md);
    padding: var(--spacing-sm);
    border-radius: var(--radius-sm);
    transition: background var(--transition-fast);
}

.video-item:hover {
    background: var(--bg-secondary);
}

.video-thumbnail {
    position: relative;
    width: 160px;
    flex-shrink: 0;
    border-radius: var(--radius-sm);
    overflow: hidden;
}

.video-thumbnail img {
    width: 100%;
    height: auto;
    display: block;
}

.video-thumbnail .video-duration {
    position: absolute;
    bottom: 4px;
    right: 4px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 1px 6px;
    border-radius: var(--radius-sm);
    font-size: var(--font-size-xs);
}

.video-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.video-title {
    font-weight: var(--font-medium);
    margin-bottom: var(--spacing-xs);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.video-meta {
    font-size: var(--font-size-xs);
    color: var(--text-tertiary);
    display: flex;
    gap: var(--spacing-xs);
    align-items: center;
}

@media (max-width: 768px) {
    .video-thumbnail {
        width: 120px;
    }
    
    .quick-actions {
        flex-direction: column;
    }
    
    .quick-actions .btn {
        width: 100%;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>