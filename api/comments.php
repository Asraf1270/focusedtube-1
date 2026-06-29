<?php
/**
 * FocusedTube - Comment Controller
 * 
 * Handles comment API operations
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Api;

use FocusedTube\Security;
use FocusedTube\ApiAuth;

class CommentController
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
     * Get comments for a video
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function index($input, $params)
    {
        $videoId = $params['videoId'] ?? '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 20;
        
        if (empty($videoId)) {
            return ['status' => 400, 'data' => ['error' => 'Video ID required']];
        }
        
        $allComments = $this->db->read('comments.json');
        $comments = array_filter($allComments, function($comment) use ($videoId) {
            return $comment['video_id'] === $videoId;
        });
        
        // Sort by newest first
        usort($comments, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Paginate
        $total = count($comments);
        $totalPages = ceil($total / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $items = array_slice($comments, $offset, $perPage);
        
        // Get user info for each comment
        $users = $this->db->read('users.json');
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user['id']] = $user['username'] ?? $user['email'];
        }
        
        foreach ($items as &$comment) {
            $comment['author_name'] = $userMap[$comment['user_id']] ?? 'Anonymous';
        }
        
        return [
            'status' => 200,
            'data' => [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages
            ]
        ];
    }
    
    /**
     * Store new comment
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function store($input, $params)
    {
        $user = $this->apiAuth->requireAuth();
        
        $videoId = $input['video_id'] ?? '';
        $text = $input['text'] ?? '';
        
        if (empty($videoId) || empty($text)) {
            return ['status' => 400, 'data' => ['error' => 'Video ID and text required']];
        }
        
        // Check if video exists
        $video = $this->db->findById('videos.json', $videoId);
        if (!$video || ($video['status'] ?? 'published') !== 'published') {
            return ['status' => 404, 'data' => ['error' => 'Video not found']];
        }
        
        $comment = [
            'id' => 'cmt_' . uniqid(),
            'video_id' => $videoId,
            'user_id' => $user['id'],
            'text' => Security::sanitize($text),
            'likes' => 0,
            'replies' => [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'status' => 'published'
        ];
        
        if ($this->db->insert('comments.json', $comment)) {
            // Update video comment count
            $video['comment_count'] = ($video['comment_count'] ?? 0) + 1;
            $this->db->updateById('videos.json', $videoId, ['comment_count' => $video['comment_count']]);
            
            // Log activity
            $this->logActivity('Comment', "User commented on video: {$video['title']}");
            
            return [
                'status' => 201,
                'data' => [
                    'message' => 'Comment added successfully',
                    'comment' => $comment
                ]
            ];
        }
        
        return ['status' => 500, 'data' => ['error' => 'Failed to add comment']];
    }
    
    /**
     * Delete comment
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function delete($input, $params)
    {
        $user = $this->apiAuth->requireAuth();
        $commentId = $params['id'] ?? '';
        
        if (empty($commentId)) {
            return ['status' => 400, 'data' => ['error' => 'Comment ID required']];
        }
        
        $comment = $this->db->findById('comments.json', $commentId);
        
        if (!$comment) {
            return ['status' => 404, 'data' => ['error' => 'Comment not found']];
        }
        
        // Check if user owns the comment or is admin
        if ($comment['user_id'] !== $user['id'] && $user['role'] !== 'admin') {
            return ['status' => 403, 'data' => ['error' => 'Permission denied']];
        }
        
        if ($this->db->deleteById('comments.json', $commentId)) {
            // Update video comment count
            $video = $this->db->findById('videos.json', $comment['video_id']);
            if ($video) {
                $video['comment_count'] = max(0, ($video['comment_count'] ?? 0) - 1);
                $this->db->updateById('videos.json', $comment['video_id'], ['comment_count' => $video['comment_count']]);
            }
            
            $this->logActivity('Comment Delete', "User deleted comment: {$commentId}");
            return ['status' => 200, 'data' => ['message' => 'Comment deleted successfully']];
        }
        
        return ['status' => 500, 'data' => ['error' => 'Failed to delete comment']];
    }
    
    /**
     * Update comment
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function update($input, $params)
    {
        $user = $this->apiAuth->requireAuth();
        $commentId = $params['id'] ?? '';
        
        if (empty($commentId)) {
            return ['status' => 400, 'data' => ['error' => 'Comment ID required']];
        }
        
        $comment = $this->db->findById('comments.json', $commentId);
        
        if (!$comment) {
            return ['status' => 404, 'data' => ['error' => 'Comment not found']];
        }
        
        // Check if user owns the comment
        if ($comment['user_id'] !== $user['id']) {
            return ['status' => 403, 'data' => ['error' => 'Permission denied']];
        }
        
        $text = $input['text'] ?? '';
        if (empty($text)) {
            return ['status' => 400, 'data' => ['error' => 'Text required']];
        }
        
        $updates = [
            'text' => Security::sanitize($text),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($this->db->updateById('comments.json', $commentId, $updates)) {
            return ['status' => 200, 'data' => ['message' => 'Comment updated successfully']];
        }
        
        return ['status' => 500, 'data' => ['error' => 'Failed to update comment']];
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