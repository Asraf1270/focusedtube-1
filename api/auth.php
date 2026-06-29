<?php
/**
 * FocusedTube - Auth Controller
 * 
 * Handles authentication API operations
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Api;

use FocusedTube\Security;
use FocusedTube\Auth;

class AuthController
{
    private $auth;
    private $apiAuth;
    
    public function __construct()
    {
        $this->auth = new Auth();
        $this->apiAuth = new ApiAuth();
    }
    
    /**
     * Login user
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function login($input, $params)
    {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            return ['status' => 400, 'data' => ['error' => 'Email and password required']];
        }
        
        $result = $this->auth->login($email, $password);
        
        if ($result === true) {
            $user = $this->auth->getUser();
            return [
                'status' => 200,
                'data' => [
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'settings' => $user['settings'] ?? []
                    ]
                ]
            ];
        }
        
        return ['status' => 401, 'data' => ['error' => $result]];
    }
    
    /**
     * Register user
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function register($input, $params)
    {
        $email = $input['email'] ?? '';
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($username) || empty($password)) {
            return ['status' => 400, 'data' => ['error' => 'All fields are required']];
        }
        
        $result = $this->auth->createUser([
            'email' => $email,
            'username' => $username,
            'password' => $password
        ]);
        
        if ($result === true) {
            // Auto-login after registration
            $loginResult = $this->auth->login($email, $password);
            
            if ($loginResult === true) {
                $user = $this->auth->getUser();
                return [
                    'status' => 201,
                    'data' => [
                        'message' => 'Registration successful',
                        'user' => [
                            'id' => $user['id'],
                            'email' => $user['email'],
                            'username' => $user['username'],
                            'role' => $user['role']
                        ]
                    ]
                ];
            }
            
            return ['status' => 200, 'data' => ['message' => 'Registration successful. Please login.']];
        }
        
        return ['status' => 400, 'data' => ['error' => $result]];
    }
    
    /**
     * Logout user
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function logout($input, $params)
    {
        $this->auth->logout();
        return ['status' => 200, 'data' => ['message' => 'Logged out successfully']];
    }
    
    /**
     * Get current user
     * 
     * @param array $input
     * @param array $params
     * @return array
     */
    public function me($input, $params)
    {
        $user = $this->apiAuth->requireAuth();
        
        return [
            'status' => 200,
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'avatar' => $user['avatar'] ?? null,
                    'bio' => $user['bio'] ?? '',
                    'settings' => $user['settings'] ?? []
                ]
            ]
        ];
    }
}