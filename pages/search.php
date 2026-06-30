<?php
/**
 * FocusedTube - Search Page
 * 
 * Search results page with advanced filtering
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;

// Ensure variables are available
$paginatedVideos = $paginatedVideos ?? [];
$totalVideos = $totalVideos ?? 0;
$query = $query ?? '';
$category = $category ?? '';
$sort = $sort ?? 'relevance';
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$categories = $categories ?? [];
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

.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
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
    
    .videos-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
}

@media (max-width: 480px) {
    .videos-grid {
        grid-template-columns: 1fr;
    }
}
</style>