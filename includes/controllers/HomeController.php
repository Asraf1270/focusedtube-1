<?php
/**
 * FocusedTube - Home Controller
 * 
 * Handles home page requests
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Controllers;

use FocusedTube\Template;

class HomeController extends Controller
{
    /**
     * Display home page
     * 
     * @param array $params
     * @return mixed
     */
    public function index($params = [])
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = ITEMS_PER_PAGE;
        
        $videos = $this->db->read('videos.json');
        
        // Filter only published videos
        $videos = array_filter($videos, function($video) {
            return ($video['status'] ?? 'published') === 'published';
        });
        
        // Sort by created_at descending
        usort($videos, function($a, $b) {
            $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
            $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
            return $timeB - $timeA;
        });
        
        // Paginate
        $totalVideos = count($videos);
        $totalPages = ceil($totalVideos / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $paginatedVideos = array_slice($videos, $offset, $perPage);
        $hasMore = $page < $totalPages;
        
        // Set meta
        $metaTitle = APP_NAME . ' - Watch, Organize, Discover';
        $metaDescription = 'A self-hosted YouTube video library. Watch, organize, and discover videos without distractions.';
        
        // Include header
        include_once __DIR__ . '/../header.php';
        
        // Include home page content
        include_once __DIR__ . '/../../pages/home.php';
        
        // Include footer
        include_once __DIR__ . '/../footer.php';
    }
}