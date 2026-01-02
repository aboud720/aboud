<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Security Service
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Comprehensive security utilities
 * @security     
 *   - Password hashing with Argon2ID
 *   - AES-256-GCM encryption
 *   - Secure token generation
 *   - XSS prevention
 *   - Input validation
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Core;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class Security
{
    /**
     * Configuration
     */
    private array $config;
    
    /**
     * Encryption key (derived from app key)
     */
    private string $encryptionKey;
    
    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        
        // Derive encryption key from app key
        $appKey = Application::getInstance()->config('app.key');
        $this->encryptionKey = hash('sha256', $appKey, true);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // PASSWORD HASHING
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Hash password using Argon2ID
     * 
     * Why Argon2ID?
     * - Winner of Password Hashing Competition
     * - Resistant to GPU attacks
     * - Resistant to side-channel attacks
     * - Memory-hard to prevent ASIC attacks
     */
    public function hashPassword(string $password): string
    {
        return password_hash(
            $password,
            $this->config['password_algo'],
            $this->config['password_options']
        );
    }
    
    /**
     * Verify password against hash
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing (algorithm updated)
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash(
            $hash,
            $this->config['password_algo'],
            $this->config['password_options']
        );
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // ENCRYPTION (AES-256-GCM)
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Encrypt data using AES-256-GCM
     * 
     * Why AES-256-GCM?
     * - Authenticated encryption (integrity + confidentiality)
     * - Tag prevents tampering
     * - Industry standard
     */
    public function encrypt(string $plaintext): string
    {
        // Generate random IV
        $ivLength = openssl_cipher_iv_length($this->config['encryption_method']);
        $iv = random_bytes($ivLength);
        
        // Encrypt with authentication tag
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->config['encryption_method'],
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // Tag length
        );
        
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }
        
        // Combine IV + Tag + Ciphertext and encode
        return base64_encode($iv . $tag . $ciphertext);
    }
    
    /**
     * Decrypt data
     */
    public function decrypt(string $encrypted): string
    {
        $data = base64_decode($encrypted, true);
        if ($data === false) {
            throw new \RuntimeException('Invalid encrypted data');
        }
        
        $ivLength = openssl_cipher_iv_length($this->config['encryption_method']);
        $tagLength = 16;
        
        // Extract IV, Tag, and Ciphertext
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, $tagLength);
        $ciphertext = substr($data, $ivLength + $tagLength);
        
        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->config['encryption_method'],
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed');
        }
        
        return $plaintext;
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // TOKEN GENERATION
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Generate cryptographically secure random token
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate URL-safe token
     */
    public function generateUrlSafeToken(int $length = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
    }
    
    /**
     * Hash token for storage (so stored tokens can't be used if DB is compromised)
     */
    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // XSS PREVENTION
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Escape output for HTML
     */
    public function escapeHtml(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Escape for JavaScript
     */
    public function escapeJs(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return json_encode($string, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
    
    /**
     * Escape for URL
     */
    public function escapeUrl(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return rawurlencode($string);
    }
    
    /**
     * Clean and sanitize HTML (allow specific tags)
     */
    public function sanitizeHtml(string $html, array $allowedTags = []): string
    {
        // Strip all tags if none allowed
        if (empty($allowedTags)) {
            return strip_tags($html);
        }
        
        // Allow specific tags
        $tagString = '<' . implode('><', $allowedTags) . '>';
        return strip_tags($html, $tagString);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // INPUT VALIDATION
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Validate email
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone (Saudi format)
     */
    public function validatePhone(string $phone): bool
    {
        // Saudi phone: +966XXXXXXXXX or 05XXXXXXXX
        return (bool) preg_match('/^(\+966|0)?5\d{8}$/', $phone);
    }
    
    /**
     * Validate username
     */
    public function validateUsername(string $username): bool
    {
        // 3-30 characters, alphanumeric and underscores only
        return (bool) preg_match('/^[a-zA-Z][a-zA-Z0-9_]{2,29}$/', $username);
    }
    
    /**
     * Validate password strength
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }
    
    /**
     * Validate UUID format
     */
    public function validateUuid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }
    
    /**
     * Sanitize filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path information
        $filename = basename($filename);
        
        // Remove non-alphanumeric except dots, underscores, hyphens
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Prevent double extensions
        $parts = explode('.', $filename);
        if (count($parts) > 2) {
            $extension = array_pop($parts);
            $filename = implode('_', $parts) . '.' . $extension;
        }
        
        return $filename;
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // TIMING-SAFE COMPARISON
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Timing-safe string comparison
     */
    public function timingSafeEquals(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // IP ADDRESS HANDLING
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get client IP address (handles proxies)
     */
    public function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // X-Forwarded-For can contain multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Check if IP is in allowed range
     */
    public function isIpAllowed(string $ip, array $allowedRanges): bool
    {
        foreach ($allowedRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if IP is in CIDR range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);
        
        return ($ip & $mask) === ($subnet & $mask);
    }
}
