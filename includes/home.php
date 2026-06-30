<?php
/**
 * FocusedTube - Home Page
 * 
 * Home page displaying video grid with infinite scroll
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;

// Ensure variables are available
$paginatedVideos = $paginatedVideos ?? [];
$hasMore = $hasMore ?? false;
$page = $page ?? 1;
?>
<div class="container">
    <!-- Hero Section -->
    <section class="hero-section fade-in">
        <div class="hero-content">
            <h1 class="hero-title">Watch, Organize, Discover</h1>
            <p class="hero-description">
                A self-hosted YouTube video library. Watch your favorite videos without distractions.
            </p>
            <div class="hero-actions">
                <a href="/categories" class="btn btn-primary">Browse Categories</a>
            </div>
        </div>
    </section>
    
    <!-- Video Grid -->
    <section class="video-section">
        <div class="section-header">
            <h2 class="section-title">Latest Videos</h2>
            <div class="section-actions">
                <span class="video-count"><?php echo count($paginatedVideos); ?> videos</span>
            </div>
        </div>
        
        <?php if (empty($paginatedVideos)): ?>
            <div class="empty-state">
                <div class="empty-icon">📹</div>
                <h3>No Videos Available</h3>
                <p>There are no videos in the library yet. Check back later!</p>
                <?php if (isAdmin()): ?>
                    <a href="/admin/videos.php" class="btn btn-primary" style="margin-top: var(--spacing-md);">
                        Import Videos
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="videos-grid" id="videoGrid">
                <?php foreach ($paginatedVideos as $video): ?>
                    <a href="/watch?id=<?php echo Security::escapeHtml($video['id']); ?>" 
                       class="video-card fade-in" 
                       data-id="<?php echo Security::escapeHtml($video['id']); ?>"
                       title="<?php echo Security::escapeHtml($video['title']); ?>">
                        
                        <div class="thumbnail">
                            <img src="<?php echo Security::escapeHtml($video['thumbnail_url']); ?>" 
                                 alt="<?php echo Security::escapeHtml($video['title']); ?>" 
                                 loading="lazy"
                                 onerror="this.src='/assets/images/default-thumbnail.jpg'">
                            <?php if (isset($video['duration'])): ?>
                                <span class="duration"><?php echo formatDuration($video['duration']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="info">
                            <div class="title"><?php echo Security::escapeHtml($video['title']); ?></div>
                            <div class="channel"><?php echo Security::escapeHtml($video['channel_name']); ?></div>
                            <div class="meta">
                                <span class="views">👁️ <?php echo formatNumber($video['view_count'] ?? 0); ?></span>
                                <?php if (isset($video['created_at'])): ?>
                                    <span class="dot">•</span>
                                    <span class="published"><?php echo timeAgo($video['created_at']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Infinite Scroll Sentinel -->
            <?php if ($hasMore): ?>
                <div id="infinite-scroll-sentinel" 
                     data-page="<?php echo $page; ?>" 
                     data-total="<?php echo $totalPages ?? 1; ?>"
                     data-url="/api/videos"></div>
                <div class="loader" id="loader" style="display: none;">
                    <div class="spinner"></div>
                    <p>Loading more videos...</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<style>
/* Hero Section */
.hero-section {
    padding: var(--spacing-3xl) 0 var(--spacing-2xl);
    text-align: center;
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
    border-radius: var(--radius-lg);
    margin: var(--spacing-lg) 0;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at 30% 50%, rgba(var(--primary-rgb), 0.05) 0%, transparent 70%);
    animation: floatSlow 20s ease-in-out infinite;
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-title {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-bold);
    margin-bottom: var(--spacing-md);
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-description {
    font-size: var(--font-size-lg);
    color: var(--text-secondary);
    max-width: 600px;
    margin: 0 auto var(--spacing-lg);
}

.hero-actions {
    display: flex;
    gap: var(--spacing-md);
    justify-content: center;
    flex-wrap: wrap;
}

/* Video Section */
.video-section {
    padding: var(--spacing-xl) 0;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--spacing-lg);
}

.section-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-bold);
    margin: 0;
}

.video-count {
    font-size: var(--font-size-sm);
    color: var(--text-tertiary);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: var(--spacing-3xl) var(--spacing-xl);
    background: var(--bg-secondary);
    border-radius: var(--radius-lg);
}

.empty-icon {
    font-size: 64px;
    margin-bottom: var(--spacing-md);
}

/* Videos Grid */
.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--spacing-lg);
}

.video-card {
    background: var(--bg-card);
    border-radius: var(--radius-md);
    overflow: hidden;
    transition: all var(--transition-normal);
    cursor: pointer;
    text-decoration: none;
    color: var(--text-primary);
}

.video-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px var(--shadow-md);
}

.video-card .thumbnail {
    position: relative;
    padding-top: 56.25%;
    background: var(--bg-secondary);
    overflow: hidden;
}

.video-card .thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-normal);
}

.video-card:hover .thumbnail img {
    transform: scale(1.05);
}

.video-card .duration {
    position: absolute;
    bottom: var(--spacing-sm);
    right: var(--spacing-sm);
    padding: 2px var(--spacing-sm);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    font-size: var(--font-size-xs);
    font-weight: var(--font-medium);
    border-radius: var(--radius-sm);
}

.video-card .info {
    padding: var(--spacing-md);
}

.video-card .title {
    font-weight: var(--font-semibold);
    margin-bottom: var(--spacing-xs);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.video-card .channel {
    color: var(--text-secondary);
    font-size: var(--font-size-sm);
    margin-bottom: var(--spacing-xs);
}

.video-card .meta {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    color: var(--text-tertiary);
    font-size: var(--font-size-xs);
}

.video-card .meta span {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

/* Loader */
.loader {
    text-align: center;
    padding: var(--spacing-xl) 0;
}

.loader .spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid var(--border-color);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: rotate 0.8s linear infinite;
    margin-bottom: var(--spacing-md);
}

.loader p {
    color: var(--text-secondary);
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title {
        font-size: var(--font-size-2xl);
    }
    
    .hero-description {
        font-size: var(--font-size-md);
    }
    
    .hero-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .videos-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
}

@media (max-width: 480px) {
    .hero-section {
        padding: var(--spacing-xl) var(--spacing-md);
        margin: var(--spacing-md) 0;
    }
    
    .hero-title {
        font-size: var(--font-size-xl);
    }
    
    .videos-grid {
        grid-template-columns: 1fr;
    }
}
</style>