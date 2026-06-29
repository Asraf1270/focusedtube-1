<?php
/**
 * FocusedTube - YouTube Controller
 * 
 * Handles YouTube API operations
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Api;

use FocusedTube\Security;
use FocusedTube\YouTubeAPI;
use FocusedTube\ApiAuth;

class YouTubeController
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
     * Import video from YouTube URL
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function import($input, $params)
    {
        // Require admin authentication
        $this->apiAuth->requireAdmin();
        
        $url = $input['url'] ?? '';
        
        if (empty($url)) {
            return ['status' => 400, 'data' => ['error' => 'YouTube URL required']];
        }
        
        $youtube = new YouTubeAPI();
        $videoId = $youtube->extractVideoId($url);
        
        if (!$videoId) {
            return ['status' => 400, 'data' => ['error' => 'Invalid YouTube URL']];
        }
        
        try {
            $metadata = $youtube->getVideoMetadata($videoId);
            
            if (!$metadata) {
                return ['status' => 404, 'data' => ['error' => 'Video not found']];
            }
            
            // Check if video already exists
            $existing = $this->db->findById('videos.json', $videoId);
            
            if ($existing) {
                return ['status' => 409, 'data' => ['error' => 'Video already exists']];
            }
            
            // Add video
            $videoData = [
                'id' => $videoId,
                'title' => $metadata['title'],
                'description' => $metadata['description'],
                'channel_id' => $metadata['channel_id'],
                'channel_name' => $metadata['channel_name'],
                'category_id' => $metadata['category_id'],
                'tags' => $metadata['tags'],
                'thumbnail_url' => $metadata['thumbnail_url'],
                'published_at' => $metadata['published_at'],
                'duration' => $metadata['duration'],
                'view_count' => $metadata['view_count'],
                'like_count' => $metadata['like_count'],
                'comment_count' => $metadata['comment_count'],
                'embed_url' => $metadata['embed_url'],
                'watch_url' => $metadata['watch_url'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'status' => 'published'
            ];
            
            if ($this->db->insert('videos.json', $videoData)) {
                // Log activity
                $this->logActivity('Video Import', "Imported video: {$metadata['title']}");
                return ['status' => 201, 'data' => [
                    'message' => 'Video imported successfully',
                    'video' => $videoData
                ]];
            }
            
            return ['status' => 500, 'data' => ['error' => 'Failed to import video']];
            
        } catch (\Exception $e) {
            return ['status' => 500, 'data' => ['error' => $e->getMessage()]];
        }
    }
    
    /**
     * Get YouTube video metadata
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function metadata($input, $params)
    {
        $videoId = $params['videoId'] ?? '';
        
        if (empty($videoId)) {
            return ['status' => 400, 'data' => ['error' => 'Video ID required']];
        }
        
        $youtube = new YouTubeAPI();
        
        try {
            $metadata = $youtube->getVideoMetadata($videoId);
            
            if (!$metadata) {
                return ['status' => 404, 'data' => ['error' => 'Video not found']];
            }
            
            return ['status' => 200, 'data' => $metadata];
            
        } catch (\Exception $e) {
            return ['status' => 500, 'data' => ['error' => $e->getMessage()]];
        }
    }
    
    /**
     * Log activity
     * 
     * @param string $action
     * @param string $description
     */
    private function logActivity($action, $description)
    {
        $activity = [
            'id' => 'act_' . uniqid(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'action' => $action,
            'description' => $description,
            'ip' => Security::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('activity.json', $activity);
    }
}