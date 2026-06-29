<?php
/**
 * FocusedTube - Like Controller
 * 
 * Handles like API operations
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Api;

use FocusedTube\Security;
use FocusedTube\ApiAuth;

class LikeController
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
     * Toggle like on a video
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
        
        $likes = $this->db->read('likes.json');
        
        // Check if user already liked
        $existingLike = null;
        $existingIndex = null;
        foreach ($likes as $index => $like) {
            if ($like['video_id'] === $videoId && $like['user_id'] === $user['id']) {
                $existingLike = $like;
                $existingIndex = $index;
                break;
            }
        }
        
        if ($existingLike) {
            // Remove like
            unset($likes[$existingIndex]);
            $likes = array_values($likes);
            $this->db->write('likes.json', $likes);
            
            // Update video like count
            $video['like_count'] = max(0, ($video['like_count'] ?? 0) - 1);
            $this->db->updateById('videos.json', $videoId, ['like_count' => $video['like_count']]);
            
            return [
                'status' => 200,
                'data' => [
                    'message' => 'Like removed',
                    'liked' => false,
                    'likes' => $video['like_count']
                ]
            ];
        } else {
            // Add like
            $like = [
                'id' => 'lik_' . uniqid(),
                'video_id' => $videoId,
                'user_id' => $user['id'],
                'type' => 'like',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $likes[] = $like;
            $this->db->write('likes.json', $likes);
            
            // Update video like count
            $video['like_count'] = ($video['like_count'] ?? 0) + 1;
            $this->db->updateById('videos.json', $videoId, ['like_count' => $video['like_count']]);
            
            return [
                'status' => 200,
                'data' => [
                    'message' => 'Video liked',
                    'liked' => true,
                    'likes' => $video['like_count']
                ]
            ];
        }
    }
    
    /**
     * Get like status for a video
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function get($input, $params)
    {
        $videoId = $params['videoId'] ?? '';
        
        if (empty($videoId)) {
            return ['status' => 400, 'data' => ['error' => 'Video ID required']];
        }
        
        $video = $this->db->findById('videos.json', $videoId);
        if (!$video) {
            return ['status' => 404, 'data' => ['error' => 'Video not found']];
        }
        
        $user = $this->apiAuth->getCurrentUser();
        $isLiked = false;
        
        if ($user) {
            $likes = $this->db->read('likes.json');
            $isLiked = !empty(array_filter($likes, function($like) use ($videoId, $user) {
                return $like['video_id'] === $videoId && 
                       $like['user_id'] === $user['id'];
            }));
        }
        
        return [
            'status' => 200,
            'data' => [
                'likes' => $video['like_count'] ?? 0,
                'liked' => $isLiked
            ]
        ];
    }
}