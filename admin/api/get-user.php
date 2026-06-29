<?php
/**
 * FocusedTube - Get User API
 * 
 * Returns user data for editing in admin panel
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

use FocusedTube\Security;
use FocusedTube\Template;
use FocusedTube\Auth;

// Check authentication and admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    Template::json(['error' => 'Unauthorized'], 403);
    exit;
}

$userId = isset($_GET['id']) ? Security::sanitize($_GET['id']) : '';

if (empty($userId)) {
    Template::json(['error' => 'User ID required'], 400);
    exit;
}

global $db;
$auth = new Auth();
$user = $auth->getUserById($userId);

if (!$user) {
    Template::json(['error' => 'User not found'], 404);
    exit;
}

// Remove sensitive data
unset($user['password']);

Template::json([
    'success' => true,
    'user' => $user
]);