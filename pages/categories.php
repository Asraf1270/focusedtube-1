<?php
/**
 * FocusedTube - Categories Page
 * 
 * Browse videos by categories
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;

// Ensure variables are available
$categories = $categories ?? [];
?>
<div class="container">
    <div class="categories-page">
        <div class="page-header">
            <h1 class="page-title">Categories</h1>
            <p class="page-subtitle">Browse videos by category</p>
        </div>
        
        <?php if (empty($categories)): ?>
            <div class="empty-state">
                <div class="empty-icon">🏷️</div>
                <h3>No Categories Found</h3>
                <p>Categories will appear here once they are added by the administrator.</p>
                <?php if (isAdmin()): ?>
                    <a href="/admin/categories.php" class="btn btn-primary" style="margin-top: var(--spacing-md);">
                        Add Categories
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="/categories/<?php echo Security::escapeHtml($category['slug'] ?? $category['id']); ?>" 
                       class="category-card <?php echo ($category['video_count'] ?? 0) > 0 ? 'has-videos' : ''; ?>">
                        <div class="category-icon">
                            <?php echo $category['icon'] ?? '📁'; ?>
                        </div>
                        <div class="category-info">
                            <h3 class="category-name"><?php echo Security::escapeHtml($category['name']); ?></h3>
                            <p class="category-count">
                                <?php echo number_format($category['video_count'] ?? 0); ?> 
                                <?php echo ($category['video_count'] ?? 0) === 1 ? 'video' : 'videos'; ?>
                            </p>
                            <?php if (!empty($category['description'])): ?>
                                <p class="category-description">
                                    <?php echo Security::escapeHtml(substr($category['description'], 0, 100)); ?>
                                    <?php echo strlen($category['description']) > 100 ? '...' : ''; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="category-arrow">→</div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.categories-page {
    padding: var(--spacing-lg) 0;
}

.page-header {
    margin-bottom: var(--spacing-xl);
}

.page-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-bold);
    margin-bottom: var(--spacing-xs);
}

.page-subtitle {
    color: var(--text-secondary);
    font-size: var(--font-size-lg);
    margin: 0;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--spacing-md);
}

.category-card {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-lg);
    background: var(--bg-card);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    text-decoration: none;
    color: var(--text-primary);
    transition: all var(--transition-normal);
    position: relative;
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px var(--shadow-md);
    border-color: var(--primary-color);
}

.category-card.has-videos:hover {
    border-color: var(--primary-color);
}

.category-card:not(.has-videos) {
    opacity: 0.6;
    cursor: not-allowed;
}

.category-icon {
    font-size: 32px;
    flex-shrink: 0;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-secondary);
    border-radius: var(--radius-sm);
}

.category-info {
    flex: 1;
    min-width: 0;
}

.category-name {
    font-size: var(--font-size-md);
    font-weight: var(--font-semibold);
    margin: 0 0 var(--spacing-xs) 0;
}

.category-count {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    margin: 0 0 var(--spacing-xs) 0;
}

.category-description {
    font-size: var(--font-size-sm);
    color: var(--text-tertiary);
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.category-arrow {
    font-size: var(--font-size-xl);
    color: var(--text-tertiary);
    transition: transform var(--transition-fast);
}

.category-card:hover .category-arrow {
    transform: translateX(4px);
    color: var(--primary-color);
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
}

@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .category-card {
        padding: var(--spacing-md);
    }
}

@media (max-width: 480px) {
    .category-icon {
        font-size: 24px;
        width: 44px;
        height: 44px;
    }
    
    .category-name {
        font-size: var(--font-size-sm);
    }
}
</style>