<?php
/**
 * FocusedTube - Video Card Component
 * 
 * Reusable video card for displaying videos in grids
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;

// Ensure video data is available
if (!isset($video)) {
    return;
}

// Format duration
$duration = isset($video['duration']) ? formatDuration($video['duration']) : '';
$views = isset($video['view_count']) ? formatNumber($video['view_count']) : '0';
$published = isset($video['published_at']) ? timeAgo($video['published_at']) : '';
$thumbnail = isset($video['thumbnail_url']) ? $video['thumbnail_url'] : '/assets/images/default-thumbnail.jpg';
$videoId = isset($video['id']) ? $video['id'] : '';
$title = isset($video['title']) ? $video['title'] : 'Untitled Video';
$channelName = isset($video['channel_name']) ? $video['channel_name'] : 'Unknown Channel';
?>

<a href="/watch?id=<?php echo Security::escapeHtml($videoId); ?>" 
   class="video-card fade-in" 
   data-id="<?php echo Security::escapeHtml($videoId); ?>"
   title="<?php echo Security::escapeHtml($title); ?>">
    
    <div class="thumbnail">
        <img src="<?php echo Security::escapeHtml($thumbnail); ?>" 
             alt="<?php echo Security::escapeHtml($title); ?>" 
             loading="lazy"
             width="480"
             height="270">
        <?php if ($duration): ?>
            <span class="duration"><?php echo Security::escapeHtml($duration); ?></span>
        <?php endif; ?>
    </div>
    
    <div class="info">
        <div class="title"><?php echo Security::escapeHtml($title); ?></div>
        <div class="channel"><?php echo Security::escapeHtml($channelName); ?></div>
        <div class="meta">
            <span class="views">👁️ <?php echo $views; ?> views</span>
            <?php if ($published): ?>
                <span class="dot">•</span>
                <span class="published"><?php echo Security::escapeHtml($published); ?></span>
            <?php endif; ?>
        </div>
    </div>
</a>

<?php
// Helper functions
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
    return sprintf('%02d:%02d', $minutes, $seconds);
}

function formatNumber($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    }
    if ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return (string)$number;
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . 'm ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . 'd ago';
    }
    if ($diff < 2592000) {
        return floor($diff / 604800) . 'w ago';
    }
    if ($diff < 31536000) {
        return floor($diff / 2592000) . 'mo ago';
    }
    return floor($diff / 31536000) . 'y ago';
}
?>