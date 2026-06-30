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

use FocusedTube\Security;
use FocusedTube\YouTubeAPI;
use FocusedTube\Template;

// Check authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

global $db;
$youtube = new YouTubeAPI();

// Handle actions
$action = $_GET['action'] ?? 'list';

if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!isset($_POST['csrf_token']) || !check_csrf($_POST['csrf_token'])) {
        $_SESSION['flash']['error'] = 'Invalid security token';
        header('Location: videos.php');
        exit;
    }
    
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
                        $_SESSION['flash']['success'] = 'Video imported successfully: ' . $metadata['title'];
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

if ($action === 'delete' && isset($_GET['id'])) {
    if (!isset($_GET['csrf_token']) || !check_csrf($_GET['csrf_token'])) {
        $_SESSION['flash']['error'] = 'Invalid security token';
        header('Location: videos.php');
        exit;
    }
    
    $video = $db->findById('videos.json', $_GET['id']);
    if ($video) {
        $db->deleteById('videos.json', $_GET['id']);
        $_SESSION['flash']['success'] = 'Video deleted successfully';
    }
    header('Location: videos.php');
    exit;
}

if ($action === 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !check_csrf($_POST['csrf_token'])) {
        $_SESSION['flash']['error'] = 'Invalid security token';
        header('Location: videos.php');
        exit;
    }
    
    $updates = [
        'title' => Security::sanitize($_POST['title']),
        'description' => Security::sanitize($_POST['description']),
        'category_id' => Security::sanitize($_POST['category_id']),
        'tags' => array_map('trim', explode(',', Security::sanitize($_POST['tags']))),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($db->updateById('videos.json', $_GET['id'], $updates)) {
        $_SESSION['flash']['success'] = 'Video updated successfully';
    } else {
        $_SESSION['flash']['error'] = 'Failed to update video';
    }
    
    header('Location: videos.php');
    exit;
}

if ($action === 'toggle-status' && isset($_GET['id'])) {
    if (!isset($_GET['csrf_token']) || !check_csrf($_GET['csrf_token'])) {
        $_SESSION['flash']['error'] = 'Invalid security token';
        header('Location: videos.php');
        exit;
    }
    
    $video = $db->findById('videos.json', $_GET['id']);
    if ($video) {
        $newStatus = $video['status'] === 'published' ? 'draft' : 'published';
        $db->updateById('videos.json', $_GET['id'], ['status' => $newStatus]);
        $_SESSION['flash']['success'] = 'Video status updated successfully';
    }
    header('Location: videos.php');
    exit;
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
    $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
    $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
    return $timeB - $timeA;
});

// Paginate
$totalVideos = count($videos);
$totalPages = ceil($totalVideos / ADMIN_ITEMS_PER_PAGE);
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * ADMIN_ITEMS_PER_PAGE;
$paginatedVideos = array_slice($videos, $offset, ADMIN_ITEMS_PER_PAGE);

$pagination = [
    'items' => $paginatedVideos,
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
    
    <!-- Display Flash Messages -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="flash-container">
            <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                <div class="alert alert-<?php echo $type; ?> fade-in">
                    <?php echo Security::escapeHtml($message); ?>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash']); ?>
        </div>
    <?php endif; ?>
    
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
            <button class="btn btn-primary" onclick="openImportModal()">
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
                    <?php if (empty($paginatedVideos)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: var(--spacing-2xl);">
                                <div class="admin-empty-state">
                                    <div class="empty-icon">📹</div>
                                    <h3>No Videos Found</h3>
                                    <p>Start by importing your first video from YouTube.</p>
                                    <button class="btn btn-primary" onclick="openImportModal()" style="margin-top: var(--spacing-md);">
                                        Import Video
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paginatedVideos as $video): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div style="width: 80px; flex-shrink: 0;">
                                            <img src="<?php echo Security::escapeHtml($video['thumbnail_url']); ?>" 
                                                 alt="<?php echo Security::escapeHtml($video['title']); ?>" 
                                                 style="width: 100%; border-radius: var(--radius-sm);"
                                                 onerror="this.src='/assets/images/default-thumbnail.jpg'">
                                        </div>
                                        <div>
                                            <strong><?php echo Security::escapeHtml(substr($video['title'], 0, 50)) . (strlen($video['title']) > 50 ? '...' : ''); ?></strong>
                                            <div style="font-size: var(--font-size-xs); color: var(--text-tertiary);">
                                                <?php echo formatDuration($video['duration']); ?>
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
                                           class="btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this video?')">🗑️</a>
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
<div id="importModal" class="modal-overlay" onclick="if(event.target === this) closeImportModal()">
    <div class="modal">
        <div class="modal-header">
            <h3>Import Video from YouTube</h3>
            <button class="modal-close" onclick="closeImportModal()">×</button>
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
                <button type="button" class="btn btn-outline" onclick="closeImportModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Import Video</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Video Modal -->
<div id="editModal" class="modal-overlay" onclick="if(event.target === this) closeEditModal()">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Video</h3>
            <button class="modal-close" onclick="closeEditModal()">×</button>
        </div>
        <form method="POST" action="" class="admin-form" id="editForm">
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
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Video</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: var(--z-modal);
    display: none;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-md);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.modal-overlay.active {
    display: flex;
    animation: fadeIn 0.3s ease;
}

