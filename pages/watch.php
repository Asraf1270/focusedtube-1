<?php
/**
 * FocusedTube - Watch Page
 * 
 * Video watch page with player, details, and comments
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;

// Ensure variables are available
$video = $video ?? null;
$videoId = $video['id'] ?? '';
$relatedVideos = $relatedVideos ?? [];
$videoComments = $videoComments ?? [];
$userLiked = $userLiked ?? false;
$isFavorited = $isFavorited ?? false;
$isWatchLater = $isWatchLater ?? false;
?>
<div class="container">
    <div class="watch-layout">
        <!-- Main Content -->
        <div class="watch-main">
            <!-- Video Player -->
            <div class="video-player-wrapper">
                <iframe 
                    src="https://www.youtube.com/embed/<?php echo Security::escapeHtml($videoId); ?>?autoplay=0&rel=0&modestbranding=1" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen
                    loading="lazy"
                    title="<?php echo Security::escapeHtml($video['title']); ?>">
                </iframe>
            </div>
            
            <!-- Video Details -->
            <div class="video-details">
                <h1 class="title"><?php echo Security::escapeHtml($video['title']); ?></h1>
                
                <div class="meta">
                    <div class="channel-info">
                        <span class="channel-name"><?php echo Security::escapeHtml($video['channel_name']); ?></span>
                    </div>
                    
                    <div class="video-stats">
                        <span class="views"><?php echo formatNumber($video['view_count'] ?? 0); ?> views</span>
                        <span class="dot">•</span>
                        <span class="date"><?php echo formatDate($video['created_at'] ?? $video['published_at'] ?? 'now'); ?></span>
                    </div>
                </div>
                
                <div class="video-actions">
                    <div class="action-group">
                        <button class="action-btn <?php echo $userLiked ? 'liked' : ''; ?>" 
                                onclick="likeVideo('<?php echo $videoId; ?>')"
                                aria-label="Like video">
                            <span class="icon">👍</span>
                            <span class="count"><?php echo formatNumber($video['like_count'] ?? 0); ?></span>
                        </button>
                        <button class="action-btn" onclick="shareVideo()" aria-label="Share video">
                            <span class="icon">📤</span>
                            <span>Share</span>
                        </button>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <div class="action-group">
                            <button class="action-btn <?php echo $isFavorited ? 'favorited' : ''; ?>" 
                                    onclick="toggleFavorite('<?php echo $videoId; ?>')"
                                    aria-label="Add to favorites">
                                <span class="icon">⭐</span>
                                <span><?php echo $isFavorited ? 'Favorited' : 'Favorite'; ?></span>
                            </button>
                            <button class="action-btn <?php echo $isWatchLater ? 'added' : ''; ?>" 
                                    onclick="toggleWatchLater('<?php echo $videoId; ?>')"
                                    aria-label="Watch later">
                                <span class="icon">⏰</span>
                                <span><?php echo $isWatchLater ? 'Added' : 'Watch Later'; ?></span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($video['description'])): ?>
                    <div class="description">
                        <button class="description-toggle" onclick="toggleDescription()">Show Description</button>
                        <div class="description-content" style="display: none;">
                            <?php echo nl2br(Security::escapeHtml($video['description'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($video['tags'])): ?>
                    <div class="video-tags">
                        <?php foreach ($video['tags'] as $tag): ?>
                            <a href="/search?q=<?php echo urlencode($tag); ?>" class="tag">#<?php echo Security::escapeHtml($tag); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Comments Section -->
            <div class="comment-section">
                <h3 class="comment-title">
                    Comments <span class="comment-count">(<?php echo count($videoComments); ?>)</span>
                </h3>
                
                <?php if (isLoggedIn()): ?>
                    <form class="comment-form" data-video-id="<?php echo $videoId; ?>">
                        <div class="comment-input-wrapper">
                            <div class="comment-avatar">
                                <?php echo strtoupper(substr($_SESSION['user_email'] ?? 'U', 0, 1)); ?>
                            </div>
                            <textarea class="comment-input" placeholder="Write a comment..." rows="1"></textarea>
                        </div>
                        <div class="comment-actions">
                            <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('form').querySelector('textarea').value = ''">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Comment</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="comment-login-prompt">
                        <a href="/admin/login">Sign in</a> to leave a comment
                    </p>
                <?php endif; ?>
                
                <div class="comments-list">
                    <?php if (empty($videoComments)): ?>
                        <p class="no-comments">No comments yet. Be the first to comment!</p>
                    <?php else: ?>
                        <?php foreach ($videoComments as $comment): ?>
                            <div class="comment">
                                <div class="comment-avatar">
                                    <?php echo strtoupper(substr($comment['author'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div class="comment-content">
                                    <div class="comment-author">
                                        <?php echo Security::escapeHtml($comment['author'] ?? 'Anonymous'); ?>
                                        <span class="comment-time"><?php echo timeAgo($comment['created_at']); ?></span>
                                    </div>
                                    <div class="comment-text"><?php echo nl2br(Security::escapeHtml($comment['text'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="watch-sidebar">
            <h4 class="sidebar-title">Related Videos</h4>
            <?php if (empty($relatedVideos)): ?>
                <p class="text-secondary">No related videos found</p>
            <?php else: ?>
                <?php foreach ($relatedVideos as $related): ?>
                    <a href="/watch?id=<?php echo Security::escapeHtml($related['id']); ?>" class="related-video">
                        <div class="related-thumbnail">
                            <img src="<?php echo Security::escapeHtml($related['thumbnail_url']); ?>" 
                                 alt="<?php echo Security::escapeHtml($related['title']); ?>"
                                 loading="lazy"
                                 onerror="this.src='/assets/images/default-thumbnail.jpg'">
                            <span class="duration"><?php echo formatDuration($related['duration'] ?? 0); ?></span>
                        </div>
                        <div class="related-info">
                            <div class="related-title"><?php echo Security::escapeHtml($related['title']); ?></div>
                            <div class="related-channel"><?php echo Security::escapeHtml($related['channel_name']); ?></div>
                            <div class="related-meta">
                                <?php echo formatNumber($related['view_count'] ?? 0); ?> views • <?php echo timeAgo($related['created_at'] ?? $related['published_at'] ?? 'now'); ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.watch-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: var(--spacing-lg);
    padding: var(--spacing-lg) 0;
}

.watch-main {
    min-width: 0;
}

.video-player-wrapper {
    margin-bottom: var(--spacing-lg);
}

.video-player-wrapper iframe {
    width: 100%;
    height: 100%;
    border: none;
}

.video-details .title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-bold);
    margin-bottom: var(--spacing-sm);
}

.video-details .meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-md);
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid var(--border-color);
    margin-bottom: var(--spacing-md);
}

.channel-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.channel-name {
    font-weight: var(--font-semibold);
    font-size: var(--font-size-md);
}

.video-stats {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

.video-actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.action-group {
    display: flex;
    gap: var(--spacing-sm);
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm) var(--spacing-md);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-full);
    background: var(--bg-primary);
    cursor: pointer;
    transition: all var(--transition-fast);
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

.action-btn:hover {
    background: var(--bg-secondary);
    border-color: var(--text-tertiary);
}

.action-btn.liked,
.action-btn.favorited,
.action-btn.added {
    background: rgba(var(--primary-rgb), 0.1);
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.action-btn .icon {
    font-size: var(--font-size-md);
}

.description-toggle {
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    font-weight: var(--font-medium);
    padding: 0;
    margin-bottom: var(--spacing-sm);
}

.description-content {
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-radius: var(--radius-sm);
    white-space: pre-wrap;
    word-wrap: break-word;
    color: var(--text-secondary);
}

.video-tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
}

/* Comments */
.comment-section {
    margin-top: var(--spacing-xl);
}

