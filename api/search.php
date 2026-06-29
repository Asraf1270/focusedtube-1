<?php
/**
 * FocusedTube - Search Controller
 * 
 * Handles search API operations
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Api;

use FocusedTube\Security;

class SearchController
{
    private $db;
    
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Search videos
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function search($input, $params)
    {
        $query = isset($_GET['q']) ? Security::sanitize($_GET['q']) : '';
        $category = isset($_GET['category']) ? Security::sanitize($_GET['category']) : '';
        $sort = isset($_GET['sort']) ? Security::sanitize($_GET['sort']) : 'relevance';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 20;
        
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
        $total = count($videos);
        $totalPages = ceil($total / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $items = array_slice($videos, $offset, $perPage);
        
        return [
            'status' => 200,
            'data' => [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
                'hasMore' => $page < $totalPages
            ]
        ];
    }
    
    /**
     * Get search suggestions
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function suggestions($input, $params)
    {
        $query = isset($_GET['q']) ? Security::sanitize($_GET['q']) : '';
        
        if (empty($query) || strlen($query) < 2) {
            return ['status' => 200, 'data' => []];
        }
        
        $videos = $this->db->read('videos.json');
        $suggestions = [];
        $queryLower = strtolower($query);
        
        // Get matching titles
        foreach ($videos as $video) {
            if (($video['status'] ?? 'published') !== 'published') {
                continue;
            }
            
            $title = strtolower($video['title'] ?? '');
            if (strpos($title, $queryLower) !== false && count($suggestions) < 5) {
                $suggestions[] = $video['title'];
            }
        }
        
        // If not enough suggestions, add channel names
        if (count($suggestions) < 5) {
            foreach ($videos as $video) {
                if (($video['status'] ?? 'published') !== 'published') {
                    continue;
                }
                
                $channel = strtolower($video['channel_name'] ?? '');
                if (strpos($channel, $queryLower) !== false && count($suggestions) < 5) {
                    $suggestions[] = $video['channel_name'];
                }
            }
        }
        
        // If not enough suggestions, add tags
        if (count($suggestions) < 5) {
            foreach ($videos as $video) {
                if (($video['status'] ?? 'published') !== 'published') {
                    continue;
                }
                
                if (isset($video['tags']) && is_array($video['tags'])) {
                    foreach ($video['tags'] as $tag) {
                        if (strpos(strtolower($tag), $queryLower) !== false && count($suggestions) < 5) {
                            $suggestions[] = $tag;
                        }
                    }
                }
            }
        }
        
        // Remove duplicates
        $suggestions = array_unique($suggestions);
        $suggestions = array_values($suggestions);
        
        return ['status' => 200, 'data' => $suggestions];
    }
}