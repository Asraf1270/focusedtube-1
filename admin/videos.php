<?php
/**
 * FocusedTube - Video Management
 * 
 * Admin interface for managing videos
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/functions.php';

use FocusedTube\Security;
use FocusedTube\YouTubeAPI;
use FocusedTube\Template;

// Check authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$admin = new AdminFunctions();
$db = $GLOBALS['db'];
$youtube = new YouTubeAPI();

// Handle actions
$action = $_GET['action'] ?? 'list';

if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($admin->validateAction('import_video')) {
        $url = Security::sanitize($_POST['url'], 'url');
        $videoId = $youtube->extractVideoId($url);
        
        if (!$videoId) {
            $_SESSION['flash']['error'] = 'Invalid YouTube URL';
        } else {
            try {
                $metadata = $youtube->getVideoMetadata($videoId);
                
                if ($metadata) {
                    // Check if video already exists
                    $existing = $db->findOne('videos.json', ['id' => $videoId]);
                    
                    if ($existing) {
                        $_SESSION['flash']['warning'] = 'Video already exists in the library';
                    } else {
                        // Add video
                        $videoData = [
                            'id' => $videoId,
                            'title' => $metadata['title'],
                            'description' => $metadata['description'],
                            'channel_id' => $metadata['channel_id'],
                            'channel_name' => $metadata['channel_name'],
                            'category_id' => $metadata['category_id'],
                            'tags' => $metadata['tags'],
                            'thumbnail_url' => $metadata['thumbnail_url'],
                            'published_at' => $metadata['published_at'],
                            'duration' => $metadata['duration'],
                            'view_count' => $metadata['view_count'],
                            'like_count' => $metadata['like_count'],
                            'comment_count' => $metadata['comment_count'],
                            'embed_url' => $metadata['embed_url'],
                            'watch_url' => $metadata['watch_url'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'status' => 'published'
                        ];
                        
                        if ($db->insert('videos.json', $videoData)) {
                            $admin->logAction('Video Import', "Imported video: {$metadata['title']}");
                            $_SESSION['flash']['success'] = 'Video imported successfully';
                        } else {
                            $_SESSION['flash']['error'] = 'Failed to import video';
                        }
                    }
                } else {
                    $_SESSION['flash']['error'] = 'Video not found or API error';
                }
            } catch (\Exception $e) {
                $_SESSION['flash']['error'] = 'Error: ' . $e->getMessage();
            }
        }
        
        header('Location: videos.php');
        exit;
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    if ($admin->validateAction('delete_video')) {
        $video = $db->findById('videos.json', $_GET['id']);
        if ($video) {
            $db->deleteById('videos.json', $_GET['id']);
            $admin->logAction('Video Delete', "Deleted video: {$video['title']}");
            $_SESSION['flash']['success'] = 'Video deleted successfully';
        }
        header('Location: videos.php');
        exit;
    }
}

if ($action === 'toggle-status' && isset($_GET['id'])) {
    if ($admin->validateAction('toggle_video_status')) {
        $video = $db->findById('videos.json', $_GET['id']);
        if ($video) {
            $newStatus = $video['status'] === 'published' ? 'draft' : 'published';
            $db->updateById('videos.json', $_GET['id'], ['status' => $newStatus]);
            $admin->logAction('Video Status', "Changed video status to {$newStatus} for: {$video['title']}");
            $_SESSION['flash']['success'] = 'Video status updated successfully';
        }
        header('Location: videos.php');
        exit;
    }
}

if ($action === 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($admin->validateAction('edit_video')) {
        $updates = [
            'title' => Security::sanitize($_POST['title']),
            'description' => Security::sanitize($_POST['description']),
            'category_id' => Security::sanitize($_POST['category_id']),
            'tags' => array_map('trim', explode(',', Security::sanitize($_POST['tags']))),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($db->updateById('videos.json', $_GET['id'], $updates)) {
            $admin->logAction('Video Edit', "Updated video: {$_POST['title']}");
            $_SESSION['flash']['success'] = 'Video updated successfully';
        } else {
            $_SESSION['flash']['error'] = 'Failed to update video';
        }
        
        header('Location: videos.php');
        exit;
    }
}

// Get videos
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? Security::sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? Security::sanitize($_GET['status']) : '';
$category = isset($_GET['category']) ? Security::sanitize($_GET['category']) : '';

$videos = $db->read('videos.json');

// Apply filters
if ($search) {
    $videos = array_filter($videos, function($video) use ($search) {
        return stripos($video['title'], $search) !== false || 
               stripos($video['description'], $search) !== false ||
               stripos($video['channel_name'], $search) !== false;
    });
}

if ($status) {
    $videos = array_filter($videos, function($video) use ($status) {
        return ($video['status'] ?? 'published') === $status;
    });
}

if ($category) {
    $videos = array_filter($videos, function($video) use ($category) {
        return ($video['category_id'] ?? '') === $category;
    });
}

// Sort by created_at descending
usort($videos, function($a, $b) {
    return strtotime($b['created_at'] ?? $b['published_at']) - strtotime($a['created_at'] ?? $a['published_at']);
});

// Paginate
$totalVideos = count($videos);
$totalPages = ceil($totalVideos / ADMIN_ITEMS_PER_PAGE);
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * ADMIN_ITEMS_PER_PAGE;
$videos = array_slice($videos, $offset, ADMIN_ITEMS_PER_PAGE);

$pagination = [
    'items' => $videos,
    'total' => $totalVideos,
    'page' => $page,
    'perPage' => ADMIN_ITEMS_PER_PAGE,
    'totalPages' => $totalPages,
    'hasPrevious' => $page > 1,
    'hasNext' => $page < $totalPages
];

// Get categories for filter
$categories = $db->read('categories.json');

// Set page title
$pageTitle = 'Video Management - FocusedTube Admin';

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1 class="page-title">Video Management</h1>
        <p class="page-subtitle">Manage your video library</p>
    </div>
    
    <!-- Search and Filters -->
    <div class="table-header">
        <div class="admin-search">
            <form method="GET" action="" class="flex" style="gap: var(--spacing-sm); flex-wrap: wrap;">
                <input type="text" name="search" placeholder="Search videos..." value="<?php echo Security::escapeHtml($search); ?>">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                </select>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category === $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo Security::escapeHtml($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="videos.php" class="btn btn-outline">Reset</a>
            </form>
        </div>
        <div class="table-actions">
            <button class="btn btn-primary" onclick="openModal('importVideoModal')">
                <span>📥</span> Import Video
            </button>
        </div>
    </div>
    
    <!-- Videos Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Video</th>
                        <th>Channel</th>
                        <th>Views</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($videos)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary" style="padding: var(--spacing-2xl);">
                                <div class="admin-empty-state">
                                    <div class="empty-icon">📹</div>
                                    <h3>No Videos Found</h3>
                                    <p>Start by importing your first video from YouTube.</p>
                                    <button class="btn btn-primary" onclick="openModal('importVideoModal')" style="margin-top: var(--spacing-md);">
                                        Import Video
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($videos as $video): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div style="width: 80px; flex-shrink: 0;">
                                            <img src="<?php echo Security::escapeHtml($video['thumbnail_url']); ?>" 
                                                 alt="<?php echo Security::escapeHtml($video['title']); ?>" 
                                                 style="width: 100%; border-radius: var(--radius-sm);">
                                        </div>
                                        <div>
                                            <strong><?php echo Security::escapeHtml(substr($video['title'], 0, 50)) . (strlen($video['title']) > 50 ? '...' : ''); ?></strong>
                                            <div style="font-size: var(--font-size-xs); color: var(--text-tertiary);">
                                                <?php echo $admin->formatDuration($video['duration']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo Security::escapeHtml($video['channel_name']); ?></td>
                                <td><?php echo number_format($video['view_count'] ?? 0); ?></td>
                                <td>
                                    <span class="status-badge <?php echo ($video['status'] ?? 'published') === 'published' ? 'active' : 'inactive'; ?>">
                                        <?php echo ucfirst($video['status'] ?? 'Published'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($video['created_at'] ?? $video['published_at'])); ?></td>
                                <td>
                                    <div class="table-actions-cell">
                                        <button class="btn-view" onclick="editVideo('<?php echo $video['id']; ?>')" title="Edit">✏️</button>
                                        <a href="/watch?id=<?php echo $video['id']; ?>" target="_blank" class="btn-view" title="View">👁️</a>
                                        <a href="videos.php?action=toggle-status&id=<?php echo $video['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn-view" title="Toggle Status">
                                            <?php echo ($video['status'] ?? 'published') === 'published' ? '📄' : '📢'; ?>
                                        </a>
                                        <a href="videos.php?action=delete&id=<?php echo $video['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn-delete delete-confirm" title="Delete">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($pagination['totalPages'] > 1): ?>
        <ul class="pagination">
            <?php if ($pagination['hasPrevious']): ?>
                <li class="page-item">
                    <a href="?page=<?php echo $pagination['page'] - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>" class="page-link">
                        &laquo; Previous
                    </a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                <li class="page-item <?php echo $i === $pagination['page'] ? 'active' : ''; ?>">
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>" class="page-link">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if ($pagination['hasNext']): ?>
                <li class="page-item">
                    <a href="?page=<?php echo $pagination['page'] + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>" class="page-link">
                        Next &raquo;
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- Import Video Modal -->
<div id="importVideoModal" class="modal-overlay admin-modal" onclick="if(event.target === this) closeModal('importVideoModal')">
    <div class="modal">
        <div class="modal-header">
            <h3>Import Video from YouTube</h3>
            <button class="modal-close" onclick="closeModal('importVideoModal')">×</button>
        </div>
        <form method="POST" action="videos.php?action=import" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">YouTube URL</label>
                    <input type="url" name="url" class="form-input" 
                           placeholder="https://www.youtube.com/watch?v=..." required>
                    <small class="form-hint">
                        Paste any YouTube video URL. The system will automatically fetch all metadata.
                    </small>
                </div>
                
                <div class="form-group">
                    <div style="background: var(--bg-secondary); padding: var(--spacing-md); border-radius: var(--radius-sm);">
                        <p style="font-size: var(--font-size-sm); color: var(--text-secondary); margin: 0;">
                            💡 The video will be imported with:
                        </p>
                        <ul style="font-size: var(--font-size-sm); color: var(--text-tertiary); margin-top: var(--spacing-sm); padding-left: var(--spacing-lg);">
                            <li>Title & Description</li>
                            <li>Channel information</li>
                            <li>Thumbnail</li>
                            <li>Duration & Statistics</li>
                            <li>Tags & Category</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('importVideoModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Import Video</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Video Modal -->
<div id="editVideoModal" class="modal-overlay admin-modal" onclick="if(event.target === this) closeModal('editVideoModal')">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Video</h3>
            <button class="modal-close" onclick="closeModal('editVideoModal')">×</button>
        </div>
        <form method="POST" action="" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="5"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo Security::escapeHtml($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tags</label>
                        <input type="text" name="tags" class="form-input" 
                               placeholder="tag1, tag2, tag3">
                        <small class="form-hint">Comma separated tags</small>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editVideoModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Video</button>
            </div>
        </form>
    </div>
</div>

<script>
function editVideo(videoId) {
    fetch(`/admin/api/get-video.php?id=${videoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('editVideoModal');
                const form = modal.querySelector('form');
                form.action = `videos.php?action=edit&id=${videoId}`;
                
                form.querySelector('[name="title"]').value = data.video.title;
                form.querySelector('[name="description"]').value = data.video.description || '';
                form.querySelector('[name="category_id"]').value = data.video.category_id || '';
                form.querySelector('[name="tags"]').value = (data.video.tags || []).join(', ');
                
                openModal('editVideoModal');
            }
        })
        .catch(error => {
            console.error('Error fetching video:', error);
            showToast('error', 'Failed to load video data');
        });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>