<?php
/**
 * FocusedTube - Search Page
 * 
 * Search results page with advanced filtering
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/init.php';

use FocusedTube\Security;
use FocusedTube\Template;

global $db;

// Get search parameters
$query = isset($_GET['q']) ? Security::sanitize($_GET['q']) : '';
$category = isset($_GET['category']) ? Security::sanitize($_GET['category']) : '';
$sort = isset($_GET['sort']) ? Security::sanitize($_GET['sort']) : 'relevance';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = ITEMS_PER_PAGE;

// Get all published videos
$videos = $db->read('videos.json');
$videos = array_filter($videos, function($video) {
    return ($video['status'] ?? 'published') === 'published';
});

// Search
if ($query) {
    $searchTerms = explode(' ', strtolower($query));
    $videos = array_filter($videos, function($video) use ($searchTerms) {
        $title = strtolower($video['title'] ?? '');
        $description = strtolower($video['description'] ?? '');
        $channel = strtolower($video['channel_name'] ?? '');
        $tags = array_map('strtolower', $video['tags'] ?? []);
        
        foreach ($searchTerms as $term) {
            if (strpos($title, $term) !== false ||
                strpos($description, $term) !== false ||
                strpos($channel, $term) !== false) {
                return true;
            }
            foreach ($tags as $tag) {
                if (strpos($tag, $term) !== false) {
                    return true;
                }
            }
        }
        return false;
    });
}

// Filter by category
if ($category) {
    $videos = array_filter($videos, function($video) use ($category) {
        return ($video['category_id'] ?? '') === $category;
    });
}

// Sort
switch ($sort) {
    case 'newest':
        usort($videos, function($a, $b) {
            $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
            $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
            return $timeB - $timeA;
        });
        break;
    case 'oldest':
        usort($videos, function($a, $b) {
            $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
            $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
            return $timeA - $timeB;
        });
        break;
    case 'views':
        usort($videos, function($a, $b) {
            return ($b['view_count'] ?? 0) - ($a['view_count'] ?? 0);
        });
        break;
    case 'relevance':
    default:
        // Keep current order (relevance by matching title)
        if ($query) {
            $queryLower = strtolower($query);
            usort($videos, function($a, $b) use ($queryLower) {
                $aTitle = strtolower($a['title'] ?? '');
                $bTitle = strtolower($b['title'] ?? '');
                $aScore = strpos($aTitle, $queryLower) !== false ? 1 : 0;
                $bScore = strpos($bTitle, $queryLower) !== false ? 1 : 0;
                return $bScore - $aScore;
            });
        }
        break;
}

// Paginate
$totalVideos = count($videos);
$totalPages = ceil($totalVideos / $perPage);
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;
$paginatedVideos = array_slice($videos, $offset, $perPage);
$hasMore = $page < $totalPages;

// Get categories for filter
$categories = $db->read('categories.json');

// Set meta
$metaTitle = ($query ? "Search: {$query} - " : "") . APP_NAME;
$metaDescription = $query ? "Search results for '{$query}'" : "Search for videos";
$canonicalUrl = SITE_URL . '/search?' . http_build_query($_GET);

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="search-page">
        <!-- Search Header -->
        <div class="search-header">
            <h1 class="search-title">
                <?php if ($query): ?>
                    Results for "<?php echo Security::escapeHtml($query); ?>"
                <?php else: ?>
                    Search Videos
                <?php endif; ?>
            </h1>
            <p class="search-count">
                <?php echo number_format($totalVideos); ?> <?php echo $totalVideos === 1 ? 'video' : 'videos'; ?> found
            </p>
        </div>
        
        <!-- Search Filters -->
        <div class="search-filters">
            <form method="GET" action="" class="filter-form">
                <input type="hidden" name="q" value="<?php echo Security::escapeHtml($query); ?>">
                
                <div class="filter-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo Security::escapeHtml($cat['id']); ?>" 
                                <?php echo $category === $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escapeHtml($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort By</label>
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="views" <?php echo $sort === 'views' ? 'selected' : ''; ?>>Most Views</option>
                    </select>
                </div>
            </form>
        </div>
        
        <!-- Results -->
        <?php if (empty($paginatedVideos)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3>No Videos Found</h3>
                <p>
                    <?php if ($query): ?>
                        No videos match your search criteria. Try adjusting your search terms.
                    <?php else: ?>
                        Start searching for videos in the library.
                    <?php endif; ?>
                </p>
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
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                                &laquo; Previous
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-link">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
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
.search-page {
    padding: var(--spacing-lg) 0;
}

.search-header {
    margin-bottom: var(--spacing-lg);
}

.search-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-bold);
    margin-bottom: var(--spacing-xs);
}

.search-count {
    color: var(--text-secondary);
    font-size: var(--font-size-sm);
    margin: 0;
}

.search-filters {
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-lg);
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.filter-group label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
}

.filter-group select {
    padding: var(--spacing-sm) var(--spacing-md);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-sm);
    background: var(--bg-primary);
    color: var(--text-primary);
    font-size: var(--font-size-sm);
    cursor: pointer;
    min-width: 150px;
}

.filter-group select:focus {
    outline: none;
    border-color: var(--primary-color);
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

.empty-state h3 {
    margin-bottom: var(--spacing-sm);
}

.empty-state p {
    color: var(--text-secondary);
    max-width: 400px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .filter-group select {
        width: 100%;
        min-width: auto;
    }
}
</style>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>