<?php
/**
 * FocusedTube - Get Video API
 * 
 * Returns video data for editing in admin panel
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

use FocusedTube\Security;
use FocusedTube\Template;

// Check authentication and admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    Template::json(['error' => 'Unauthorized'], 403);
    exit;
}

$videoId = isset($_GET['id']) ? Security::sanitize($_GET['id']) : '';

if (empty($videoId)) {
    Template::json(['error' => 'Video ID required'], 400);
    exit;
}

global $db;
$video = $db->findById('videos.json', $videoId);

if (!$video) {
    Template::json(['error' => 'Video not found'], 404);
    exit;
}

Template::json([
    'success' => true,
    'video' => $video
]);