<?php
/**
 * FocusedTube - YouTube API Handler
 * 
 * Handles all YouTube Data API v3 interactions
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube;

class YouTubeAPI
{
    /**
     * @var string $apiKey YouTube API key
     */
    private $apiKey;
    
    /**
     * @var string $baseUrl YouTube API base URL
     */
    private $baseUrl = 'https://www.googleapis.com/youtube/v3/';
    
    /**
     * @var Cache $cache Cache instance
     */
    private $cache;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $settings = $this->getSettings();
        $this->apiKey = $settings['youtube_api_key'] ?? '';
        $this->cache = new Cache();
    }
    
    /**
     * Get API settings
     * 
     * @return array
     */
    private function getSettings()
    {
        global $db;
        $settings = $db->read('settings.json');
        return $settings['youtube'] ?? [];
    }
    
    /**
     * Extract video ID from URL
     * 
     * @param string $url
     * @return string|null
     */
    public function extractVideoId($url)
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=)([\w-]{11})/',
            '/(?:youtu\.be\/)([\w-]{11})/',
            '/(?:youtube\.com\/embed\/)([\w-]{11})/',
            '/(?:youtube\.com\/v\/)([\w-]{11})/',
            '/(?:youtube\.com\/shorts\/)([\w-]{11})/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Get video metadata from YouTube API
     * 
     * @param string $videoId
     * @return array|null
     */
    public function getVideoMetadata($videoId)
    {
        // Check cache first
        $cacheKey = 'youtube_video_' . $videoId;
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }
        
        if (empty($this->apiKey)) {
            throw new \Exception('YouTube API key not configured');
        }
        
        $url = $this->baseUrl . 'videos';
        $params = [
            'id' => $videoId,
            'key' => $this->apiKey,
            'part' => 'snippet,contentDetails,statistics'
        ];
        
        $url .= '?' . http_build_query($params);
        
        $response = $this->makeRequest($url);
        
        if (!$response || !isset($response['items']) || empty($response['items'])) {
            return null;
        }
        
        $video = $response['items'][0];
        $metadata = $this->parseVideoData($video);
        
        // Cache the result
        $this->cache->set($cacheKey, $metadata, 3600); // Cache for 1 hour
        
        return $metadata;
    }
    
    /**
     * Parse video data from API response
     * 
     * @param array $video
     * @return array
     */
    private function parseVideoData($video)
    {
        $snippet = $video['snippet'] ?? [];
        $contentDetails = $video['contentDetails'] ?? [];
        $statistics = $video['statistics'] ?? [];
        
        return [
            'id' => $video['id'],
            'title' => $snippet['title'] ?? '',
            'description' => $snippet['description'] ?? '',
            'channel_id' => $snippet['channelId'] ?? '',
            'channel_name' => $snippet['channelTitle'] ?? '',
            'category_id' => $snippet['categoryId'] ?? '',
            'tags' => $snippet['tags'] ?? [],
            'thumbnail_url' => $this->getThumbnailUrl($snippet),
            'published_at' => $snippet['publishedAt'] ?? '',
            'duration' => $this->parseDuration($contentDetails['duration'] ?? 'PT0S'),
            'view_count' => $statistics['viewCount'] ?? 0,
            'like_count' => $statistics['likeCount'] ?? 0,
            'comment_count' => $statistics['commentCount'] ?? 0,
            'embed_url' => 'https://www.youtube.com/embed/' . $video['id'],
            'watch_url' => 'https://www.youtube.com/watch?v=' . $video['id']
        ];
    }
    
    /**
     * Get best thumbnail URL
     * 
     * @param array $snippet
     * @return string
     */
    private function getThumbnailUrl($snippet)
    {
        if (isset($snippet['thumbnails']['maxres']['url'])) {
            return $snippet['thumbnails']['maxres']['url'];
        } elseif (isset($snippet['thumbnails']['high']['url'])) {
            return $snippet['thumbnails']['high']['url'];
        } elseif (isset($snippet['thumbnails']['medium']['url'])) {
            return $snippet['thumbnails']['medium']['url'];
        } elseif (isset($snippet['thumbnails']['default']['url'])) {
            return $snippet['thumbnails']['default']['url'];
        }
        
        return '';
    }
    
    /**
     * Parse ISO 8601 duration to seconds
     * 
     * @param string $duration
     * @return int
     */
    private function parseDuration($duration)
    {
        $interval = new \DateInterval($duration);
        $seconds = 0;
        
        $seconds += $interval->days * 86400;
        $seconds += $interval->h * 3600;
        $seconds += $interval->i * 60;
        $seconds += $interval->s;
        
        return $seconds;
    }
    
    /**
     * Format duration for display
     * 
     * @param int $seconds
     * @return string
     */
    public function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%02d:%02d', $minutes, $seconds);
        }
    }
    
    /**
     * Make API request with error handling
     * 
     * @param string $url
     * @return array|null
     */
    private function makeRequest($url)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL Error: ' . $error);
        }
        
        if ($info['http_code'] !== 200) {
            throw new \Exception('YouTube API Error: HTTP ' . $info['http_code']);
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            $message = $data['error']['message'] ?? 'Unknown YouTube API error';
            throw new \Exception('YouTube API Error: ' . $message);
        }
        
        return $data;
    }
    
    /**
     * Search videos on YouTube
     * 
     * @param string $query
     * @param int $maxResults
     * @return array
     */
    public function searchVideos($query, $maxResults = 10)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('YouTube API key not configured');
        }
        
        $url = $this->baseUrl . 'search';
        $params = [
            'q' => $query,
            'key' => $this->apiKey,
            'part' => 'snippet',
            'type' => 'video',
            'maxResults' => $maxResults,
            'order' => 'relevance'
        ];
        
        $url .= '?' . http_build_query($params);
        
        $response = $this->makeRequest($url);
        
        if (!$response || !isset($response['items'])) {
            return [];
        }
        
        $results = [];
        foreach ($response['items'] as $item) {
            if ($item['id']['kind'] === 'youtube#video') {
                $results[] = [
                    'id' => $item['id']['videoId'],
                    'title' => $item['snippet']['title'],
                    'description' => $item['snippet']['description'],
                    'channel_name' => $item['snippet']['channelTitle'],
                    'channel_id' => $item['snippet']['channelId'],
                    'thumbnail_url' => $this->getThumbnailUrl($item['snippet']),
                    'published_at' => $item['snippet']['publishedAt'],
                    'kind' => 'video'
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get channel metadata
     * 
     * @param string $channelId
     * @return array|null
     */
    public function getChannelMetadata($channelId)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('YouTube API key not configured');
        }
        
        $url = $this->baseUrl . 'channels';
        $params = [
            'id' => $channelId,
            'key' => $this->apiKey,
            'part' => 'snippet,statistics'
        ];
        
        $url .= '?' . http_build_query($params);
        
        $response = $this->makeRequest($url);
        
        if (!$response || !isset($response['items']) || empty($response['items'])) {
            return null;
        }
        
        $channel = $response['items'][0];
        
        return [
            'id' => $channel['id'],
            'name' => $channel['snippet']['title'],
            'description' => $channel['snippet']['description'],
            'thumbnail_url' => $channel['snippet']['thumbnails']['default']['url'] ?? '',
            'subscriber_count' => $channel['statistics']['subscriberCount'] ?? 0,
            'video_count' => $channel['statistics']['videoCount'] ?? 0,
            'view_count' => $channel['statistics']['viewCount'] ?? 0
        ];
    }
    
    /**
     * Validate YouTube video exists
     * 
     * @param string $videoId
     * @return bool
     */
    public function validateVideo($videoId)
    {
        try {
            $metadata = $this->getVideoMetadata($videoId);
            return $metadata !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
}
?>