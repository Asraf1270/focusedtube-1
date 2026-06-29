<?php
/**
 * FocusedTube - Favorite Controller
 * 
 * Handles favorite API operations
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Api;

use FocusedTube\Security;
use FocusedTube\ApiAuth;

class FavoriteController
{
    private $db;
    private $apiAuth;
    
    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->apiAuth = new ApiAuth();
    }
    
    /**
     * Toggle favorite on a video
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function toggle($input, $params)
    {
        $user = $this->apiAuth->requireAuth();
        $videoId = $params['videoId'] ?? '';
        
        if (empty($videoId)) {
            return ['status' => 400, 'data' => ['error' => 'Video ID required']];
        }
        
        // Check if video exists
        $video = $this->db->findById('videos.json', $videoId);
        if (!$video || ($video['status'] ?? 'published') !== 'published') {
            return ['status' => 404, 'data' => ['error' => 'Video not found']];
        }
        
        $favorites = $this->db->read('favorites.json');
        
        // Check if already favorited
        $existing = null;
        foreach ($favorites as $index => $fav) {
            if ($fav['video_id'] === $videoId && $fav['user_id'] === $user['id']) {
                $existing = $index;
                break;
            }
        }
        
        if ($existing !== null) {
            // Remove from favorites
            unset($favorites[$existing]);
            $favorites = array_values($favorites);
            $this->db->write('favorites.json', $favorites);
            
            return [
                'status' => 200,
                'data' => [
                    'message' => 'Removed from favorites',
                    'favorited' => false
                ]
            ];
        } else {
            // Add to favorites
            $favorite = [
                'id' => 'fav_' . uniqid(),
                'video_id' => $videoId,
                'user_id' => $user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $favorites[] = $favorite;
            $this->db->write('favorites.json', $favorites);
            
            return [
                'status' => 200,
                'data' => [
                    'message' => 'Added to favorites',
                    'favorited' => true
                ]
            ];
        }
    }
    
    /**
     * Get user's favorites
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function index($input, $params)
    {
        $user = $this->apiAuth->requireAuth();
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 20;
        
        $favorites = $this->db->read('favorites.json');
        
        // Filter user's favorites
        $userFavorites = array_filter($favorites, function($fav) use ($user) {
            return $fav['user_id'] === $user['id'];
        });
        
        // Get video details for each favorite
        $videos = $this->db->read('videos.json');
        $videoMap = [];
        foreach ($videos as $video) {
            $videoMap[$video['id']] = $video;
        }
        
        $items = [];
        foreach ($userFavorites as $fav) {
            if (isset($videoMap[$fav['video_id']])) {
                $video = $videoMap[$fav['video_id']];
                if (($video['status'] ?? 'published') === 'published') {
                    $items[] = $video;
                }
            }
        }
        
        // Sort by favorite added date (newest first)
        usort($items, function($a, $b) use ($favorites) {
            $timeA = 0;
            $timeB = 0;
            foreach ($favorites as $fav) {
                if ($fav['video_id'] === $a['id']) {
                    $timeA = strtotime($fav['created_at']);
                }
                if ($fav['video_id'] === $b['id']) {
                    $timeB = strtotime($fav['created_at']);
                }
            }
            return $timeB - $timeA;
        });
        
        // Paginate
        $total = count($items);
        $totalPages = ceil($total / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($items, $offset, $perPage);
        
        return [
            'status' => 200,
            'data' => [
                'items' => $paginated,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages
            ]
        ];
    }
    
    /**
     * Remove from favorites
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function remove($input, $params)
    {
        $user = $this->apiAuth->requireAuth();
        $videoId = $params['videoId'] ?? '';
        
        if (empty($videoId)) {
            return ['status' => 400, 'data' => ['error' => 'Video ID required']];
        }
        
        $favorites = $this->db->read('favorites.json');
        
        // Find and remove
        foreach ($favorites as $index => $fav) {
            if ($fav['video_id'] === $videoId && $fav['user_id'] === $user['id']) {
                unset($favorites[$index]);
                $favorites = array_values($favorites);
                $this->db->write('favorites.json', $favorites);
                
                return ['status' => 200, 'data' => ['message' => 'Removed from favorites']];
            }
        }
        
        return ['status' => 404, 'data' => ['error' => 'Favorite not found']];
    }
}