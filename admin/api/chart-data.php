<?php
/**
 * FocusedTube - Chart Data API
 * 
 * Returns chart data for admin dashboard
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

use FocusedTube\Security;
use FocusedTube\Template;
use FocusedTube\AdminFunctions;

// Check authentication and admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    Template::json(['error' => 'Unauthorized'], 403);
    exit;
}

$period = isset($_GET['period']) ? (int)$_GET['period'] : 30;

// Get statistics
$admin = new AdminFunctions();
$stats = $admin->getDashboardStats();

// Get views data
$viewsData = $stats['views_data'] ?? [];

// Filter by period
if ($period < count($viewsData)) {
    $viewsData = array_slice($viewsData, -$period);
}

$labels = array_column($viewsData, 'date');
$values = array_column($viewsData, 'views');

Template::json([
    'labels' => $labels,
    'values' => $values
]);