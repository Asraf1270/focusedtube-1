<?php
/**
 * FocusedTube - Admin Login & Redirect
 * 
 * Handles admin login and redirects to dashboard if already logged in
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/init.php';

use FocusedTube\Security;
use FocusedTube\Auth;
use FocusedTube\Template;

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}

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
            $auth = new Auth();
            $result = $auth->login($email, $password);
            
            if ($result === true) {
                // Check if user is admin
                if ($_SESSION['user_role'] === 'admin') {
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // Logout non-admin users
                    $auth->logout();
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

// Set page title
$pageTitle = 'Admin Login - FocusedTube';

?><!DOCTYPE html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="icon" href="/assets/images/favicon.ico">
</head>
<body class="admin-login-page">
    <div class="login-container">
        <div class="login-box scale-in">
            <div class="login-header">
                <div class="login-brand">
                    <img src="/assets/images/logo.svg" alt="FocusedTube" height="40">
                    <h1>FocusedTube</h1>
                </div>
                <p class="login-subtitle">Admin Panel Login</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error fade-in">
                    <span class="alert-icon">⚠️</span>
                    <?php echo Security::escapeHtml($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           placeholder="admin@example.com" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Enter your password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <span class="btn-icon">🔐</span>
                        Login to Admin
                    </button>
                </div>
                
                <div class="login-footer">
                    <a href="/" class="text-link">← Return to Website</a>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        .admin-login-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: var(--spacing-md);
        }
        
        .login-box {
            background: var(--admin-card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-2xl);
            box-shadow: 0 20px 60px var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-sm);
        }
        
        .login-brand h1 {
            font-size: var(--font-size-2xl);
            margin: 0;
        }
        
        .login-brand img {
            height: 40px;
        }
        
        .login-subtitle {
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
            margin: 0;
        }
        
        .login-form .form-group {
            margin-bottom: var(--spacing-md);
        }
        
        .login-form .btn-block {
            width: 100%;
        }
        
        .login-footer {
            text-align: center;
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-color);
        }
        
        .login-footer .text-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: var(--font-size-sm);
            transition: color var(--transition-fast);
        }
        
        .login-footer .text-link:hover {
            color: var(--primary-color);
        }
        
        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-sm);
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            border-left: 4px solid;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-left-color: var(--error-color);
            color: var(--error-color);
        }
        
        .alert .alert-icon {
            font-size: var(--font-size-lg);
        }
    </style>
</body>
</html>