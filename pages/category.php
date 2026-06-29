<?php
/**
 * FocusedTube - Category View
 * 
 * Display videos in a specific category
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/init.php';

use FocusedTube\Security;
use FocusedTube\Template;

global $db;

// Get category slug or ID
$slug = isset($_GET['slug']) ? Security::sanitize($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: /categories');
    exit;
}

// Find category by slug or ID
$categories = $db->read('categories.json');
$category = null;

foreach ($categories as $cat) {
    $catSlug = $cat['slug'] ?? strtolower(str_replace(' ', '-', $cat['name']));
    if ($catSlug === $slug || $cat['id'] === $slug) {
        $category = $cat;
        break;
    }
}

if (!$category) {
    header('Location: /categories');
    exit;
}

// Get videos in category
$videos = $db->read('videos.json');
$categoryVideos = array_filter($videos, function($video) use ($category) {
    return ($video['category_id'] ?? '') === $category['id'] && 
           ($video['status'] ?? 'published') === 'published';
});

// Sort by newest first
usort($categoryVideos, function($a, $b) {
    $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
    $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
    return $timeB - $timeA;
});

// Paginate
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = ITEMS_PER_PAGE;
$totalVideos = count($categoryVideos);
$totalPages = ceil($totalVideos / $perPage);
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;
$paginatedVideos = array_slice($categoryVideos, $offset, $perPage);

// Set meta
$metaTitle = Security::escapeHtml($category['name']) . ' - ' . APP_NAME;
$metaDescription = Security::escapeHtml($category['description'] ?? "Watch videos in the {$category['name']} category on " . APP_NAME);
$canonicalUrl = SITE_URL . '/categories/' . $slug;

// Structured Data
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $category['name'],
    'description' => $category['description'] ?? '',
    'url' => $canonicalUrl
];

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="category-page">
        <!-- Category Header -->
        <div class="category-header">
            <div class="category-header-content">
                <div class="category-icon-large">
                    <?php echo $category['icon'] ?? '📁'; ?>
                </div>
                <div class="category-header-info">
                    <h1 class="category-title"><?php echo Security::escapeHtml($category['name']); ?></h1>
                    <?php if (!empty($category['description'])): ?>
                        <p class="category-description">
                            <?php echo Security::escapeHtml($category['description']); ?>
                        </p>
                    <?php endif; ?>
                    <p class="category-stats">
                        <?php echo number_format($totalVideos); ?> <?php echo $totalVideos === 1 ? 'video' : 'videos'; ?>
                    </p>
                </div>
            </div>
            <a href="/categories" class="btn btn-outline btn-sm">
                ← All Categories
            </a>
        </div>
        
        <!-- Videos Grid -->
        <?php if (empty($paginatedVideos)): ?>
            <div class="empty-state">
                <div class="empty-icon">📹</div>
                <h3>No Videos in this Category</h3>
                <p>There are no videos in the "<?php echo Security::escapeHtml($category['name']); ?>" category yet.</p>
                <a href="/categories" class="btn btn-primary" style="margin-top: var(--spacing-md);">
                    Browse Other Categories
                </a>
            </div>
        <?php else: ?>
            <div class="videos-grid" id="videoGrid">
                <?php foreach ($paginatedVideos as $video): ?>
                    <?php include __DIR__ . '/../includes/components/video-card.php'; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a href="?page=<?php echo $page - 1; ?>" class="page-link">
                                &laquo; Previous
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a href="?page=<?php echo $i; ?>" class="page-link">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a href="?page=<?php echo $page + 1; ?>" class="page-link">
                                Next &raquo;
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.category-page {
    padding: var(--spacing-lg) 0;
}

.category-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--spacing-lg);
    padding: var(--spacing-xl);
    background: var(--bg-secondary);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-xl);
    flex-wrap: wrap;
}

.category-header-content {
    display: flex;
    gap: var(--spacing-lg);
    align-items: flex-start;
}

.category-icon-large {
    font-size: 48px;
    flex-shrink: 0;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-primary);
    border-radius: var(--radius-md);
    box-shadow: 0 2px 8px var(--shadow-color);
}

.category-header-info {
    flex: 1;
}

.category-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-bold);
    margin-bottom: var(--spacing-xs);
}

.category-description {
    color: var(--text-secondary);
    font-size: var(--font-size-md);
    margin-bottom: var(--spacing-xs);
}

.category-stats {
    color: var(--text-tertiary);
    font-size: var(--font-size-sm);
    margin: 0;
}

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

@media (max-width: 768px) {
    .category-header {
        flex-direction: column;
    }
    
    .category-header-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
        width: 100%;
    }
    
    .category-header-info {
        text-align: center;
    }
    
    .category-icon-large {
        font-size: 36px;
        width: 64px;
        height: 64px;
    }
    
    .category-title {
        font-size: var(--font-size-xl);
    }
}
</style>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>