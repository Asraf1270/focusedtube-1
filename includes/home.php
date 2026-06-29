<?php
/**
 * FocusedTube - Home Page
 * 
 * Home page displaying video grid with infinite scroll
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/init.php';

use FocusedTube\Security;
use FocusedTube\Template;

// Get videos
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = ITEMS_PER_PAGE;

global $db;
$videos = $db->read('videos.json');

// Filter only published videos
$videos = array_filter($videos, function($video) {
    return ($video['status'] ?? 'published') === 'published';
});

// Sort by created_at descending
usort($videos, function($a, $b) {
    $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
    $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
    return $timeB - $timeA;
});

// Paginate
$totalVideos = count($videos);
$totalPages = ceil($totalVideos / $perPage);
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;
$paginatedVideos = array_slice($videos, $offset, $perPage);
$hasMore = $page < $totalPages;

// Set meta
$metaTitle = APP_NAME . ' - Watch, Organize, Discover';
$metaDescription = 'A self-hosted YouTube video library. Watch, organize, and discover videos without distractions.';
$canonicalUrl = SITE_URL . '/';

// Include header
include_once __DIR__ . '/../includes/header.php';
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
                <a href="/trending" class="btn btn-outline">View Trending</a>
            </div>
        </div>
    </section>
    
    <!-- Video Grid -->
    <section class="video-section">
        <div class="section-header">
            <h2 class="section-title">Latest Videos</h2>
            <div class="section-actions">
                <a href="/latest" class="btn btn-sm btn-outline">View All →</a>
            </div>
        </div>
        
        <?php if (empty($paginatedVideos)): ?>
            <div class="empty-state">
                <div class="empty-icon">📹</div>
                <h3>No Videos Available</h3>
                <p>There are no videos in the library yet. Check back later!</p>
            </div>
        <?php else: ?>
            <div class="videos-grid" id="videoGrid">
                <?php foreach ($paginatedVideos as $video): ?>
                    <?php include __DIR__ . '/../includes/components/video-card.php'; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Infinite Scroll Sentinel -->
            <?php if ($hasMore): ?>
                <div id="infinite-scroll-sentinel" 
                     data-page="<?php echo $page; ?>" 
                     data-total="<?php echo $totalPages; ?>"
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

.section-actions {
    display: flex;
    gap: var(--spacing-sm);
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

/* Loader */
.loader {
    text-align: center;
    padding: var(--spacing-xl) 0;
}

.loader .spinner {
    display: inline-block;
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
    
    .section-header {
        flex-direction: column;
        gap: var(--spacing-sm);
        text-align: center;
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
}
</style>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>