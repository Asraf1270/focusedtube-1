<?php
/**
 * FocusedTube - Create Admin User
 * 
 * Run this script once to create the initial admin user
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/includes/init.php';

use FocusedTube\Security;
use FocusedTube\Auth;

// Check if admin already exists
$db = new \FocusedTube\Database();
$users = $db->read('users.json');

$adminExists = false;
foreach ($users as $user) {
    if ($user['role'] === 'admin') {
        $adminExists = true;
        break;
    }
}

if ($adminExists) {
    echo "⚠️ Admin user already exists!\n";
    echo "Existing admin users:\n";
    foreach ($users as $user) {
        if ($user['role'] === 'admin') {
            echo "  - Email: " . $user['email'] . "\n";
            echo "  - Username: " . $user['username'] . "\n";
            echo "  - Status: " . $user['status'] . "\n\n";
        }
    }
    exit;
}

// Create admin user
$auth = new Auth();
$result = $auth->createUser([
    'email' => 'admin@focusedtube.com',
    'username' => 'admin',
    'password' => 'admin123',
    'role' => 'admin'
]);

if ($result === true) {
    echo "✅ Admin user created successfully!\n";
    echo "Email: admin@focusedtube.com\n";
    echo "Password: admin123\n";
    echo "\n⚠️ Please change this password immediately after logging in!\n";
} else {
    echo "❌ Failed to create admin user: " . $result . "\n";
}