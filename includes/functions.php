<?php
/**
 * FocusedTube - Global Functions
 * 
 * Collection of reusable helper functions for the entire application
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;

// ============================================
// FORMATTING FUNCTIONS
// ============================================

/**
 * Format duration in seconds to HH:MM:SS
 * 
 * @param int $seconds
 * @return string
 */
function formatDuration($seconds)
{
    if (empty($seconds) || !is_numeric($seconds)) {
        return '00:00';
    }
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
    return sprintf('%02d:%02d', $minutes, $secs);
}

/**
 * Format number with K/M suffixes
 * 
 * @param int $number
 * @return string
 */
function formatNumber($number)
{
    if (empty($number) || !is_numeric($number)) {
        return '0';
    }
    
    $number = (int)$number;
    
    if ($number >= 1000000000) {
        return round($number / 1000000000, 1) . 'B';
    }
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    }
    if ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return (string)$number;
}

/**
 * Format file size
 * 
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function formatFileSize($bytes, $precision = 2)
{
    if ($bytes === 0) {
        return '0 B';
    }
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $i = floor(log($bytes, 1024));
    $size = $bytes / pow(1024, $i);
    
    return round($size, $precision) . ' ' . $units[$i];
}

/**
 * Get time ago string (e.g., "5 minutes ago")
 * 
 * @param string|int $timestamp
 * @return string
 */
function timeAgo($timestamp)
{
    if (empty($timestamp)) {
        return 'Just now';
    }
    
    if (is_string($timestamp)) {
        $time = strtotime($timestamp);
    } else {
        $time = (int)$timestamp;
    }
    
    if (!$time) {
        return 'Just now';
    }
    
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 0) {
        return 'Just now';
    }
    
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . 'm ago';
    }
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . 'h ago';
    }
    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . 'd ago';
    }
    if ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . 'w ago';
    }
    if ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . 'mo ago';
    }
    
    $years = floor($diff / 31536000);
    return $years . 'y ago';
}

/**
 * Format date for display
 * 
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'M d, Y')
{
    if (empty($date)) {
        return '';
    }
    
    $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format date with time
 * 
 * @param string $date
 * @return string
 */
function formatDateTime($date)
{
    return formatDate($date, 'M d, Y g:i A');
}

/**
 * Get relative time with full date fallback
 * 
 * @param string $timestamp
 * @return string
 */
function getRelativeTime($timestamp)
{
    $time = is_numeric($timestamp) ? (int)$timestamp : strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 86400) {
        return timeAgo($timestamp);
    }
    
    if ($diff < 604800) {
        return timeAgo($timestamp);
    }
    
    return formatDate($timestamp);
}

// ============================================
// STRING FUNCTIONS
// ============================================

/**
 * Truncate text with ellipsis
 * 
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncateText($text, $length = 100, $suffix = '...')
{
    $text = strip_tags($text);
    
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate URL-friendly slug
 * 
 * @param string $string
 * @param string $separator
 * @return string
 */
function createSlug($string, $separator = '-')
{
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', ' ', $string);
    $string = trim($string);
    $string = str_replace(' ', $separator, $string);
    return $string;
}

/**
 * Generate random string
 * 
 * @param int $length
 * @param string $characters
 * @return string
 */
function randomString($length = 16, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Generate UUID v4
 * 
 * @return string
 */
function generateUUID()
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Highlight search terms in text
 * 
 * @param string $text
 * @param string|array $search
 * @param string $highlightClass
 * @return string
 */
function highlightText($text, $search, $highlightClass = 'highlight')
{
    if (empty($search) || empty($text)) {
        return $text;
    }
    
    $search = is_array($search) ? $search : [$search];
    $text = htmlspecialchars($text);
    
    foreach ($search as $term) {
        $term = trim($term);
        if (!empty($term)) {
            $text = preg_replace('/' . preg_quote($term, '/') . '/i', 
                '<span class="' . $highlightClass . '">$0</span>', 
                $text);
        }
    }
    
    return $text;
}

/**
 * Clean HTML content
 * 
 * @param string $html
 * @return string
 */
function cleanHtml($html)
{
    // Remove script tags
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    // Remove style tags
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
    // Remove iframe tags
    $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);
    // Remove object tags
    $html = preg_replace('/<object\b[^>]*>(.*?)<\/object>/is', '', $html);
    // Remove embed tags
    $html = preg_replace('/<embed\b[^>]*>/is', '', $html);
    
    return $html;
}