.comment-title {
    font-size: var(--font-size-lg);
    margin-bottom: var(--spacing-md);
}

.comment-count {
    color: var(--text-tertiary);
    font-weight: var(--font-regular);
}

.comment-form {
    margin-bottom: var(--spacing-lg);
}

.comment-input-wrapper {
    display: flex;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-sm);
}

.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-full);
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: var(--font-bold);
    flex-shrink: 0;
}

.comment-input {
    flex: 1;
    padding: var(--spacing-sm) var(--spacing-md);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-sm);
    resize: vertical;
    font-family: var(--font-family);
    font-size: var(--font-size-sm);
    min-height: 44px;
    background: var(--bg-primary);
    color: var(--text-primary);
}

.comment-input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.comment-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-sm);
}

.comment-login-prompt {
    color: var(--text-secondary);
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-radius: var(--radius-sm);
    margin-bottom: var(--spacing-md);
}

.no-comments {
    color: var(--text-tertiary);
    text-align: center;
    padding: var(--spacing-xl) 0;
}

/* Related Videos */
.watch-sidebar {
    position: sticky;
    top: calc(var(--admin-header-height) + var(--spacing-lg));
    max-height: calc(100vh - var(--admin-header-height) - var(--spacing-2xl));
    overflow-y: auto;
}

