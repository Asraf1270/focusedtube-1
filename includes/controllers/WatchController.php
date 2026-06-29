<?php
/**
 * FocusedTube - Watch Controller
 * 
 * Handles video watch page requests
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Controllers;

use FocusedTube\Security;
use FocusedTube\YouTubeAPI;

class WatchController extends Controller
{
    /**
     * Display watch page
     * 
     * @param array $params
     * @return mixed
     */
    public function index($params = [])
    {
        $videoId = isset($_GET['id']) ? Security::sanitize($_GET['id'], 'youtube_id') : '';
        
        if (empty($videoId)) {
            header('Location: /');
            exit;
        }
        
        // Get video data
        $video = $this->db->findById('videos.json', $videoId);
        
        if (!$video || ($video['status'] ?? 'published') !== 'published') {
            // Try to import the video if it doesn't exist
            try {
                $youtube = new YouTubeAPI();
                $metadata = $youtube->getVideoMetadata($videoId);
                
                if ($metadata) {
                    // Import video
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
                    
                    $this->db->insert('videos.json', $videoData);
                    $video = $videoData;
                } else {
                    header('Location: /404');
                    exit;
                }
            } catch (\Exception $e) {
                header('Location: /404');
                exit;
            }
        }
        
        // Update view count
        $viewCount = ($video['view_count'] ?? 0) + 1;
        $this->db->updateById('videos.json', $videoId, ['view_count' => $viewCount]);
        
        // Record in history
        if ($this->isLoggedIn()) {
            $user = $this->getUser();
            $history = $this->db->findOne('history.json', [
                'user_id' => $user['id'],
                'video_id' => $videoId
            ]);
            
            if ($history) {
                $this->db->updateById('history.json', $history['id'], [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'count' => ($history['count'] ?? 0) + 1
                ]);
            } else {
                $this->db->insert('history.json', [
                    'id' => 'hist_' . uniqid(),
                    'user_id' => $user['id'],
                    'video_id' => $videoId,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'count' => 1
                ]);
            }
        }
        
        // Get related videos
        $allVideos = $this->db->read('videos.json');
        $relatedVideos = array_filter($allVideos, function($v) use ($videoId) {
            return $v['id'] !== $videoId && ($v['status'] ?? 'published') === 'published';
        });
        
        // Sort related by relevance
        usort($relatedVideos, function($a, $b) use ($video) {
            $scoreA = 0;
            $scoreB = 0;
            
            if (isset($a['category_id']) && isset($video['category_id']) && $a['category_id'] === $video['category_id']) {
                $scoreA += 10;
            }
            if (isset($b['category_id']) && isset($video['category_id']) && $b['category_id'] === $video['category_id']) {
                $scoreB += 10;
            }
            
            if (isset($a['tags']) && isset($video['tags'])) {
                $scoreA += count(array_intersect($a['tags'], $video['tags']));
            }
            if (isset($b['tags']) && isset($video['tags'])) {
                $scoreB += count(array_intersect($b['tags'], $video['tags']));
            }
            
            $scoreA += ($a['view_count'] ?? 0) / 1000;
            $scoreB += ($b['view_count'] ?? 0) / 1000;
            
            return $scoreB - $scoreA;
        });
        
        $relatedVideos = array_slice($relatedVideos, 0, 10);
        
        // Get comments
        $comments = $this->db->read('comments.json');
        $videoComments = array_filter($comments, function($comment) use ($videoId) {
            return $comment['video_id'] === $videoId;
        });
        
        usort($videoComments, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Get user interaction status
        $userLiked = false;
        $isFavorited = false;
        $isWatchLater = false;
        
        if ($this->isLoggedIn()) {
            $user = $this->getUser();
            
            $likes = $this->db->read('likes.json');
            $userLiked = !empty(array_filter($likes, function($like) use ($videoId, $user) {
                return $like['video_id'] === $videoId && 
                       $like['user_id'] === $user['id'] &&
                       $like['type'] === 'like';
            }));
            
            $favorites = $this->db->read('favorites.json');
            $isFavorited = !empty(array_filter($favorites, function($fav) use ($videoId, $user) {
                return $fav['video_id'] === $videoId && 
                       $fav['user_id'] === $user['id'];
            }));
            
            $watchLater = $this->db->read('watchlater.json');
            $isWatchLater = !empty(array_filter($watchLater, function($wl) use ($videoId, $user) {
                return $wl['video_id'] === $videoId && 
                       $wl['user_id'] === $user['id'];
            }));
        }
        
        // Set meta
        $metaTitle = Security::escapeHtml($video['title']) . ' - ' . APP_NAME;
        $metaDescription = Security::escapeHtml(substr($video['description'] ?? '', 0, 160));
        $metaImage = Security::escapeHtml($video['thumbnail_url']);
        
        // Structured Data
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $video['title'],
            'description' => $video['description'] ?? '',
            'thumbnailUrl' => $video['thumbnail_url'],
            'uploadDate' => $video['published_at'] ?? $video['created_at'],
            'duration' => 'PT' . ($video['duration'] ?? 0) . 'S',
            'embedUrl' => $video['embed_url'] ?? 'https://www.youtube.com/embed/' . $videoId,
            'interactionCount' => $video['view_count'] ?? 0,
            'author' => [
                '@type' => 'Person',
                'name' => $video['channel_name'] ?? ''
            ]
        ];
        
        // Include header
        include_once __DIR__ . '/../header.php';
        
        // Include watch page content
        include_once __DIR__ . '/../../pages/watch.php';
        
        // Include footer
        include_once __DIR__ . '/../footer.php';
    }
}