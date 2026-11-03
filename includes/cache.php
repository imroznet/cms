<?php
class Cache {
    private $cachePath;
    private $cacheDuration = 3600; // 1 hour by default
    
    public function __construct($path = 'cache') {
        $this->cachePath = dirname(__DIR__) . '/' . $path;
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    public function getCache($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }
        
        $data = file_get_contents($filename);
        $cacheData = unserialize($data);
        
        // Check if cache has expired
        if (time() > $cacheData['expires']) {
            unlink($filename); // Delete expired cache
            return false;
        }
        
        return $cacheData['content'];
    }
    
    public function setCache($key, $content, $duration = null) {
        $duration = $duration ?? $this->cacheDuration;
        $cacheData = [
            'expires' => time() + $duration,
            'content' => $content
        ];
        
        $filename = $this->getCacheFilename($key);
        file_put_contents($filename, serialize($cacheData));
    }
    
    public function clearCache($key = null) {
        if ($key) {
            $filename = $this->getCacheFilename($key);
            if (file_exists($filename)) {
                unlink($filename);
            }
        } else {
            // Clear all cache files
            $files = glob($this->cachePath . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
    
    private function getCacheFilename($key) {
        return $this->cachePath . '/' . md5($key) . '.cache';
    }
}