.modal {
    background: var(--bg-primary);
    border-radius: var(--radius-md);
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    animation: scaleIn 0.3s ease;
}

.modal-header {
    padding: var(--spacing-md) var(--spacing-lg);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: var(--font-size-xl);
    cursor: pointer;
    color: var(--text-tertiary);
    transition: color var(--transition-fast);
    padding: 0 var(--spacing-sm);
}

.modal-close:hover {
    color: var(--text-primary);
}

.modal-body {
    padding: var(--spacing-lg);
}

.modal-footer {
    padding: var(--spacing-md) var(--spacing-lg);
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-sm);
}

/* Flash Messages */
.flash-container {
    margin-bottom: var(--spacing-md);
}

.alert {
    padding: var(--spacing-md);
    border-radius: var(--radius-sm);
    margin-bottom: var(--spacing-sm);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-left: 4px solid;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border-left-color: var(--success-color);
    color: var(--success-color);
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border-left-color: var(--error-color);
    color: var(--error-color);
}

.alert-warning {
    background: rgba(245, 158, 11, 0.1);
    border-left-color: var(--warning-color);
    color: var(--warning-color);
}

.alert-info {
    background: rgba(59, 130, 246, 0.1);
    border-left-color: var(--info-color);
    color: var(--info-color);
}

.alert-close {
    background: none;
    border: none;
    font-size: var(--font-size-xl);
    cursor: pointer;
    color: inherit;
    opacity: 0.6;
    transition: opacity var(--transition-fast);
    padding: 0 var(--spacing-xs);
}

.alert-close:hover {
    opacity: 1;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes scaleIn {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .modal {
        max-width: 100%;
        margin: var(--spacing-md);
        max-height: 95vh;
    }
    
    .modal-body {
        padding: var(--spacing-md);
    }
    
    .admin-search form {
        flex-direction: column;
    }
    
    .admin-search input,
    .admin-search select {
        width: 100% !important;
    }
}
</style>

<script>
// Open Import Modal
function openImportModal() {
    document.getElementById('importModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close Import Modal
function closeImportModal() {
    document.getElementById('importModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Open Edit Modal
function openEditModal() {
    document.getElementById('editModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close Edit Modal
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Edit Video - Fetch video data and open edit modal
function editVideo(videoId) {
    fetch(`/admin/api/get-video.php?id=${videoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const form = document.getElementById('editForm');
                form.action = `videos.php?action=edit&id=${videoId}`;
                
                form.querySelector('[name="title"]').value = data.video.title || '';
                form.querySelector('[name="description"]').value = data.video.description || '';
                form.querySelector('[name="category_id"]').value = data.video.category_id || '';
                form.querySelector('[name="tags"]').value = (data.video.tags || []).join(', ');
                
                openEditModal();
            } else {
                alert('Failed to load video data: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error fetching video:', error);
            alert('Failed to load video data. Please check console for details.');
        });
}

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImportModal();
        closeEditModal();
    }
});

// Close modals on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeImportModal();
        closeEditModal();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>