<?php
/**
 * FocusedTube - Video Controller
 * 
 * Handles video API operations
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Api;

use FocusedTube\Security;
use FocusedTube\YouTubeAPI;
use FocusedTube\ApiAuth;

class VideoController
{
    private $db;
    private $auth;
    private $apiAuth;
    
    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->auth = new \FocusedTube\Auth();
        $this->apiAuth = new ApiAuth();
    }
    
    /**
     * Get list of videos with pagination and filters
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function index($input, $params)
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 20;
        $category = isset($_GET['category']) ? Security::sanitize($_GET['category']) : '';
        $tag = isset($_GET['tag']) ? Security::sanitize($_GET['tag']) : '';
        $sort = isset($_GET['sort']) ? Security::sanitize($_GET['sort']) : 'newest';
        
        $videos = $this->db->read('videos.json');
        
        // Filter published videos
        $videos = array_filter($videos, function($video) {
            return ($video['status'] ?? 'published') === 'published';
        });
        
        // Filter by category
        if ($category) {
            $videos = array_filter($videos, function($video) use ($category) {
                return ($video['category_id'] ?? '') === $category;
            });
        }
        
        // Filter by tag
        if ($tag) {
            $videos = array_filter($videos, function($video) use ($tag) {
                return in_array($tag, $video['tags'] ?? []);
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
            case 'popular':
                usort($videos, function($a, $b) {
                    return ($b['view_count'] ?? 0) - ($a['view_count'] ?? 0);
                });
                break;
            case 'views':
                usort($videos, function($a, $b) {
                    return ($b['view_count'] ?? 0) - ($a['view_count'] ?? 0);
                });
                break;
            default:
                // Default sort by newest
                usort($videos, function($a, $b) {
                    $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
                    $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
                    return $timeB - $timeA;
                });
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
     * Get single video by ID
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function show($input, $params)
    {
        $videoId = $params['id'] ?? '';
        
        if (empty($videoId)) {
            return ['status' => 400, 'data' => ['error' => 'Video ID required']];
        }
        
        $video = $this->db->findById('videos.json', $videoId);
        
        if (!$video || ($video['status'] ?? 'published') !== 'published') {
            return ['status' => 404, 'data' => ['error' => 'Video not found']];
        }
        
        // Get related videos
        $allVideos = $this->db->read('videos.json');
        $related = array_filter($allVideos, function($v) use ($videoId) {
            return $v['id'] !== $videoId && ($v['status'] ?? 'published') === 'published';
        });
        
        // Sort related by relevance
        usort($related, function($a, $b) use ($video) {
            $scoreA = 0;
            $scoreB = 0;
            
            // Same category
            if (isset($a['category_id']) && isset($video['category_id']) && $a['category_id'] === $video['category_id']) {
                $scoreA += 10;
            }
            if (isset($b['category_id']) && isset($video['category_id']) && $b['category_id'] === $video['category_id']) {
                $scoreB += 10;
            }
            
            // Shared tags
            if (isset($a['tags']) && isset($video['tags'])) {
                $scoreA += count(array_intersect($a['tags'], $video['tags']));
            }
            if (isset($b['tags']) && isset($video['tags'])) {
                $scoreB += count(array_intersect($b['tags'], $video['tags']));
            }
            
            return $scoreB - $scoreA;
        });
        
        $related = array_slice($related, 0, 10);
        
        // Get user interaction status
        $isLiked = false;
        $isFavorited = false;
        $isWatchLater = false;
        
        $user = $this->apiAuth->getCurrentUser();
        if ($user) {
            // Check like status
            $likes = $this->db->read('likes.json');
            $isLiked = !empty(array_filter($likes, function($like) use ($videoId, $user) {
                return $like['video_id'] === $videoId && 
                       $like['user_id'] === $user['id'] &&
                       $like['type'] === 'like';
            }));
            
            // Check favorite status
            $favorites = $this->db->read('favorites.json');
            $isFavorited = !empty(array_filter($favorites, function($fav) use ($videoId, $user) {
                return $fav['video_id'] === $videoId && 
                       $fav['user_id'] === $user['id'];
            }));
            
            // Check watch later status
            $watchLater = $this->db->read('watchlater.json');
            $isWatchLater = !empty(array_filter($watchLater, function($wl) use ($videoId, $user) {
                return $wl['video_id'] === $videoId && 
                       $wl['user_id'] === $user['id'];
            }));
        }
        
        return [
            'status' => 200,
            'data' => [
                'video' => $video,
                'related' => $related,
                'user_interaction' => [
                    'liked' => $isLiked,
                    'favorited' => $isFavorited,
                    'watch_later' => $isWatchLater
                ]
            ]
        ];
    }
    
    /**
     * Get related videos
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function related($input, $params)
    {
        $videoId = $params['id'] ?? '';
        
        if (empty($videoId)) {
            return ['status' => 400, 'data' => ['error' => 'Video ID required']];
        }
        
        $video = $this->db->findById('videos.json', $videoId);
        
        if (!$video) {
            return ['status' => 404, 'data' => ['error' => 'Video not found']];
        }
        
        $allVideos = $this->db->read('videos.json');
        $related = array_filter($allVideos, function($v) use ($videoId) {
            return $v['id'] !== $videoId && ($v['status'] ?? 'published') === 'published';
        });
        
        // Sort by relevance
        usort($related, function($a, $b) use ($video) {
            $scoreA = 0;
            $scoreB = 0;
            
            // Same category
            if (isset($a['category_id']) && isset($video['category_id']) && $a['category_id'] === $video['category_id']) {
                $scoreA += 10;
            }
            if (isset($b['category_id']) && isset($video['category_id']) && $b['category_id'] === $video['category_id']) {
                $scoreB += 10;
            }
            
            // Shared tags
            if (isset($a['tags']) && isset($video['tags'])) {
                $scoreA += count(array_intersect($a['tags'], $video['tags']));
            }
            if (isset($b['tags']) && isset($video['tags'])) {
                $scoreB += count(array_intersect($b['tags'], $video['tags']));
            }
            
            // View count weight
            $scoreA += ($a['view_count'] ?? 0) / 1000;
            $scoreB += ($b['view_count'] ?? 0) / 1000;
            
            return $scoreB - $scoreA;
        });
        
        return [
            'status' => 200,
            'data' => array_slice($related, 0, 10)
        ];
    }
    
    /**
     * Import video from YouTube
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
                return ['status' => 201, 'data' => ['message' => 'Video imported successfully', 'video' => $videoData]];
            }
            
            return ['status' => 500, 'data' => ['error' => 'Failed to import video']];
            
        } catch (\Exception $e) {
            return ['status' => 500, 'data' => ['error' => $e->getMessage()]];
        }
    }
    
    /**
     * Update video
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function update($input, $params)
    {
        // Require admin authentication
        $this->apiAuth->requireAdmin();
        
        $videoId = $params['id'] ?? '';
        
        if (empty($videoId)) {
            return ['status' => 400, 'data' => ['error' => 'Video ID required']];
        }
        
        $video = $this->db->findById('videos.json', $videoId);
        
        if (!$video) {
            return ['status' => 404, 'data' => ['error' => 'Video not found']];
        }
        
        $updates = [];
        
        if (isset($input['title'])) {
            $updates['title'] = Security::sanitize($input['title']);
        }
        
        if (isset($input['description'])) {
            $updates['description'] = Security::sanitize($input['description']);
        }
        
        if (isset($input['category_id'])) {
            $updates['category_id'] = Security::sanitize($input['category_id']);
        }
        
        if (isset($input['tags']) && is_array($input['tags'])) {
            $updates['tags'] = array_map([Security::class, 'sanitize'], $input['tags']);
        }
        
        if (isset($input['status'])) {
            $updates['status'] = Security::sanitize($input['status']);
        }
        
        if (empty($updates)) {
            return ['status' => 400, 'data' => ['error' => 'No updates provided']];
        }
        
        $updates['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->updateById('videos.json', $videoId, $updates)) {
            $this->logActivity('Video Edit', "Updated video: {$video['title']}");
            return ['status' => 200, 'data' => ['message' => 'Video updated successfully']];
        }
        
        return ['status' => 500, 'data' => ['error' => 'Failed to update video']];
    }
    
    /**
     * Delete video
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function delete($input, $params)
    {
        // Require admin authentication
        $this->apiAuth->requireAdmin();
        
        $videoId = $params['id'] ?? '';
        
        if (empty($videoId)) {
            return ['status' => 400, 'data' => ['error' => 'Video ID required']];
        }
        
        $video = $this->db->findById('videos.json', $videoId);
        
        if (!$video) {
            return ['status' => 404, 'data' => ['error' => 'Video not found']];
        }
        
        if ($this->db->deleteById('videos.json', $videoId)) {
            $this->logActivity('Video Delete', "Deleted video: {$video['title']}");
            return ['status' => 200, 'data' => ['message' => 'Video deleted successfully']];
        }
        
        return ['status' => 500, 'data' => ['error' => 'Failed to delete video']];
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