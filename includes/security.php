<?php
/**
 * FocusedTube - Security Handler
 * 
 * Handles all security operations including validation, sanitization, CSRF, XSS protection
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube;

class Security
{
    /**
     * @var array $input Sanitized input data
     */
    private static $input = [];
    
    /**
     * Sanitize user input
     * 
     * @param mixed $input
     * @param string $type
     * @return mixed
     */
    public static function sanitize($input, $type = 'string')
    {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitize($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'string':
                return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT);
            case 'boolean':
                return filter_var($input, FILTER_VALIDATE_BOOLEAN);
            case 'youtube_id':
                return preg_replace('/[^a-zA-Z0-9_-]/', '', trim($input));
            default:
                return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate input against rules
     * 
     * @param mixed $input
     * @param array $rules
     * @return bool|string
     */
    public static function validate($input, array $rules)
    {
        foreach ($rules as $field => $ruleSet) {
            $value = isset($input[$field]) ? $input[$field] : null;
            
            // Check required
            if (in_array('required', $ruleSet) && empty($value)) {
                return "Field '{$field}' is required";
            }
            
            // Skip validation if field is empty and not required
            if (empty($value) && !in_array('required', $ruleSet)) {
                continue;
            }
            
            foreach ($ruleSet as $rule) {
                switch ($rule) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            return "Field '{$field}' must be a valid email address";
                        }
                        break;
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            return "Field '{$field}' must be a valid URL";
                        }
                        break;
                    case 'youtube_id':
                        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $value)) {
                            return "Field '{$field}' must be a valid YouTube ID";
                        }
                        break;
                    case 'integer':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            return "Field '{$field}' must be an integer";
                        }
                        break;
                    case 'boolean':
                        if (!is_bool($value) && !in_array($value, ['true', 'false', '1', '0', 1, 0])) {
                            return "Field '{$field}' must be a boolean";
                        }
                        break;
                    default:
                        if (strpos($rule, 'min:') === 0) {
                            $min = intval(substr($rule, 4));
                            if (strlen($value) < $min) {
                                return "Field '{$field}' must be at least {$min} characters";
                            }
                        } elseif (strpos($rule, 'max:') === 0) {
                            $max = intval(substr($rule, 4));
                            if (strlen($value) > $max) {
                                return "Field '{$field}' must be at most {$max} characters";
                            }
                        } elseif (strpos($rule, 'in:') === 0) {
                            $allowed = explode(',', substr($rule, 3));
                            if (!in_array($value, $allowed)) {
                                return "Field '{$field}' must be one of: " . implode(', ', $allowed);
                            }
                        }
                        break;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public static function generateCsrfToken()
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME] = $token;
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
        return $token;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token
     * @return bool
     */
    public static function validateCsrfToken($token)
    {
        if (!isset($_SESSION[CSRF_TOKEN_NAME]) || 
            !isset($_SESSION[CSRF_TOKEN_NAME . '_time'])) {
            return false;
        }
        
        // Check token lifetime
        if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_LIFETIME) {
            return false;
        }
        
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Escape output for HTML
     * 
     * @param string $output
     * @return string
     */
    public static function escapeHtml($output)
    {
        return htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Escape output for JavaScript
     * 
     * @param string $output
     * @return string
     */
    public static function escapeJs($output)
    {
        return json_encode($output, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    
    /**
     * Escape output for URL
     * 
     * @param string $output
     * @return string
     */
    public static function escapeUrl($output)
    {
        return urlencode($output);
    }
    
    /**
     * Hash password
     * 
     * @param string $password
     * @return string
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehash
     * 
     * @param string $hash
     * @return bool
     */
    public static function needsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Generate secure random token
     * 
     * @param int $length
     * @return string
     */
    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Rate limiting
     * 
     * @param string $key
     * @param int $limit
     * @param int $timeWindow
     * @return bool
     */
    public static function rateLimit($key, $limit = RATE_LIMIT_REQUESTS, $timeWindow = RATE_LIMIT_TIME_WINDOW)
    {
        $rateKey = 'rate_limit_' . md5($key);
        $requests = isset($_SESSION[$rateKey]) ? $_SESSION[$rateKey] : [];
        
        // Remove old requests
        $requests = array_filter($requests, function($timestamp) use ($timeWindow) {
            return time() - $timestamp < $timeWindow;
        });
        
        if (count($requests) >= $limit) {
            return false;
        }
        
        $requests[] = time();
        $_SESSION[$rateKey] = $requests;
        
        return true;
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file
     * @param array $allowedTypes
     * @param int $maxSize
     * @return bool|string
     */
    public static function validateFile($file, $allowedTypes = ALLOWED_EXTENSIONS, $maxSize = MAX_UPLOAD_SIZE)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'File upload error: ' . $file['error'];
        }
        
        if ($file['size'] > $maxSize) {
            return 'File size exceeds maximum allowed size';
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            return 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes);
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp'
        ];
        
        if (isset($allowedMimeTypes[$extension]) && 
            $mimeType !== $allowedMimeTypes[$extension]) {
            return 'File type does not match extension';
        }
        
        return true;
    }
    
    /**
     * Sanitize filename
     * 
     * @param string $filename
     * @return string
     */
    public static function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $filename);
        $filename = preg_replace('/\s+/', '-', $filename);
        return strtolower($filename);
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    public static function getClientIp()
    {
        $ipAddress = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate IP address
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            $ipAddress = '0.0.0.0';
        }
        
        return $ipAddress;
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Set secure headers
     */
    public static function setSecurityHeaders()
    {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.youtube.com https://www.google.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https://i.ytimg.com https://*.googleusercontent.com; frame-src https://www.youtube.com; connect-src 'self' https://www.googleapis.com; font-src 'self';");
        
        // XSS Protection
        header("X-XSS-Protection: 1; mode=block");
        
        // Content Type Options
        header("X-Content-Type-Options: nosniff");
        
        // Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Feature Policy
        header("Permissions-Policy: accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()");
        
        // HSTS (only in production)
        if (ENVIRONMENT === 'production') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
    }
}
?>