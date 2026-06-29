<?php
/**
 * FocusedTube - Base Controller
 * 
 * Base controller class for all controllers
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube\Controllers;

use FocusedTube\Template;
use FocusedTube\Security;

class Controller
{
    /**
     * @var \FocusedTube\Database $db
     */
    protected $db;
    
    /**
     * @var \FocusedTube\Auth $auth
     */
    protected $auth;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        global $db, $auth;
        $this->db = $db;
        $this->auth = $auth;
    }
    
    /**
     * Render view
     * 
     * @param string $view
     * @param array $data
     */
    protected function render($view, $data = [])
    {
        Template::render($view, $data);
    }
    
    /**
     * Return JSON response
     * 
     * @param mixed $data
     * @param int $statusCode
     */
    protected function json($data, $statusCode = 200)
    {
        Template::json($data, $statusCode);
    }
    
    /**
     * Redirect to URL
     * 
     * @param string $url
     */
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Get current user
     * 
     * @return array|null
     */
    protected function getUser()
    {
        return $this->auth->getUser();
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    protected function isLoggedIn()
    {
        return $this->auth->isLoggedIn();
    }
    
    /**
     * Check if user has role
     * 
     * @param string $role
     * @return bool
     */
    protected function hasRole($role)
    {
        return $this->auth->hasRole($role);
    }
}