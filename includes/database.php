<?php
/**
 * FocusedTube - Database Handler
 * 
 * Handles all JSON file operations with CRUD functionality
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

namespace FocusedTube;

class Database
{
    /**
     * @var string $dataPath Path to data directory
     */
    private $dataPath;
    
    /**
     * @var array $cache In-memory cache for frequently accessed data
     */
    private $cache = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dataPath = DATA_PATH;
        $this->ensureDataDirectoryExists();
    }
    
    /**
     * Ensure data directory exists with proper permissions
     */
    private function ensureDataDirectoryExists()
    {
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }
        
        // Create default JSON files if they don't exist
        $defaultFiles = [
            'videos.json' => [],
            'users.json' => [],
            'comments.json' => [],
            'likes.json' => [],
            'history.json' => [],
            'favorites.json' => [],
            'playlists.json' => [],
            'categories.json' => [],
            'settings.json' => [],
            'notifications.json' => [],
            'reports.json' => [],
            'activity.json' => [],
            'watchlater.json' => []
        ];
        
        foreach ($defaultFiles as $file => $defaultData) {
            $filePath = $this->dataPath . '/' . $file;
            if (!file_exists($filePath)) {
                $this->writeJsonFile($filePath, $defaultData);
            }
        }
    }
    
    /**
     * Read JSON file and return decoded data
     * 
     * @param string $filename
     * @return array
     */
    public function read($filename)
    {
        // Check cache first
        if (isset($this->cache[$filename])) {
            return $this->cache[$filename];
        }
        
        $filePath = $this->dataPath . '/' . $filename;
        
        if (!file_exists($filePath)) {
            return [];
        }
        
        $data = $this->readJsonFile($filePath);
        
        // Store in cache
        $this->cache[$filename] = $data;
        
        return $data;
    }
    
    /**
     * Write data to JSON file
     * 
     * @param string $filename
     * @param array $data
     * @return bool
     */
    public function write($filename, array $data)
    {
        $filePath = $this->dataPath . '/' . $filename;
        $result = $this->writeJsonFile($filePath, $data);
        
        if ($result) {
            // Update cache
            $this->cache[$filename] = $data;
            
            // Create backup
            $this->createBackup($filename);
        }
        
        return $result;
    }
    
    /**
     * Find items by criteria
     * 
     * @param string $filename
     * @param array $criteria
     * @return array
     */
    public function find($filename, array $criteria = [])
    {
        $data = $this->read($filename);
        
        if (empty($criteria)) {
            return $data;
        }
        
        $results = [];
        foreach ($data as $item) {
            $matches = true;
            foreach ($criteria as $key => $value) {
                if (!isset($item[$key]) || $item[$key] !== $value) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $results[] = $item;
            }
        }
        
        return $results;
    }
    
    /**
     * Find a single item by criteria
     * 
     * @param string $filename
     * @param array $criteria
     * @return array|null
     */
    public function findOne($filename, array $criteria)
    {
        $results = $this->find($filename, $criteria);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Find by ID
     * 
     * @param string $filename
     * @param string|int $id
     * @param string $idField
     * @return array|null
     */
    public function findById($filename, $id, $idField = 'id')
    {
        return $this->findOne($filename, [$idField => $id]);
    }
    
    /**
     * Insert a new item
     * 
     * @param string $filename
     * @param array $item
     * @return bool
     */
    public function insert($filename, array $item)
    {
        $data = $this->read($filename);
        $data[] = $item;
        return $this->write($filename, $data);
    }
    
    /**
     * Update items by criteria
     * 
     * @param string $filename
     * @param array $criteria
     * @param array $updates
     * @return bool
     */
    public function update($filename, array $criteria, array $updates)
    {
        $data = $this->read($filename);
        $updated = false;
        
        foreach ($data as $key => $item) {
            $matches = true;
            foreach ($criteria as $k => $v) {
                if (!isset($item[$k]) || $item[$k] !== $v) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $data[$key] = array_merge($item, $updates);
                $updated = true;
            }
        }
        
        if ($updated) {
            return $this->write($filename, $data);
        }
        
        return false;
    }
    
    /**
     * Update by ID
     * 
     * @param string $filename
     * @param string|int $id
     * @param array $updates
     * @param string $idField
     * @return bool
     */
    public function updateById($filename, $id, array $updates, $idField = 'id')
    {
        return $this->update($filename, [$idField => $id], $updates);
    }
    
    /**
     * Delete items by criteria
     * 
     * @param string $filename
     * @param array $criteria
     * @return bool
     */
    public function delete($filename, array $criteria)
    {
        $data = $this->read($filename);
        $deleted = false;
        
        foreach ($data as $key => $item) {
            $matches = true;
            foreach ($criteria as $k => $v) {
                if (!isset($item[$k]) || $item[$k] !== $v) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                unset($data[$key]);
                $deleted = true;
            }
        }
        
        if ($deleted) {
            $data = array_values($data); // Reindex array
            return $this->write($filename, $data);
        }
        
        return false;
    }
    
    /**
     * Delete by ID
     * 
     * @param string $filename
     * @param string|int $id
     * @param string $idField
     * @return bool
     */
    public function deleteById($filename, $id, $idField = 'id')
    {
        return $this->delete($filename, [$idField => $id]);
    }
    
    /**
     * Get paginated results
     * 
     * @param string $filename
     * @param int $page
     * @param int $perPage
     * @param array $criteria
     * @return array
     */
    public function paginate($filename, $page = 1, $perPage = 20, array $criteria = [])
    {
        $data = $this->find($filename, $criteria);
        $total = count($data);
        $totalPages = ceil($total / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $items = array_slice($data, $offset, $perPage);
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'hasPrevious' => $page > 1,
            'hasNext' => $page < $totalPages
        ];
    }
    
    /**
     * Get count of items
     * 
     * @param string $filename
     * @param array $criteria
     * @return int
     */
    public function count($filename, array $criteria = [])
    {
        $data = $this->find($filename, $criteria);
        return count($data);
    }
    
    /**
     * Get all unique values for a field
     * 
     * @param string $filename
     * @param string $field
     * @return array
     */
    public function getUniqueValues($filename, $field)
    {
        $data = $this->read($filename);
        $values = [];
        
        foreach ($data as $item) {
            if (isset($item[$field])) {
                $values[] = $item[$field];
            }
        }
        
        return array_unique($values);
    }
    
    /**
     * Search in JSON data
     * 
     * @param string $filename
     * @param string $searchTerm
     * @param array $fields
     * @return array
     */
    public function search($filename, $searchTerm, array $fields = [])
    {
        $data = $this->read($filename);
        $results = [];
        $searchTerm = strtolower($searchTerm);
        
        foreach ($data as $item) {
            foreach ($fields as $field) {
                if (isset($item[$field]) && 
                    stripos($item[$field], $searchTerm) !== false) {
                    $results[] = $item;
                    break;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Clear cache
     */
    public function clearCache()
    {
        $this->cache = [];
    }
    
    /**
     * Create a backup of a JSON file
     * 
     * @param string $filename
     * @return bool
     */
    private function createBackup($filename)
    {
        $source = $this->dataPath . '/' . $filename;
        $backupDir = BACKUPS_PATH . '/json';
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . '/' . $filename . '_' . $timestamp . '.backup';
        
        return copy($source, $backupFile);
    }
    
    /**
     * Read JSON file with error handling
     * 
     * @param string $filePath
     * @return array
     */
    private function readJsonFile($filePath)
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }
        
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }
    
    /**
     * Write JSON file with error handling
     * 
     * @param string $filePath
     * @param array $data
     * @return bool
     */
    private function writeJsonFile($filePath, array $data)
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($json === false) {
            return false;
        }
        
        // Create directory if it doesn't exist
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Write file with exclusive lock
        $result = file_put_contents($filePath, $json, LOCK_EX);
        
        // Set proper permissions
        if ($result !== false) {
            chmod($filePath, 0644);
            return true;
        }
        
        return false;
    }
    
    /**
     * Export entire database to backup
     * 
     * @return bool
     */
    public function exportAllData()
    {
        $backupDir = BACKUPS_PATH . '/full_' . date('Y-m-d_H-i-s');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $files = glob($this->dataPath . '/*.json');
        foreach ($files as $file) {
            $filename = basename($file);
            copy($file, $backupDir . '/' . $filename);
        }
        
        return true;
    }
    
    /**
     * Import data from backup
     * 
     * @param string $backupPath
     * @return bool
     */
    public function importData($backupPath)
    {
        if (!is_dir($backupPath)) {
            return false;
        }
        
        $files = glob($backupPath . '/*.json');
        $success = true;
        
        foreach ($files as $file) {
            $filename = basename($file);
            $target = $this->dataPath . '/' . $filename;
            
            if (copy($file, $target)) {
                chmod($target, 0644);
            } else {
                $success = false;
            }
        }
        
        // Clear cache
        $this->clearCache();
        
        return $success;
    }
}

// Create global database instance
$db = new Database();
?>