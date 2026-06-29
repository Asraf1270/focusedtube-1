<?php
/**
 * FocusedTube - API Authentication Middleware
 * 
 * Handles API authentication and authorization
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Api;

use FocusedTube\Auth;
use FocusedTube\Template;

class ApiAuth
{
    /**
     * @var Auth $auth Auth instance
     */
    private $auth;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->auth = new Auth();
    }
    
    /**
     * Require authentication
     * 
     * @return array
     */
    public function requireAuth()
    {
        if (!$this->auth->isLoggedIn()) {
            Template::json(['error' => 'Authentication required'], 401);
            exit;
        }
        
        return $this->auth->getUser();
    }
    
    /**
     * Require specific role
     * 
     * @param string $role
     * @return array
     */
    public function requireRole($role)
    {
        $user = $this->requireAuth();
        
        if ($user['role'] !== $role && $user['role'] !== 'admin') {
            Template::json(['error' => 'Insufficient permissions'], 403);
            exit;
        }
        
        return $user;
    }
    
    /**
     * Require admin role
     * 
     * @return array
     */
    public function requireAdmin()
    {
        return $this->requireRole('admin');
    }
    
    /**
     * Get current user
     * 
     * @return array|null
     */
    public function getCurrentUser()
    {
        if ($this->auth->isLoggedIn()) {
            return $this->auth->getUser();
        }
        return null;
    }
    
    /**
     * Generate API token
     * 
     * @param string $userId
     * @return string
     */
    public function generateToken($userId)
    {
        $token = bin2hex(random_bytes(32));
        $expires = time() + 86400; // 24 hours
        
        // Store token in database
        global $db;
        $tokens = $db->read('tokens.json');
        $tokens[] = [
            'id' => 'tok_' . uniqid(),
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'expires' => $expires,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $db->write('tokens.json', $tokens);
        
        return $token;
    }
    
    /**
     * Validate API token
     * 
     * @param string $token
     * @return array|null
     */
    public function validateToken($token)
    {
        global $db;
        $tokens = $db->read('tokens.json');
        $hashedToken = hash('sha256', $token);
        
        foreach ($tokens as $storedToken) {
            if ($storedToken['token'] === $hashedToken && $storedToken['expires'] > time()) {
                return $this->auth->getUserById($storedToken['user_id']);
            }
        }
        
        return null;
    }
}