// ============================================
// ARRAY FUNCTIONS
// ============================================

/**
 * Check if array is associative
 * 
 * @param array $array
 * @return bool
 */
function isAssocArray($array)
{
    if (!is_array($array)) {
        return false;
    }
    return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * Get array value with default
 * 
 * @param array $array
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function array_get($array, $key, $default = null)
{
    if (!is_array($array)) {
        return $default;
    }
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Get nested array value with dot notation
 * 
 * @param array $array
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function array_get_nested($array, $key, $default = null)
{
    if (!is_array($array)) {
        return $default;
    }
    
    $keys = explode('.', $key);
    $value = $array;
    
    foreach ($keys as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    
    return $value;
}

/**
 * Pluck specific field from array of objects/arrays
 * 
 * @param array $items
 * @param string $field
 * @param string $keyField
 * @return array
 */
function array_pluck($items, $field, $keyField = null)
{
    $result = [];
    
    foreach ($items as $key => $item) {
        if (is_array($item)) {
            $value = isset($item[$field]) ? $item[$field] : null;
            if ($keyField !== null) {
                $resultKey = isset($item[$keyField]) ? $item[$keyField] : $key;
                $result[$resultKey] = $value;
            } else {
                $result[] = $value;
            }
        } elseif (is_object($item)) {
            $value = isset($item->$field) ? $item->$field : null;
            if ($keyField !== null) {
                $resultKey = isset($item->$keyField) ? $item->$keyField : $key;
                $result[$resultKey] = $value;
            } else {
                $result[] = $value;
            }
        }
    }
    
    return $result;
}

/**
 * Group array by field
 * 
 * @param array $array
 * @param string $field
 * @return array
 */
function array_group_by($array, $field)
{
    $result = [];
    
    foreach ($array as $item) {
        if (is_array($item)) {
            $key = isset($item[$field]) ? $item[$field] : 'unknown';
        } elseif (is_object($item)) {
            $key = isset($item->$field) ? $item->$field : 'unknown';
        } else {
            continue;
        }
        
        if (!isset($result[$key])) {
            $result[$key] = [];
        }
        $result[$key][] = $item;
    }
    
    return $result;
}

// ============================================
// URL FUNCTIONS
// ============================================

/**
 * Get full URL
 * 
 * @param string $path
 * @return string
 */
function fullUrl($path = '')
{
    return SITE_URL . '/' . ltrim($path, '/');
}

/**
 * Get current URL
 * 
 * @return string
 */
function currentUrl()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}

/**
 * Get video URL
 * 
 * @param string $videoId
 * @return string
 */
function videoUrl($videoId)
{
    return fullUrl('/watch?id=' . $videoId);
}

/**
 * Get YouTube embed URL
 * 
 * @param string $videoId
 * @param array $params
 * @return string
 */
function youtubeEmbedUrl($videoId, $params = [])
{
    $defaults = [
        'autoplay' => 0,
        'rel' => 0,
        'modestbranding' => 1,
        'enablejsapi' => 1
    ];
    
    $params = array_merge($defaults, $params);
    $query = http_build_query($params);
    
    return 'https://www.youtube.com/embed/' . $videoId . '?' . $query;
}

/**
 * Get YouTube thumbnail URL
 * 
 * @param string $videoId
 * @param string $quality
 * @return string
 */
function youtubeThumbnail($videoId, $quality = 'maxresdefault')
{
    $qualities = [
        'default' => 'default',
        'mqdefault' => 'mqdefault',
        'hqdefault' => 'hqdefault',
        'sddefault' => 'sddefault',
        'maxresdefault' => 'maxresdefault'
    ];
    
    $quality = isset($qualities[$quality]) ? $qualities[$quality] : 'maxresdefault';
    return 'https://img.youtube.com/vi/' . $videoId . '/' . $quality . '.jpg';
}

// ============================================
// VIDEO FUNCTIONS
// ============================================

/**
 * Get video by ID
 * 
 * @param string $videoId
 * @return array|null
 */
function getVideo($videoId)
{
    global $db;
    return $db->findById('videos.json', $videoId);
}

/**
 * Get published videos
 * 
 * @param int $limit
 * @param string $order
 * @return array
 */
function getPublishedVideos($limit = null, $order = 'newest')
{
    global $db;
    
    $videos = $db->read('videos.json');
    
    // Filter published
    $videos = array_filter($videos, function($video) {
        return ($video['status'] ?? 'published') === 'published';
    });
    
    // Sort
    switch ($order) {
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
            usort($videos, function($a, $b) {
                $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
                $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
                return $timeB - $timeA;
            });
            break;
    }
    
    if ($limit !== null) {
        $videos = array_slice($videos, 0, $limit);
    }
    
    return array_values($videos);
}

/**
 * Get related videos
 * 
 * @param array $video
 * @param int $limit
 * @return array
 */
function getRelatedVideos($video, $limit = 10)
{
    global $db;
    
    $allVideos = $db->read('videos.json');
    $related = array_filter($allVideos, function($v) use ($video) {
        return $v['id'] !== $video['id'] && ($v['status'] ?? 'published') === 'published';
    });
    
    // Sort by relevance
    usort($related, function($a, $b) use ($video) {
        $scoreA = 0;
        $scoreB = 0;
        
        // Same category (10 points)
        if (isset($a['category_id']) && isset($video['category_id']) && $a['category_id'] === $video['category_id']) {
            $scoreA += 10;
        }
        if (isset($b['category_id']) && isset($video['category_id']) && $b['category_id'] === $video['category_id']) {
            $scoreB += 10;
        }
        
        // Shared tags (1 point per tag)
        if (isset($a['tags']) && isset($video['tags'])) {
            $scoreA += count(array_intersect($a['tags'], $video['tags']));
        }
        if (isset($b['tags']) && isset($video['tags'])) {
            $scoreB += count(array_intersect($b['tags'], $video['tags']));
        }
        
        // Same channel (5 points)
        if (isset($a['channel_id']) && isset($video['channel_id']) && $a['channel_id'] === $video['channel_id']) {
            $scoreA += 5;
        }
        if (isset($b['channel_id']) && isset($video['channel_id']) && $b['channel_id'] === $video['channel_id']) {
            $scoreB += 5;
        }
        
        // View count weight
        $scoreA += ($a['view_count'] ?? 0) / 10000;
        $scoreB += ($b['view_count'] ?? 0) / 10000;
        
        return $scoreB - $scoreA;
    });
    
    return array_slice($related, 0, $limit);
}

/**
 * Get video categories
 * 
 * @return array
 */
function getVideoCategories()
{
    global $db;
    return $db->read('categories.json');
}

/**
 * Get videos by category
 * 
 * @param string $categoryId
 * @param int $limit
 * @return array
 */
function getVideosByCategory($categoryId, $limit = null)
{
    global $db;
    
    $videos = $db->read('videos.json');
    $videos = array_filter($videos, function($video) use ($categoryId) {
        return ($video['category_id'] ?? '') === $categoryId && 
               ($video['status'] ?? 'published') === 'published';
    });
    
    // Sort by newest
    usort($videos, function($a, $b) {
        $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
        $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
        return $timeB - $timeA;
    });
    
    if ($limit !== null) {
        $videos = array_slice($videos, 0, $limit);
    }
    
    return array_values($videos);
}

// ============================================
// USER FUNCTIONS
// ============================================

/**
 * Get user by ID
 * 
 * @param string $userId
 * @return array|null
 */
function getUser($userId)
{
    global $db;
    return $db->findById('users.json', $userId);
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['login_time']) &&
           (time() - $_SESSION['login_time']) < SESSION_LIFETIME;
}

/**
 * Check if user is admin
 * 
 * @return bool
 */
function isAdmin()
{
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current user
 * 
 * @return array|null
 */
function currentUser()
{
    if (!isLoggedIn()) {
        return null;
    }
    return getUser($_SESSION['user_id']);
}

/**
 * Get user avatar URL
 * 
 * @param array $user
 * @param int $size
 * @return string
 */
function getUserAvatar($user, $size = 40)
{
    if (isset($user['avatar']) && !empty($user['avatar'])) {
        return $user['avatar'];
    }
    
    $email = isset($user['email']) ? $user['email'] : '';
    $hash = md5(strtolower(trim($email)));
    $d = 'mp';
    $s = $size;
    
    return "https://www.gravatar.com/avatar/{$hash}?d={$d}&s={$s}";
}

// ============================================
// COMMENT FUNCTIONS
// ============================================

/**
 * Get video comments
 * 
 * @param string $videoId
 * @param int $limit
 * @return array
 */
function getVideoComments($videoId, $limit = null)
{
    global $db;
    
    $comments = $db->read('comments.json');
    $comments = array_filter($comments, function($comment) use ($videoId) {
        return $comment['video_id'] === $videoId && 
               ($comment['status'] ?? 'published') === 'published';
    });
    
    usort($comments, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    if ($limit !== null) {
        $comments = array_slice($comments, 0, $limit);
    }
    
    return array_values($comments);
}

/**
 * Get comment count for video
 * 
 * @param string $videoId
 * @return int
 */
function getCommentCount($videoId)
{
    global $db;
    
    $comments = $db->read('comments.json');
    $count = 0;
    
    foreach ($comments as $comment) {
        if ($comment['video_id'] === $videoId && 
            ($comment['status'] ?? 'published') === 'published') {
            $count++;
        }
    }
    
    return $count;
}

// ============================================
// VALIDATION FUNCTIONS
// ============================================

/**
 * Validate email
 * 
 * @param string $email
 * @return bool
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate YouTube URL
 * 
 * @param string $url
 * @return bool
 */
function isYouTubeUrl($url)
{
    $patterns = [
        '/youtube\.com\/watch\?v=[\w-]{11}/',
        '/youtu\.be\/[\w-]{11}/',
        '/youtube\.com\/embed\/[\w-]{11}/',
        '/youtube\.com\/v\/[\w-]{11}/',
        '/youtube\.com\/shorts\/[\w-]{11}/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Extract YouTube video ID
 * 
 * @param string $url
 * @return string|null
 */
function extractYouTubeId($url)
{
    $patterns = [
        '/youtube\.com\/watch\?v=([\w-]{11})/',
        '/youtu\.be\/([\w-]{11})/',
        '/youtube\.com\/embed\/([\w-]{11})/',
        '/youtube\.com\/v\/([\w-]{11})/',
        '/youtube\.com\/shorts\/([\w-]{11})/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Validate YouTube video ID
 * 
 * @param string $videoId
 * @return bool
 */
function isValidYouTubeId($videoId)
{
    return preg_match('/^[\w-]{11}$/', $videoId) === 1;
}

// ============================================
// CACHE FUNCTIONS
// ============================================

/**
 * Get cached data
 * 
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function cache_get($key, $default = null)
{
    if (!CACHE_ENABLED) {
        return $default;
    }
    
    $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
    
    if (!file_exists($cacheFile)) {
        return $default;
    }
    
    $data = unserialize(file_get_contents($cacheFile));
    
    if ($data === false || $data['expires'] < time()) {
        @unlink($cacheFile);
        return $default;
    }
    
    return $data['data'];
}

/**
 * Set cached data
 * 
 * @param string $key
 * @param mixed $value
 * @param int $lifetime
 * @return bool
 */
function cache_set($key, $value, $lifetime = CACHE_LIFETIME)
{
    if (!CACHE_ENABLED) {
        return false;
    }
    
    $data = [
        'expires' => time() + $lifetime,
        'data' => $value
    ];
    
    $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
    return file_put_contents($cacheFile, serialize($data), LOCK_EX) !== false;
}

/**
 * Delete cached data
 * 
 * @param string $key
 * @return bool
 */
function cache_delete($key)
{
    $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    return true;
}

/**
 * Clear all cache
 * 
 * @return bool
 */
function cache_clear()
{
    $files = glob(CACHE_PATH . '/*.cache');
    $success = true;
    
    foreach ($files as $file) {
        if (is_file($file) && !unlink($file)) {
            $success = false;
        }
    }
    
    return $success;
}

// ============================================
// SOCIAL FUNCTIONS
// ============================================

/**
 * Get social share URLs
 * 
 * @param string $url
 * @param string $title
 * @return array
 */
function getShareUrls($url, $title = '')
{
    $encodedUrl = urlencode($url);
    $encodedTitle = urlencode($title);
    
    return [
        'facebook' => "https://www.facebook.com/sharer/sharer.php?u={$encodedUrl}",
        'twitter' => "https://twitter.com/intent/tweet?url={$encodedUrl}&text={$encodedTitle}",
        'whatsapp' => "https://api.whatsapp.com/send?text={$encodedTitle}%20{$encodedUrl}",
        'telegram' => "https://t.me/share/url?url={$encodedUrl}&text={$encodedTitle}",
        'linkedin' => "https://www.linkedin.com/sharing/share-offsite/?url={$encodedUrl}",
        'email' => "mailto:?subject={$encodedTitle}&body={$encodedUrl}",
        'reddit' => "https://reddit.com/submit?url={$encodedUrl}&title={$encodedTitle}",
        'pinterest' => "https://pinterest.com/pin/create/button/?url={$encodedUrl}&description={$encodedTitle}"
    ];
}

// ============================================
// BROWSER DETECTION
// ============================================

/**
 * Get browser information
 * 
 * @return array
 */
function getBrowserInfo()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $browsers = [
        'Edge' => 'Edg',
        'Chrome' => 'Chrome',
        'Firefox' => 'Firefox',
        'Safari' => 'Safari',
        'Opera' => 'OPR',
        'IE' => 'MSIE'
    ];
    
    $browser = 'Unknown';
    $version = 'Unknown';
    
    foreach ($browsers as $name => $token) {
        if (strpos($userAgent, $token) !== false) {
            $browser = $name;
            break;
        }
    }
    
    // Get version
    if ($browser !== 'Unknown') {
        preg_match('/' . $browser . '\/([0-9.]+)/', $userAgent, $matches);
        if (isset($matches[1])) {
            $version = $matches[1];
        }
    }
    
    return [
        'browser' => $browser,
        'version' => $version,
        'user_agent' => $userAgent
    ];
}

/**
 * Check if request is from mobile
 * 
 * @return bool
 */
function isMobile()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $mobilePatterns = [
        'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 
        'Windows Phone', 'Opera Mini', 'Mobile'
    ];
    
    foreach ($mobilePatterns as $pattern) {
        if (strpos($userAgent, $pattern) !== false) {
            return true;
        }
    }
    
    return false;
}

// ============================================
// SESSION FUNCTIONS
// ============================================

/**
 * Set flash message
 * 
 * @param string $type
 * @param string $message
 */
function flash($type, $message)
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get flash message
 * 
 * @param string $type
 * @param bool $clear
 * @return string|null
 */
function getFlash($type, $clear = true)
{
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        if ($clear) {
            unset($_SESSION['flash'][$type]);
        }
        return $message;
    }
    return null;
}

/**
 * Display flash messages
 * 
 * @return string
 */
function displayFlash()
{
    $output = '';
    $types = ['success', 'error', 'warning', 'info'];
    
    foreach ($types as $type) {
        $message = getFlash($type);
        if ($message) {
            $output .= '<div class="flash flash-' . $type . ' fade-in">';
            $output .= '<span>' . Security::escapeHtml($message) . '</span>';
            $output .= '<button class="flash-close" onclick="this.parentElement.remove()">×</button>';
            $output .= '</div>';
        }
    }
    
    return $output;
}

// ============================================
// LOGGING FUNCTIONS
// ============================================

/**
 * Log error
 * 
 * @param string $message
 * @param array $context
 */
function logError($message, $context = [])
{
    $log = date('Y-m-d H:i:s') . ' - ERROR: ' . $message;
    if (!empty($context)) {
        $log .= ' - Context: ' . json_encode($context);
    }
    $log .= "\n";
    file_put_contents(LOGS_PATH . '/errors.log', $log, FILE_APPEND);
}

/**
 * Log info
 * 
 * @param string $message
 * @param array $context
 */
function logInfo($message, $context = [])
{
    $log = date('Y-m-d H:i:s') . ' - INFO: ' . $message;
    if (!empty($context)) {
        $log .= ' - Context: ' . json_encode($context);
    }
    $log .= "\n";
    file_put_contents(LOGS_PATH . '/info.log', $log, FILE_APPEND);
}

/**
 * Log debug
 * 
 * @param string $message
 * @param array $context
 */
function logDebug($message, $context = [])
{
    if (ENVIRONMENT !== 'production') {
        $log = date('Y-m-d H:i:s') . ' - DEBUG: ' . $message;
        if (!empty($context)) {
            $log .= ' - Context: ' . json_encode($context);
        }
        $log .= "\n";
        file_put_contents(LOGS_PATH . '/debug.log', $log, FILE_APPEND);
    }
}

// ============================================
// MISC FUNCTIONS
// ============================================

/**
 * Get pagination HTML
 * 
 * @param int $total
 * @param int $page
 * @param int $perPage
 * @param string $url
 * @return string
 */
function pagination($total, $page, $perPage = 20, $url = '')
{
    if ($total <= $perPage) {
        return '';
    }
    
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    
    $html = '<ul class="pagination">';
    
    // Previous
    if ($page > 1) {
        $html .= '<li class="page-item">';
        $html .= '<a href="' . $url . '?page=' . ($page - 1) . '" class="page-link">';
        $html .= '&laquo; Previous';
        $html .= '</a>';
        $html .= '</li>';
    }
    
    // Page numbers
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a href="' . $url . '?page=1" class="page-link">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $page) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a href="' . $url . '?page=' . $i . '" class="page-link">' . $i . '</a></li>';
        }
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a href="' . $url . '?page=' . $totalPages . '" class="page-link">' . $totalPages . '</a></li>';
    }
    
    // Next
    if ($page < $totalPages) {
        $html .= '<li class="page-item">';
        $html .= '<a href="' . $url . '?page=' . ($page + 1) . '" class="page-link">';
        $html .= 'Next &raquo;';
        $html .= '</a>';
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    
    return $html;
}

/**
 * Get sorted array by field
 * 
 * @param array $array
 * @param string $field
 * @param string $order
 * @return array
 */
function sortArray($array, $field, $order = 'asc')
{
    usort($array, function($a, $b) use ($field, $order) {
        $valueA = is_array($a) ? ($a[$field] ?? '') : ($a->$field ?? '');
        $valueB = is_array($b) ? ($b[$field] ?? '') : ($b->$field ?? '');
        
        if ($valueA == $valueB) {
            return 0;
        }
        
        $result = $valueA < $valueB ? -1 : 1;
        return $order === 'desc' ? -$result : $result;
    });
    
    return $array;
}

/**
 * Get IP address
 * 
 * @return string
 */
function getIpAddress()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    
    // Validate IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }
    
    return $ip;
}

/**
 * Get country from IP
 * 
 * @param string $ip
 * @return string|null
 */
function getCountryFromIp($ip = null)
{
    if ($ip === null) {
        $ip = getIpAddress();
    }
    
    // Local IP addresses
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return 'Local';
    }
    
    // Use free IP API service
    try {
        $response = @file_get_contents('http://ip-api.com/json/' . $ip . '?fields=country');
        if ($response) {
            $data = json_decode($response, true);
            return $data['country'] ?? 'Unknown';
        }
    } catch (\Exception $e) {
        // Silent fail
    }
    
    return 'Unknown';
}

