<?php
/**
 * FocusedTube - User Management
 * 
 * Admin interface for managing users
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/functions.php';

use FocusedTube\Security;
use FocusedTube\Auth;
use FocusedTube\Template;

// Check authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$admin = new AdminFunctions();
$auth = new Auth();
$db = $GLOBALS['db'];

// Handle actions
$action = $_GET['action'] ?? 'list';

if ($action === 'delete' && isset($_GET['id'])) {
    if ($admin->validateAction('delete_user')) {
        $result = $auth->deleteUser($_GET['id']);
        if ($result === true) {
            $admin->logAction('User Delete', "Deleted user ID: {$_GET['id']}");
            $_SESSION['flash']['success'] = 'User deleted successfully';
        } else {
            $_SESSION['flash']['error'] = $result;
        }
        header('Location: users.php');
        exit;
    }
}

if ($action === 'toggle-status' && isset($_GET['id'])) {
    if ($admin->validateAction('toggle_user_status')) {
        $user = $auth->getUserById($_GET['id']);
        if ($user) {
            $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
            $db->updateById('users.json', $_GET['id'], ['status' => $newStatus]);
            $admin->logAction('User Status', "Changed user status to {$newStatus} for ID: {$_GET['id']}");
            $_SESSION['flash']['success'] = 'User status updated successfully';
        }
        header('Location: users.php');
        exit;
    }
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($admin->validateAction('add_user')) {
        $result = $auth->createUser([
            'email' => $_POST['email'],
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'role' => $_POST['role'] ?? 'user'
        ]);
        
        if ($result === true) {
            $admin->logAction('User Add', "Added new user: {$_POST['email']}");
            $_SESSION['flash']['success'] = 'User created successfully';
            header('Location: users.php');
            exit;
        } else {
            $error = $result;
        }
    }
}

if ($action === 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($admin->validateAction('edit_user')) {
        $userData = [
            'email' => $_POST['email'],
            'username' => $_POST['username'],
            'role' => $_POST['role'],
            'status' => $_POST['status']
        ];
        
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password'];
        }
        
        $result = $auth->updateUser($_GET['id'], $userData);
        
        if ($result === true) {
            $admin->logAction('User Edit', "Updated user: {$_POST['email']}");
            $_SESSION['flash']['success'] = 'User updated successfully';
            header('Location: users.php');
            exit;
        } else {
            $error = $result;
        }
    }
}

// Get users
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? Security::sanitize($_GET['search']) : '';
$role = isset($_GET['role']) ? Security::sanitize($_GET['role']) : '';
$status = isset($_GET['status']) ? Security::sanitize($_GET['status']) : '';

$users = $db->read('users.json');

// Apply filters
if ($search) {
    $users = array_filter($users, function($user) use ($search) {
        return stripos($user['email'], $search) !== false || 
               stripos($user['username'], $search) !== false;
    });
}

if ($role) {
    $users = array_filter($users, function($user) use ($role) {
        return $user['role'] === $role;
    });
}

if ($status) {
    $users = array_filter($users, function($user) use ($status) {
        return $user['status'] === $status;
    });
}

// Sort by created_at descending
usort($users, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Paginate
$totalUsers = count($users);
$totalPages = ceil($totalUsers / ADMIN_ITEMS_PER_PAGE);
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * ADMIN_ITEMS_PER_PAGE;
$users = array_slice($users, $offset, ADMIN_ITEMS_PER_PAGE);

$pagination = [
    'items' => $users,
    'total' => $totalUsers,
    'page' => $page,
    'perPage' => ADMIN_ITEMS_PER_PAGE,
    'totalPages' => $totalPages,
    'hasPrevious' => $page > 1,
    'hasNext' => $page < $totalPages
];

// Set page title
$pageTitle = 'User Management - FocusedTube Admin';

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage all registered users</p>
    </div>
    
    <!-- Search and Filters -->
    <div class="table-header">
        <div class="admin-search">
            <form method="GET" action="" class="flex" style="gap: var(--spacing-sm);">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo Security::escapeHtml($search); ?>">
                <select name="role">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="editor" <?php echo $role === 'editor' ? 'selected' : ''; ?>>Editor</option>
                    <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                </select>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="users.php" class="btn btn-outline">Reset</a>
            </form>
        </div>
        <div class="table-actions">
            <button class="btn btn-primary" onclick="openModal('addUserModal')">
                <span>➕</span> Add User
            </button>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary" style="padding: var(--spacing-2xl);">
                                <div class="admin-empty-state">
                                    <div class="empty-icon">👤</div>
                                    <h3>No Users Found</h3>
                                    <p>No users match your search criteria.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar-sm" style="width: 32px; height: 32px; border-radius: var(--radius-full); background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: var(--font-bold);">
                                            <?php echo strtoupper(substr($user['username'] ?? $user['email'], 0, 1)); ?>
                                        </div>
                                        <strong><?php echo Security::escapeHtml($user['username'] ?? $user['email']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo Security::escapeHtml($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'primary' : ($user['role'] === 'editor' ? 'info' : 'secondary'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['status'] ?? 'active'; ?>">
                                        <?php echo ucfirst($user['status'] ?? 'Active'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?></td>
                                <td>
                                    <div class="table-actions-cell">
                                        <button class="btn-edit" onclick="editUser('<?php echo $user['id']; ?>')" title="Edit">✏️</button>
                                        <a href="users.php?action=toggle-status&id=<?php echo $user['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn-view" title="Toggle Status">
                                            <?php echo ($user['status'] ?? 'active') === 'active' ? '🔒' : '🔓'; ?>
                                        </a>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <a href="users.php?action=delete&id=<?php echo $user['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                               class="btn-delete delete-confirm" title="Delete">🗑️</a>
                                        <?php endif; ?>
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
                    <a href="?page=<?php echo $pagination['page'] - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" class="page-link">
                        &laquo; Previous
                    </a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                <li class="page-item <?php echo $i === $pagination['page'] ? 'active' : ''; ?>">
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" class="page-link">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if ($pagination['hasNext']): ?>
                <li class="page-item">
                    <a href="?page=<?php echo $pagination['page'] + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" class="page-link">
                        Next &raquo;
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal-overlay admin-modal" onclick="if(event.target === this) closeModal('addUserModal')">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New User</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">×</button>
        </div>
        <form method="POST" action="users.php?action=add" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" required minlength="8">
                    <small class="form-hint">Minimum 8 characters</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="user">User</option>
                            <option value="editor">Editor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(userId) {
    // Fetch user data and populate edit modal
    fetch(`/admin/api/get-user.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('editUserModal');
                const form = modal.querySelector('form');
                form.action = `users.php?action=edit&id=${userId}`;
                
                form.querySelector('[name="email"]').value = data.user.email;
                form.querySelector('[name="username"]').value = data.user.username;
                form.querySelector('[name="role"]').value = data.user.role;
                form.querySelector('[name="status"]').value = data.user.status;
                
                openModal('editUserModal');
            }
        })
        .catch(error => {
            console.error('Error fetching user:', error);
            showToast('error', 'Failed to load user data');
        });
}
</script>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal-overlay admin-modal" onclick="if(event.target === this) closeModal('editUserModal')">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit User</h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">×</button>
        </div>
        <form method="POST" action="" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password (optional)</label>
                    <input type="password" name="password" class="form-input" minlength="8">
                    <small class="form-hint">Leave blank to keep current password</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="user">User</option>
                            <option value="editor">Editor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>