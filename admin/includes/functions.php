<?php
/**
 * FocusedTube - Admin Functions
 * 
 * Helper functions for the admin panel
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;
use FocusedTube\Template;

class AdminFunctions
{
    private $db;
    private $cache;
    
    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->cache = new \FocusedTube\Cache();
    }
    
    /**
     * Get dashboard statistics
     * 
     * @return array
     */
    public function getDashboardStats()
    {
        // Try cache first
        $cacheKey = 'admin_dashboard_stats';
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }
        
        $videos = $this->db->read('videos.json');
        $users = $this->db->read('users.json');
        $comments = $this->db->read('comments.json');
        $history = $this->db->read('history.json');
        
        // Calculate totals
        $totalVideos = count($videos);
        $totalUsers = count($users);
        $totalComments = count($comments);
        $totalViews = 0;
        
        foreach ($history as $entry) {
            $totalViews += $entry['count'] ?? 1;
        }
        
        // Calculate changes (compare with last month)
        $lastMonth = strtotime('-1 month');
        $videosLastMonth = 0;
        $usersLastMonth = 0;
        $commentsLastMonth = 0;
        $viewsLastMonth = 0;
        
        foreach ($videos as $video) {
            if (isset($video['created_at']) && strtotime($video['created_at']) > $lastMonth) {
                $videosLastMonth++;
            }
        }
        
        foreach ($users as $user) {
            if (isset($user['created_at']) && strtotime($user['created_at']) > $lastMonth) {
                $usersLastMonth++;
            }
        }
        
        foreach ($comments as $comment) {
            if (isset($comment['created_at']) && strtotime($comment['created_at']) > $lastMonth) {
                $commentsLastMonth++;
            }
        }
        
        foreach ($history as $entry) {
            if (isset($entry['timestamp']) && strtotime($entry['timestamp']) > $lastMonth) {
                $viewsLastMonth += $entry['count'] ?? 1;
            }
        }
        
        // Get views data for chart
        $viewsData = $this->getViewsData(30);
        
        $stats = [
            'total_videos' => $totalVideos,
            'total_users' => $totalUsers,
            'total_comments' => $totalComments,
            'total_views' => $totalViews,
            'videos_change' => $totalVideos > 0 ? round(($videosLastMonth / $totalVideos) * 100) : 0,
            'users_change' => $totalUsers > 0 ? round(($usersLastMonth / $totalUsers) * 100) : 0,
            'comments_change' => $totalComments > 0 ? round(($commentsLastMonth / $totalComments) * 100) : 0,
            'views_change' => $totalViews > 0 ? round(($viewsLastMonth / $totalViews) * 100) : 0,
            'views_data' => $viewsData
        ];
        
        // Cache for 5 minutes
        $this->cache->set($cacheKey, $stats, 300);
        
        return $stats;
    }
    
    /**
     * Get views data for chart
     * 
     * @param int $days
     * @return array
     */
    private function getViewsData($days = 30)
    {
        $history = $this->db->read('history.json');
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[$date] = 0;
        }
        
        foreach ($history as $entry) {
            if (isset($entry['timestamp'])) {
                $date = date('Y-m-d', strtotime($entry['timestamp']));
                if (isset($data[$date])) {
                    $data[$date] += $entry['count'] ?? 1;
                }
            }
        }
        
        $result = [];
        foreach ($data as $date => $count) {
            $result[] = ['date' => $date, 'views' => $count];
        }
        
        return $result;
    }
    
    /**
     * Get recent activity
     * 
     * @param int $limit
     * @return array
     */
    public function getRecentActivity($limit = 10)
    {
        $activities = $this->db->read('activity.json');
        
        // Sort by timestamp descending
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Get user emails for display
        $users = $this->db->read('users.json');
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user['id']] = $user['email'];
        }
        
        $result = array_slice($activities, 0, $limit);
        
        // Add user email
        foreach ($result as &$activity) {
            if (isset($activity['user_id']) && isset($userMap[$activity['user_id']])) {
                $activity['user_email'] = $userMap[$activity['user_id']];
            }
        }
        
        return $result;
    }
    
    /**
     * Get recent videos
     * 
     * @param int $limit
     * @return array
     */
    public function getRecentVideos($limit = 5)
    {
        $videos = $this->db->read('videos.json');
        
        // Sort by created_at descending
        usort($videos, function($a, $b) {
            $timeA = strtotime($a['created_at'] ?? $a['published_at'] ?? 'now');
            $timeB = strtotime($b['created_at'] ?? $b['published_at'] ?? 'now');
            return $timeB - $timeA;
        });
        
        return array_slice($videos, 0, $limit);
    }
    
    /**
     * Get activity icon based on action
     * 
     * @param string $action
     * @return string
     */
    public function getActivityIcon($action)
    {
        $icons = [
            'Login' => '🔐',
            'Logout' => '🚪',
            'Registration' => '👤',
            'Video Import' => '📥',
            'Video Delete' => '🗑️',
            'Video Update' => '✏️',
            'Comment' => '💬',
            'Like' => '❤️',
            'Favorite' => '⭐',
            'Playlist' => '📋',
            'Profile Update' => '👤',
            'Deletion' => '🗑️',
            'Backup' => '💾',
            'Restore' => '📂',
            'Settings' => '⚙️',
            'Maintenance' => '🔧'
        ];
        
        return $icons[$action] ?? '📌';
    }
    
    /**
     * Format duration
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
        }
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
    
    /**
     * Get time ago string
     * 
     * @param string $timestamp
     * @return string
     */
    public function timeAgo($timestamp)
    {
        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ago';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . 'd ago';
        }
        if ($diff < 2592000) {
            return floor($diff / 604800) . 'w ago';
        }
        if ($diff < 31536000) {
            return floor($diff / 2592000) . 'mo ago';
        }
        return floor($diff / 31536000) . 'y ago';
    }
    
    /**
     * Validate admin action
     * 
     * @param string $action
     * @return bool
     */
    public function validateAction($action)
    {
        // Check CSRF token
        if (!isset($_POST['csrf_token']) || !check_csrf($_POST['csrf_token'])) {
            return false;
        }
        
        // Check permission
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log admin action
     * 
     * @param string $action
     * @param string $description
     */
    public function logAction($action, $description)
    {
        $log = [
            'id' => 'act_' . uniqid(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'action' => $action,
            'description' => $description,
            'ip' => Security::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('activity.json', $log);
    }
}
?>