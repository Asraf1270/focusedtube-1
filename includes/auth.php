<?php
/**
 * FocusedTube - Authentication Handler
 * 
 * Handles user authentication, roles, and permissions
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube;

class Auth
{
    /**
     * @var Database $db Database instance
     */
    private $db;
    
    /**
     * @var array $user Current user data
     */
    private $user = null;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
        
        // Check if user is logged in
        if ($this->isLoggedIn()) {
            $this->user = $this->getUserById($_SESSION['user_id']);
        }
    }
    
    /**
     * Login user
     * 
     * @param string $email
     * @param string $password
     * @return bool|string
     */
    public function login($email, $password)
    {
        // Rate limiting
        $ip = Security::getClientIp();
        if (!Security::rateLimit('login_' . $ip, 5, 300)) {
            return 'Too many login attempts. Please try again later.';
        }
        
        $users = $this->db->read('users.json');
        
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                if (Security::verifyPassword($password, $user['password'])) {
                    // Check if password needs rehash
                    if (Security::needsRehash($user['password'])) {
                        $user['password'] = Security::hashPassword($password);
                        $this->db->updateById('users.json', $user['id'], ['password' => $user['password']]);
                    }
                    
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    
                    // Update last login
                    $this->db->updateById('users.json', $user['id'], [
                        'last_login' => date('Y-m-d H:i:s'),
                        'last_ip' => Security::getClientIp()
                    ]);
                    
                    // Log activity
                    $this->logActivity($user['id'], 'Login', 'User logged in');
                    
                    return true;
                }
                break;
            }
        }
        
        return 'Invalid email or password';
    }
    
    /**
     * Logout user
     */
    public function logout()
    {
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'Logout', 'User logged out');
        }
        
        $_SESSION = [];
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['login_time']) &&
               (time() - $_SESSION['login_time']) < SESSION_LIFETIME;
    }
    
    /**
     * Get current user
     * 
     * @return array|null
     */
    public function getUser()
    {
        if ($this->isLoggedIn()) {
            if ($this->user === null) {
                $this->user = $this->getUserById($_SESSION['user_id']);
            }
            return $this->user;
        }
        return null;
    }
    
    /**
     * Get user by ID
     * 
     * @param string $id
     * @return array|null
     */
    public function getUserById($id)
    {
        return $this->db->findById('users.json', $id);
    }
    
    /**
     * Get user by email
     * 
     * @param string $email
     * @return array|null
     */
    public function getUserByEmail($email)
    {
        return $this->db->findOne('users.json', ['email' => $email]);
    }
    
    /**
     * Create a new user
     * 
     * @param array $data
     * @return bool|string
     */
    public function createUser($data)
    {
        // Validate input
        $validation = Security::validate($data, [
            'email' => ['required', 'email'],
            'username' => ['required', 'min:3', 'max:50'],
            'password' => ['required', 'min:8'],
            'role' => ['in:user,editor,admin']
        ]);
        
        if ($validation !== true) {
            return $validation;
        }
        
        // Check if email already exists
        if ($this->getUserByEmail($data['email'])) {
            return 'Email already registered';
        }
        
        // Check if username already exists
        $users = $this->db->read('users.json');
        foreach ($users as $user) {
            if ($user['username'] === $data['username']) {
                return 'Username already taken';
            }
        }
        
        // Create user
        $user = [
            'id' => 'user_' . uniqid(),
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => Security::hashPassword($data['password']),
            'role' => $data['role'] ?? 'user',
            'status' => 'active',
            'avatar' => $data['avatar'] ?? null,
            'bio' => $data['bio'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'last_login' => null,
            'last_ip' => null,
            'settings' => [
                'theme' => DEFAULT_THEME,
                'language' => DEFAULT_LANGUAGE,
                'notifications' => true,
                'auto_play' => true
            ]
        ];
        
        if ($this->db->insert('users.json', $user)) {
            $this->logActivity($user['id'], 'Registration', 'User registered');
            return true;
        }
        
        return 'Failed to create user';
    }
    
    /**
     * Update user
     * 
     * @param string $id
     * @param array $data
     * @return bool|string
     */
    public function updateUser($id, $data)
    {
        $user = $this->getUserById($id);
        if (!$user) {
            return 'User not found';
        }
        
        $updates = [];
        
        if (isset($data['email'])) {
            $validation = Security::validate(['email' => $data['email']], [
                'email' => ['required', 'email']
            ]);
            if ($validation !== true) {
                return $validation;
            }
            
            // Check if email exists for other user
            $existing = $this->getUserByEmail($data['email']);
            if ($existing && $existing['id'] !== $id) {
                return 'Email already registered';
            }
            
            $updates['email'] = $data['email'];
        }
        
        if (isset($data['username'])) {
            $validation = Security::validate(['username' => $data['username']], [
                'username' => ['required', 'min:3', 'max:50']
            ]);
            if ($validation !== true) {
                return $validation;
            }
            
            $updates['username'] = $data['username'];
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $validation = Security::validate(['password' => $data['password']], [
                'password' => ['required', 'min:8']
            ]);
            if ($validation !== true) {
                return $validation;
            }
            
            $updates['password'] = Security::hashPassword($data['password']);
        }
        
        if (isset($data['role'])) {
            $validation = Security::validate(['role' => $data['role']], [
                'role' => ['in:user,editor,admin']
            ]);
            if ($validation !== true) {
                return $validation;
            }
            
            $updates['role'] = $data['role'];
        }
        
        if (isset($data['status'])) {
            $validation = Security::validate(['status' => $data['status']], [
                'status' => ['in:active,inactive,suspended']
            ]);
            if ($validation !== true) {
                return $validation;
            }
            
            $updates['status'] = $data['status'];
        }
        
        if (isset($data['avatar'])) {
            $updates['avatar'] = $data['avatar'];
        }
        
        if (isset($data['bio'])) {
            $updates['bio'] = Security::sanitize($data['bio'], 'string');
        }
        
        if (isset($data['settings'])) {
            $updates['settings'] = array_merge($user['settings'], $data['settings']);
        }
        
        $updates['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->updateById('users.json', $id, $updates)) {
            $this->logActivity($id, 'Profile Update', 'User updated profile');
            return true;
        }
        
        return 'Failed to update user';
    }
    
    /**
     * Delete user
     * 
     * @param string $id
     * @return bool
     */
    public function deleteUser($id)
    {
        if ($id === $_SESSION['user_id']) {
            return 'Cannot delete your own account';
        }
        
        $user = $this->getUserById($id);
        if (!$user) {
            return false;
        }
        
        $this->logActivity($id, 'Deletion', 'User account deleted');
        return $this->db->deleteById('users.json', $id);
    }
    
    /**
     * Check if user has role
     * 
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user = $this->getUser();
        return $user && ($user['role'] === $role || $user['role'] === 'admin');
    }
    
    /**
     * Check if user has permission
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        
        // Admin has all permissions
        if ($user['role'] === 'admin') {
            return true;
        }
        
        // Define permissions by role
        $permissions = [
            'editor' => [
                'create_video',
                'edit_video',
                'delete_video',
                'manage_categories',
                'manage_tags',
                'manage_comments'
            ],
            'user' => [
                'view_video',
                'comment',
                'like',
                'create_playlist',
                'manage_playlist',
                'watch_later',
                'favorites'
            ]
        ];
        
        if (isset($permissions[$user['role']])) {
            return in_array($permission, $permissions[$user['role']]);
        }
        
        return false;
    }
    
    /**
     * Log user activity
     * 
     * @param string $userId
     * @param string $action
     * @param string $description
     */
    private function logActivity($userId, $action, $description)
    {
        $activity = [
            'id' => 'act_' . uniqid(),
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip' => Security::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('activity.json', $activity);
    }
    
    /**
     * Get user activity
     * 
     * @param string $userId
     * @param int $limit
     * @return array
     */
    public function getUserActivity($userId, $limit = 50)
    {
        $activities = $this->db->read('activity.json');
        $result = [];
        
        foreach ($activities as $activity) {
            if ($activity['user_id'] === $userId) {
                $result[] = $activity;
            }
        }
        
        // Sort by timestamp descending
        usort($result, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($result, 0, $limit);
    }
    
    /**
     * Get all users with pagination
     * 
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getUsers($page = 1, $perPage = ADMIN_ITEMS_PER_PAGE)
    {
        return $this->db->paginate('users.json', $page, $perPage);
    }
    
    /**
     * Get user count
     * 
     * @return int
     */
    public function getUserCount()
    {
        return $this->db->count('users.json');
    }
}

// Create global auth instance
$auth = new Auth();
?>