.sidebar-title {
    font-size: var(--font-size-md);
    font-weight: var(--font-semibold);
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-sm);
    border-bottom: 1px solid var(--border-color);
}

.related-video {
    display: flex;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm);
    border-radius: var(--radius-sm);
    text-decoration: none;
    color: var(--text-primary);
    transition: background var(--transition-fast);
}

.related-video:hover {
    background: var(--bg-secondary);
}

.related-thumbnail {
    position: relative;
    flex-shrink: 0;
    width: 160px;
    border-radius: var(--radius-sm);
    overflow: hidden;
}

.related-thumbnail img {
    width: 100%;
    height: auto;
    display: block;
}

.related-thumbnail .duration {
    position: absolute;
    bottom: 4px;
    right: 4px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    font-size: var(--font-size-xs);
    padding: 1px 6px;
    border-radius: var(--radius-sm);
}

.related-info {
    flex: 1;
    min-width: 0;
}

.related-title {
    font-size: var(--font-size-sm);
    font-weight: var(--font-medium);
    margin-bottom: var(--spacing-xs);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.related-channel {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    margin-bottom: var(--spacing-xs);
}

.related-meta {
    font-size: var(--font-size-xs);
    color: var(--text-tertiary);
}

/* Responsive */
@media (max-width: 1024px) {
    .watch-layout {
        grid-template-columns: 1fr;
    }
    
    .watch-sidebar {
        position: static;
        max-height: none;
        overflow-y: visible;
    }
}

@media (max-width: 768px) {
    .video-details .meta {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-sm);
    }
    
    .video-actions {
        flex-direction: column;
    }
    
    .action-group {
        justify-content: center;
    }
    
    .related-thumbnail {
        width: 120px;
    }
}

@media (max-width: 480px) {
    .related-video {
        flex-direction: column;
    }
    
    .related-thumbnail {
        width: 100%;
    }
}
</style>

<script>
function toggleDescription() {
    const content = document.querySelector('.description-content');
    const toggle = document.querySelector('.description-toggle');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggle.textContent = 'Hide Description';
    } else {
        content.style.display = 'none';
        toggle.textContent = 'Show Description';
    }
}

function shareVideo() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo Security::escapeHtml($video['title']); ?>',
            text: 'Watch this video on <?php echo APP_NAME; ?>',
            url: window.location.href
        }).catch(() => {});
    } else {
        navigator.clipboard.writeText(window.location.href).then(() => {
            showToast('success', 'Link copied to clipboard!');
        });
    }
}
</script>