/**
 * Dump variable (for debugging)
 * 
 * @param mixed $var
 * @param bool $die
 */
function dd($var, $die = true)
{
    echo '<pre style="background: #f5f5f5; padding: 20px; border-radius: 8px; border: 1px solid #ddd; font-family: monospace; font-size: 14px; max-height: 500px; overflow: auto;">';
    var_dump($var);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}

/**
 * Print array (for debugging)
 * 
 * @param array $array
 * @param bool $die
 */
function dump($array, $die = false)
{
    echo '<pre style="background: #f5f5f5; padding: 20px; border-radius: 8px; border: 1px solid #ddd; font-family: monospace; font-size: 14px; max-height: 500px; overflow: auto;">';
    print_r($array);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}

/**
 * Check if string starts with
 * 
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function strStartsWith($haystack, $needle)
{
    return strpos($haystack, $needle) === 0;
}

/**
 * Check if string ends with
 * 
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function strEndsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) === $needle;
}

/**
 * Convert to camelCase
 * 
 * @param string $string
 * @param string $separator
 * @return string
 */
function toCamelCase($string, $separator = '_')
{
    $parts = explode($separator, $string);
    $first = array_shift($parts);
    $parts = array_map('ucfirst', $parts);
    return $first . implode('', $parts);
}

