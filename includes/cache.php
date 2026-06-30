<?php
/**
 * FocusedTube - Cache Handler
 * 
 * Handles file-based caching for improved performance
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube;

class Cache
{
    /**
     * @var string $cachePath Cache directory path
     */
    private $cachePath;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cachePath = CACHE_PATH;
        $this->ensureCacheDirectoryExists();
    }
    
    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectoryExists()
    {
        $paths = [
            $this->cachePath,
            $this->cachePath . '/pages',
            $this->cachePath . '/api',
            $this->cachePath . '/views'
        ];
        
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
    
    /**
     * Set cache value
     * 
     * @param string $key
     * @param mixed $value
     * @param int $lifetime
     * @return bool
     */
    public function set($key, $value, $lifetime = CACHE_LIFETIME)
    {
        if (!CACHE_ENABLED) {
            return false;
        }
        
        $cacheFile = $this->getCacheFile($key);
        
        // Ensure directory exists
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $data = [
            'expires' => time() + $lifetime,
            'data' => $value
        ];
        
        return file_put_contents($cacheFile, serialize($data), LOCK_EX) !== false;
    }
    
    /**
     * Get cache value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!CACHE_ENABLED) {
            return $default;
        }
        
        $cacheFile = $this->getCacheFile($key);
        
        if (!file_exists($cacheFile)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($cacheFile));
        
        if ($data === false || $data['expires'] < time()) {
            $this->delete($key);
            return $default;
        }
        
        return $data['data'];
    }
    
    /**
     * Delete cache entry
     * 
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $cacheFile = $this->getCacheFile($key);
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
    public function clear()
    {
        $this->clearDirectory($this->cachePath);
        return true;
    }
    
    /**
     * Recursively clear directory
     * 
     * @param string $dir
     */
    private function clearDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->clearDirectory($path);
                rmdir($path);
            } else {
                unlink($path);
            }
        }
    }
    
    /**
     * Get cache file path
     * 
     * @param string $key
     * @return string
     */
    private function getCacheFile($key)
    {
        $hash = md5($key);
        $subDir = substr($hash, 0, 2);
        $dir = $this->cachePath . '/' . $subDir;
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir . '/' . $hash . '.cache';
    }
    
    /**
     * Cache entire page content
     * 
     * @param string $url
     * @param string $content
     * @param int $lifetime
     * @return bool
     */
    public function cachePage($url, $content, $lifetime = CACHE_LIFETIME)
    {
        $key = 'page_' . md5($url);
        return $this->set($key, $content, $lifetime);
    }
    
    /**
     * Get cached page
     * 
     * @param string $url
     * @return string|null
     */
    public function getPage($url)
    {
        $key = 'page_' . md5($url);
        return $this->get($key);
    }
    
    /**
     * Cache API response
     * 
     * @param string $endpoint
     * @param array $data
     * @param int $lifetime
     * @return bool
     */
    public function cacheApi($endpoint, $data, $lifetime = 300)
    {
        $key = 'api_' . md5($endpoint);
        return $this->set($key, $data, $lifetime);
    }
    
    /**
     * Get cached API response
     * 
     * @param string $endpoint
     * @return array|null
     */
    public function getApi($endpoint)
    {
        $key = 'api_' . md5($endpoint);
        return $this->get($key);
    }
    
    /**
     * Cache view
     * 
     * @param string $view
     * @param array $data
     * @param int $lifetime
     * @return bool
     */
    public function cacheView($view, $data, $lifetime = CACHE_LIFETIME)
    {
        $key = 'view_' . md5($view . serialize($data));
        return $this->set($key, $data, $lifetime);
    }
    
    /**
     * Get cached view
     * 
     * @param string $view
     * @param array $data
     * @return array|null
     */
    public function getView($view, $data = [])
    {
        $key = 'view_' . md5($view . serialize($data));
        return $this->get($key);
    }
    
    /**
     * Get cache stats
     * 
     * @return array
     */
    public function getStats()
    {
        $files = glob($this->cachePath . '/*/*.cache');
        $totalSize = 0;
        $totalFiles = count($files);
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size' => $this->formatBytes($totalSize),
            'cache_path' => $this->cachePath
        ];
    }
    
    /**
     * Format bytes to human readable
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}