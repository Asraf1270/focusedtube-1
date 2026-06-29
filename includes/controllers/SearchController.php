<?php
/**
 * FocusedTube - Search Controller
 * 
 * Handles search page requests
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Controllers;

use FocusedTube\Security;

class SearchController extends Controller
{
    /**
     * Display search page
     * 
     * @param array $params
     * @return mixed
     */
    public function index($params = [])
    {
        $query = isset($_GET['q']) ? Security::sanitize($_GET['q']) : '';
        $category = isset($_GET['category']) ? Security::sanitize($_GET['category']) : '';
        $sort = isset($_GET['sort']) ? Security::sanitize($_GET['sort']) : 'relevance';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = ITEMS_PER_PAGE;
        
        $videos = $this->db->read('videos.json');
        
        // Filter published videos
        $videos = array_filter($videos, function($video) {
            return ($video['status'] ?? 'published') === 'published';
        });
        
        // Search
        if ($query) {
            $searchTerms = explode(' ', strtolower($query));
            $videos = array_filter($videos, function($video) use ($searchTerms) {
                $title = strtolower($video['title'] ?? '');
                $description = strtolower($video['description'] ?? '');
                $channel = strtolower($video['channel_name'] ?? '');
                $tags = array_map('strtolower', $video['tags'] ?? []);
                
                foreach ($searchTerms as $term) {
                    if (strpos($title, $term) !== false ||
                        strpos($description, $term) !== false ||
                        strpos($channel, $term) !== false) {
                        return true;
                    }
                    foreach ($tags as $tag) {
                        if (strpos($tag, $term) !== false) {
                            return true;
                        }
                    }
                }
                return false;
            });
        }
        
        // Filter by category
        if ($category) {
            $videos = array_filter($videos, function($video) use ($category) {
                return ($video['category_id'] ?? '') === $category;
            });
        }
        
        // Sort
        switch ($sort) {
            case 'newest':
                usort($videos, function($a, $b) {
                    $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
                    $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
                    return $timeB - $timeA;
                });
                break;
            case 'oldest':
                usort($videos, function($a, $b) {
                    $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
                    $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
                    return $timeA - $timeB;
                });
                break;
            case 'views':
                usort($videos, function($a, $b) {
                    return ($b['view_count'] ?? 0) - ($a['view_count'] ?? 0);
                });
                break;
            case 'relevance':
            default:
                if ($query) {
                    $queryLower = strtolower($query);
                    usort($videos, function($a, $b) use ($queryLower) {
                        $aTitle = strtolower($a['title'] ?? '');
                        $bTitle = strtolower($b['title'] ?? '');
                        $aScore = strpos($aTitle, $queryLower) !== false ? 1 : 0;
                        $bScore = strpos($bTitle, $queryLower) !== false ? 1 : 0;
                        return $bScore - $aScore;
                    });
                }
                break;
        }
        
        // Paginate
        $totalVideos = count($videos);
        $totalPages = ceil($totalVideos / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $paginatedVideos = array_slice($videos, $offset, $perPage);
        
        // Get categories for filter
        $categories = $this->db->read('categories.json');
        
        // Set meta
        $metaTitle = ($query ? "Search: {$query} - " : "") . APP_NAME;
        $metaDescription = $query ? "Search results for '{$query}'" : "Search for videos";
        
        // Include header
        include_once __DIR__ . '/../header.php';
        
        // Include search page content
        include_once __DIR__ . '/../../pages/search.php';
        
        // Include footer
        include_once __DIR__ . '/../footer.php';
    }
}