/**
 * Convert to snake_case
 * 
 * @param string $string
 * @return string
 */
function toSnakeCase($string)
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
}

/**
 * Generate HTML meta tags
 * 
 * @param array $meta
 * @return string
 */
function generateMetaTags($meta = [])
{
    $defaults = [
        'title' => APP_NAME,
        'description' => APP_DESCRIPTION,
        'image' => SITE_URL . '/assets/images/logo.png',
        'url' => SITE_URL,
        'type' => 'website',
        'site_name' => APP_NAME,
        'twitter_card' => 'summary_large_image'
    ];
    
    $meta = array_merge($defaults, $meta);
    
    $html = [];
    $html[] = '<title>' . Security::escapeHtml($meta['title']) . '</title>';
    $html[] = '<meta name="description" content="' . Security::escapeHtml($meta['description']) . '">';
    $html[] = '<meta property="og:title" content="' . Security::escapeHtml($meta['title']) . '">';
    $html[] = '<meta property="og:description" content="' . Security::escapeHtml($meta['description']) . '">';
    $html[] = '<meta property="og:image" content="' . Security::escapeHtml($meta['image']) . '">';
    $html[] = '<meta property="og:url" content="' . Security::escapeHtml($meta['url']) . '">';
    $html[] = '<meta property="og:type" content="' . Security::escapeHtml($meta['type']) . '">';
    $html[] = '<meta property="og:site_name" content="' . Security::escapeHtml($meta['site_name']) . '">';
    $html[] = '<meta name="twitter:card" content="' . Security::escapeHtml($meta['twitter_card']) . '">';
    $html[] = '<meta name="twitter:title" content="' . Security::escapeHtml($meta['title']) . '">';
    $html[] = '<meta name="twitter:description" content="' . Security::escapeHtml($meta['description']) . '">';
    $html[] = '<meta name="twitter:image" content="' . Security::escapeHtml($meta['image']) . '">';
    
    if (isset($meta['canonical'])) {
        $html[] = '<link rel="canonical" href="' . Security::escapeHtml($meta['canonical']) . '">';
    }
    
    return implode("\n", $html);
}

