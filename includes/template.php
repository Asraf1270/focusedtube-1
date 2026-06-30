<?php
/**
 * FocusedTube - Template Handler
 * 
 * Manages template rendering and layout system
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube;

class Template
{
    /**
     * @var array $data Template data
     */
    private static $data = [];
    
    /**
     * @var string $layout Current layout
     */
    private static $layout = 'main';
    
    /**
     * @var bool $isAdmin Admin mode flag
     */
    private static $isAdmin = false;
    
    /**
     * Set template data
     * 
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        self::$data[$key] = $value;
    }
    
    /**
     * Get template data
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return isset(self::$data[$key]) ? self::$data[$key] : $default;
    }
    
    /**
     * Set layout
     * 
     * @param string $layout
     */
    public static function setLayout($layout)
    {
        self::$layout = $layout;
    }
    
    /**
     * Set admin mode
     * 
     * @param bool $isAdmin
     */
    public static function setAdmin($isAdmin = true)
    {
        self::$isAdmin = $isAdmin;
    }
    
    /**
     * Render template
     * 
     * @param string $template
     * @param array $data
     */
    public static function render($template, $data = [])
    {
        // Merge data
        self::$data = array_merge(self::$data, $data);
        
        // Extract data for use in template
        extract(self::$data);
        
        // Get base path
        $basePath = self::$isAdmin ? ADMIN_PATH : PAGES_PATH;
        
        // Include header
        $headerPath = self::$isAdmin ? ADMIN_PATH . '/includes/header.php' : INCLUDES_PATH . '/header.php';
        if (file_exists($headerPath)) {
            include $headerPath;
        }
        
        // Include template
        $templatePath = $basePath . '/' . $template . '.php';
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            self::showError('Template not found: ' . $template);
        }
        
        // Include footer
        $footerPath = self::$isAdmin ? ADMIN_PATH . '/includes/footer.php' : INCLUDES_PATH . '/footer.php';
        if (file_exists($footerPath)) {
            include $footerPath;
        }
    }
    
    /**
     * Render component
     * 
     * @param string $component
     * @param array $data
     */
    public static function component($component, $data = [])
    {
        extract(array_merge(self::$data, $data));
        
        $componentPath = INCLUDES_PATH . '/components/' . $component . '.php';
        if (file_exists($componentPath)) {
            include $componentPath;
        }
    }
    
    /**
     * Render JSON response
     * 
     * @param mixed $data
     * @param int $statusCode
     */
    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Show error page
     * 
     * @param string $message
     * @param int $code
     */
    public static function showError($message, $code = 500)
    {
        http_response_code($code);
        
        if (Security::isAjax()) {
            self::json(['error' => true, 'message' => $message], $code);
        }
        
        // Check if custom error file exists
        $errorFile = ERRORS_PATH . '/' . $code . '.php';
        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            // Fallback error display
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Error <?php echo $code; ?></title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                        background: #f8fafc;
                        padding: 20px;
                    }
                    .error-container {
                        text-align: center;
                        max-width: 500px;
                    }
                    .error-icon {
                        font-size: 80px;
                        margin-bottom: 20px;
                    }
                    h1 {
                        font-size: 32px;
                        color: #0f172a;
                        margin-bottom: 10px;
                    }
                    p {
                        color: #64748b;
                        line-height: 1.6;
                        margin-bottom: 20px;
                    }
                    .btn {
                        display: inline-block;
                        padding: 12px 24px;
                        background: #ff3d00;
                        color: white;
                        text-decoration: none;
                        border-radius: 8px;
                        transition: background 0.3s;
                    }
                    .btn:hover {
                        background: #e03500;
                    }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <div class="error-icon">⚠️</div>
                    <h1>Error <?php echo $code; ?></h1>
                    <p><?php echo Security::escapeHtml($message); ?></p>
                    <a href="/" class="btn">Return to Home</a>
                </div>
            </body>
            </html>
            <?php
        }
        exit;
    }
    
    /**
     * Show 404 error
     * 
     * @param string $message
     */
    public static function show404($message = 'Page not found')
    {
        self::showError($message, 404);
    }
    
    /**
     * Show 403 error
     * 
     * @param string $message
     */
    public static function show403($message = 'Access denied')
    {
        self::showError($message, 403);
    }
    
    /**
     * Flash message
     * 
     * @param string $type
     * @param string $message
     */
    public static function flash($type, $message)
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
    public static function getFlash($type, $clear = true)
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
     */
    public static function displayFlashes()
    {
        $types = ['success', 'error', 'warning', 'info'];
        foreach ($types as $type) {
            $message = self::getFlash($type);
            if ($message) {
                echo '<div class="flash flash-' . $type . '">' . Security::escapeHtml($message) . '</div>';
            }
        }
    }
    
    /**
     * Pagination HTML
     * 
     * @param array $pagination
     * @param string $baseUrl
     * @return string
     */
    public static function pagination($pagination, $baseUrl = '')
    {
        if ($pagination['totalPages'] <= 1) {
            return '';
        }
        
        $html = '<ul class="pagination">';
        
        // Previous
        if ($pagination['hasPrevious']) {
            $html .= '<li class="page-item"><a href="' . $baseUrl . '?page=' . ($pagination['page'] - 1) . '" class="page-link">&laquo; Previous</a></li>';
        }
        
        // Page numbers
        $startPage = max(1, $pagination['page'] - 2);
        $endPage = min($pagination['totalPages'], $pagination['page'] + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i === $pagination['page']) {
                $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a href="' . $baseUrl . '?page=' . $i . '" class="page-link">' . $i . '</a></li>';
            }
        }
        
        // Next
        if ($pagination['hasNext']) {
            $html .= '<li class="page-item"><a href="' . $baseUrl . '?page=' . ($pagination['page'] + 1) . '" class="page-link">Next &raquo;</a></li>';
        }
        
        $html .= '</ul>';
        
        return $html;
    }
}