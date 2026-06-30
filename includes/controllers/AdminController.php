<?php
/**
 * FocusedTube - Admin Controller
 * 
 * Handles admin panel requests
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Controllers;

use FocusedTube\Security;
use FocusedTube\Template;

class AdminController extends Controller
{
    /**
     * Admin index - redirect to dashboard or login
     * 
     * @param array $params
     * @return mixed
     */
    public function index($params = [])
    {
        if ($this->isLoggedIn() && $this->hasRole('admin')) {
            header('Location: /admin/dashboard');
        } else {
            header('Location: /admin/login');
        }
        exit;
    }
    
    /**
     * Admin login
     * 
     * @param array $params
     * @return mixed
     */
    public function login($params = [])
    {
        // If already logged in as admin, redirect to dashboard
        if ($this->isLoggedIn() && $this->hasRole('admin')) {
            header('Location: /admin/dashboard');
            exit;
        }
        
        $error = null;
        
        // Handle login form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!isset($_POST['csrf_token']) || !check_csrf($_POST['csrf_token'])) {
                $error = 'Invalid security token. Please try again.';
            } else {
                $email = Security::sanitize($_POST['email'] ?? '', 'email');
                $password = $_POST['password'] ?? '';
                
                if (empty($email) || empty($password)) {
                    $error = 'Please enter both email and password.';
                } else {
                    $result = $this->auth->login($email, $password);
                    
                    if ($result === true) {
                        // Check if user is admin
                        if ($this->hasRole('admin')) {
                            header('Location: /admin/dashboard');
                            exit;
                        } else {
                            // Logout non-admin users
                            $this->auth->logout();
                            $error = 'Access denied. Admin privileges required.';
                        }
                    } else {
                        $error = $result;
                    }
                }
            }
        }
        
        // Generate CSRF token for login form
        $csrfToken = $_SESSION['csrf_token'] ?? Security::generateCsrfToken();
        
        // Include the admin login page
        include_once __DIR__ . '/../../admin/index.php';
    }
    
    /**
     * Admin logout
     * 
     * @param array $params
     * @return mixed
     */
    public function logout($params = [])
    {
        $this->auth->logout();
        header('Location: /admin/login');
        exit;
    }
    
    /**
     * Admin dashboard
     * 
     * @param array $params
     * @return mixed
     */
    public function dashboard($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        // Include the admin dashboard
        include_once __DIR__ . '/../../admin/dashboard.php';
    }
    
    /**
     * User management
     * 
     * @param array $params
     * @return mixed
     */
    public function users($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/users.php';
    }
    
    /**
     * Video management
     * 
     * @param array $params
     * @return mixed
     */
    public function videos($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/videos.php';
    }
    
    /**
     * Category management
     * 
     * @param array $params
     * @return mixed
     */
    public function categories($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/categories.php';
    }
    
    /**
     * Tag management
     * 
     * @param array $params
     * @return mixed
     */
    public function tags($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/tags.php';
    }
    
    /**
     * Settings
     * 
     * @param array $params
     * @return mixed
     */
    public function settings($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/settings.php';
    }
    
    /**
     * Analytics
     * 
     * @param array $params
     * @return mixed
     */
    public function analytics($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/analytics.php';
    }
    
    /**
     * Reports
     * 
     * @param array $params
     * @return mixed
     */
    public function reports($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/reports.php';
    }
    
    /**
     * Maintenance
     * 
     * @param array $params
     * @return mixed
     */
    public function maintenance($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/maintenance.php';
    }
    
    /**
     * Backups
     * 
     * @param array $params
     * @return mixed
     */
    public function backups($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/backups.php';
    }
    
    /**
     * Activity logs
     * 
     * @param array $params
     * @return mixed
     */
    public function activity($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/activity.php';
    }
    
    /**
     * API Settings
     * 
     * @param array $params
     * @return mixed
     */
    public function apiSettings($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/api-settings.php';
    }
    
    /**
     * Announcements
     * 
     * @param array $params
     * @return mixed
     */
    public function announcements($params = [])
    {
        if (!$this->isLoggedIn() || !$this->hasRole('admin')) {
            header('Location: /admin/login');
            exit;
        }
        
        include_once __DIR__ . '/../../admin/announcements.php';
    }
}