/**
 * Generate JSON-LD structured data
 * 
 * @param array $data
 * @return string
 */
function generateStructuredData($data)
{
    if (empty($data)) {
        return '';
    }
    
    return '<script type="application/ld+json">' . 
           json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . 
           '</script>';
}

/**
 * Get theme
 * 
 * @return string
 */
function getTheme()
{
    return isset($_COOKIE['theme']) ? $_COOKIE['theme'] : DEFAULT_THEME;
}

/**
 * Check if dark mode
 * 
 * @return bool
 */
function isDarkMode()
{
    return getTheme() === 'dark';
}

/**
 * Get assets URL
 * 
 * @param string $path
 * @return string
 */
function asset($path)
{
    return ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Get current year
 * 
 * @return int
 */
function currentYear()
{
    return (int)date('Y');
}

/**
 * Check if request is AJAX
 * 
 * @return bool
 */
function isAjax()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Redirect and exit
 * 
 * @param string $url
 * @param int $status
 */
function redirect($url, $status = 302)
{
    http_response_code($status);
    header('Location: ' . $url);
    exit;
}

/**
 * Redirect back
 * 
 * @return void
 */
function redirectBack()
{
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
    redirect($referer);
}

/**
 * Set maintenance mode
 * 
 * @param bool $enabled
 * @param string $message
 * @return bool
 */
function setMaintenanceMode($enabled = true, $message = 'We are currently performing maintenance. Please check back soon.')
{
    global $db;
    
    $settings = $db->read('settings.json');
    $settings['maintenance'] = [
        'enabled' => $enabled,
        'message' => $message,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    return $db->write('settings.json', $settings);
}