<?php
/**
 * FocusedTube - Category Controller
 * 
 * Handles category pages
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Controllers;

use FocusedTube\Security;

class CategoryController extends Controller
{
    /**
     * Display categories listing
     * 
     * @param array $params
     * @return mixed
     */
    public function index($params = [])
    {
        $categories = $this->db->read('categories.json');
        $videos = $this->db->read('videos.json');
        
        $publishedVideos = array_filter($videos, function($video) {
            return ($video['status'] ?? 'published') === 'published';
        });
        
        // Add video count to each category
        foreach ($categories as &$category) {
            $category['video_count'] = count(array_filter($publishedVideos, function($video) use ($category) {
                return ($video['category_id'] ?? '') === $category['id'];
            }));
        }
        
        // Sort categories by name
        usort($categories, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        // Set meta
        $metaTitle = 'Categories - ' . APP_NAME;
        $metaDescription = 'Browse videos by category on ' . APP_NAME;
        
        // Include header
        require_once INCLUDES_PATH . '/header.php';
        
        // Include categories page content
        require_once PAGES_PATH . '/categories.php';
        
        // Include footer
        require_once INCLUDES_PATH . '/footer.php';
    }
    
    /**
     * Display category videos
     * 
     * @param array $params
     * @return mixed
     */
    public function show($params = [])
    {
        $slug = $params['slug'] ?? '';
        
        if (empty($slug)) {
            header('Location: /categories');
            exit;
        }
        
        // Find category by slug or ID
        $categories = $this->db->read('categories.json');
        $category = null;
        
        foreach ($categories as $cat) {
            $catSlug = $cat['slug'] ?? strtolower(str_replace(' ', '-', $cat['name']));
            if ($catSlug === $slug || $cat['id'] === $slug) {
                $category = $cat;
                break;
            }
        }
        
        if (!$category) {
            header('Location: /categories');
            exit;
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = ITEMS_PER_PAGE;
        
        $videos = $this->db->read('videos.json');
        $categoryVideos = array_filter($videos, function($video) use ($category) {
            return ($video['category_id'] ?? '') === $category['id'] && 
                   ($video['status'] ?? 'published') === 'published';
        });
        
        // Sort by newest first
        usort($categoryVideos, function($a, $b) {
            $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
            $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
            return $timeB - $timeA;
        });
        
        // Paginate
        $totalVideos = count($categoryVideos);
        $totalPages = ceil($totalVideos / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $paginatedVideos = array_slice($categoryVideos, $offset, $perPage);
        
        // Set meta
        $metaTitle = Security::escapeHtml($category['name']) . ' - ' . APP_NAME;
        $metaDescription = Security::escapeHtml($category['description'] ?? "Watch videos in the {$category['name']} category on " . APP_NAME);
        
        // Include header
        require_once INCLUDES_PATH . '/header.php';
        
        // Include category page content
        require_once PAGES_PATH . '/category.php';
        
        // Include footer
        require_once INCLUDES_PATH . '/footer.php';
    }
}