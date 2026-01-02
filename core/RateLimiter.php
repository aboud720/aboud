<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Rate Limiter
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Token bucket rate limiting algorithm
 * @security     
 *   - Prevents brute force attacks
 *   - Prevents DoS attacks
 *   - Per-IP and per-user limiting
 *   - Configurable limits per action
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Core;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class RateLimiter
{
    /**
     * Configuration
     */
    private array $config;
    
    /**
     * Current rate limit key
     */
    private string $key = '';
    
    /**
     * Retry after seconds
     */
    private int $retryAfter = 0;
    
    /**
     * In-memory cache for current request
     */
    private static array $cache = [];
    
    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Check if request is allowed
     */
    public function check(string $action = 'global', ?int $maxAttempts = null, ?int $decaySeconds = null): bool
    {
        if (!($this->config['enabled'] ?? true)) {
            return true;
        }
        
        $maxAttempts = $maxAttempts ?? $this->config['max_requests'] ?? 60;
        $decaySeconds = $decaySeconds ?? $this->config['decay_time'] ?? 60;
        
        $this->key = $this->resolveKey($action);
        
        // Get current hits
        $record = $this->getRecord($this->key);
        
        if ($record === null) {
            // First request
            $this->createRecord($this->key, $decaySeconds);
            return true;
        }
        
        // Check if limit exceeded
        if ($record['hits'] >= $maxAttempts) {
            $this->retryAfter = max(0, $record['expires_at'] - time());
            return false;
        }
        
        // Increment counter
        $this->incrementRecord($this->key);
        return true;
    }
    
    /**
     * Check login rate limit (stricter)
     */
    public function checkLogin(): bool
    {
        return $this->check(
            'login',
            $this->config['login_max'] ?? 5,
            $this->config['login_decay'] ?? 900
        );
    }
    
    /**
     * Check API rate limit
     */
    public function checkApi(?int $userId = null): bool
    {
        $action = $userId ? "api_user_{$userId}" : 'api';
        return $this->check($action, 100, 60);
    }
    
    /**
     * Get retry after seconds
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
    
    /**
     * Get remaining attempts
     */
    public function remaining(string $action = 'global', ?int $maxAttempts = null): int
    {
        $maxAttempts = $maxAttempts ?? $this->config['max_requests'] ?? 60;
        $key = $this->resolveKey($action);
        $record = $this->getRecord($key);
        
        if ($record === null) {
            return $maxAttempts;
        }
        
        return max(0, $maxAttempts - $record['hits']);
    }
    
    /**
     * Clear rate limit for a key
     */
    public function clear(string $action = 'global'): void
    {
        $key = $this->resolveKey($action);
        $this->deleteRecord($key);
    }
    
    /**
     * Resolve rate limit key (IP + action)
     */
    private function resolveKey(string $action): string
    {
        $ip = Application::getInstance()->security()->getClientIp();
        return "rate_limit:{$ip}:{$action}";
    }
    
    /**
     * Get rate limit record from storage
     */
    private function getRecord(string $key): ?array
    {
        // Check memory cache first
        if (isset(self::$cache[$key])) {
            if (self::$cache[$key]['expires_at'] > time()) {
                return self::$cache[$key];
            }
            unset(self::$cache[$key]);
        }
        
        // Try database
        try {
            $db = Application::getInstance()->db();
            $record = $db->selectOne(
                "SELECT hits, UNIX_TIMESTAMP(expires_at) as expires_at 
                 FROM rate_limits 
                 WHERE `key` = ? AND expires_at > NOW()",
                [$key]
            );
            
            if ($record) {
                self::$cache[$key] = $record;
                return $record;
            }
        } catch (\Exception $e) {
            // Database not available, use file-based fallback
            return $this->getRecordFromFile($key);
        }
        
        return null;
    }
    
    /**
     * Create new rate limit record
     */
    private function createRecord(string $key, int $decaySeconds): void
    {
        $expiresAt = time() + $decaySeconds;
        
        self::$cache[$key] = [
            'hits' => 1,
            'expires_at' => $expiresAt
        ];
        
        try {
            $db = Application::getInstance()->db();
            $db->query(
                "INSERT INTO rate_limits (`key`, hits, expires_at) 
                 VALUES (?, 1, FROM_UNIXTIME(?))
                 ON DUPLICATE KEY UPDATE 
                 hits = IF(expires_at > NOW(), hits + 1, 1),
                 expires_at = IF(expires_at > NOW(), expires_at, FROM_UNIXTIME(?))",
                [$key, $expiresAt, $expiresAt]
            );
        } catch (\Exception $e) {
            // Use file-based fallback
            $this->saveRecordToFile($key, 1, $expiresAt);
        }
    }
    
    /**
     * Increment rate limit counter
     */
    private function incrementRecord(string $key): void
    {
        if (isset(self::$cache[$key])) {
            self::$cache[$key]['hits']++;
        }
        
        try {
            $db = Application::getInstance()->db();
            $db->query(
                "UPDATE rate_limits SET hits = hits + 1 WHERE `key` = ?",
                [$key]
            );
        } catch (\Exception $e) {
            // Use file-based fallback
            $record = $this->getRecordFromFile($key);
            if ($record) {
                $this->saveRecordToFile($key, $record['hits'] + 1, $record['expires_at']);
            }
        }
    }
    
    /**
     * Delete rate limit record
     */
    private function deleteRecord(string $key): void
    {
        unset(self::$cache[$key]);
        
        try {
            $db = Application::getInstance()->db();
            $db->delete('rate_limits', ['key' => $key]);
        } catch (\Exception $e) {
            $this->deleteRecordFile($key);
        }
    }
    
    /**
     * File-based fallback - get record
     */
    private function getRecordFromFile(string $key): ?array
    {
        $file = $this->getRecordFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        if (!$data || $data['expires_at'] <= time()) {
            @unlink($file);
            return null;
        }
        
        return $data;
    }
    
    /**
     * File-based fallback - save record
     */
    private function saveRecordToFile(string $key, int $hits, int $expiresAt): void
    {
        $file = $this->getRecordFilePath($key);
        $dir = dirname($file);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        
        file_put_contents($file, json_encode([
            'hits' => $hits,
            'expires_at' => $expiresAt
        ]), LOCK_EX);
    }
    
    /**
     * File-based fallback - delete record
     */
    private function deleteRecordFile(string $key): void
    {
        $file = $this->getRecordFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
    
    /**
     * Get file path for rate limit key
     */
    private function getRecordFilePath(string $key): string
    {
        $hash = md5($key);
        return ROOT_PATH . '/storage/cache/rate_limits/' . $hash . '.json';
    }
    
    /**
     * Cleanup expired records (call periodically)
     */
    public static function cleanup(): int
    {
        try {
            $db = Application::getInstance()->db();
            $result = $db->query("DELETE FROM rate_limits WHERE expires_at <= NOW()");
            return $result->rowCount();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
