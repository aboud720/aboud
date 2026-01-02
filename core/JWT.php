<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - JWT Token Handler
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  JSON Web Token implementation for API authentication
 * @security     
 *   - HMAC-SHA256 signature
 *   - Expiration validation
 *   - Token refresh mechanism
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Core;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class JWT
{
    /**
     * Configuration
     */
    private array $config;
    
    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Generate JWT token
     */
    public function generate(array $payload): string
    {
        $header = [
            'alg' => $this->config['algorithm'],
            'typ' => 'JWT'
        ];
        
        // Add standard claims
        $payload['iss'] = $this->config['issuer'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->config['expire'];
        $payload['jti'] = bin2hex(random_bytes(16)); // Unique token ID
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = $this->sign("{$headerEncoded}.{$payloadEncoded}");
        
        return "{$headerEncoded}.{$payloadEncoded}.{$signature}";
    }
    
    /**
     * Generate refresh token
     */
    public function generateRefreshToken(int $userId): string
    {
        return $this->generate([
            'sub' => $userId,
            'type' => 'refresh',
            'exp' => time() + $this->config['refresh']
        ]);
    }
    
    /**
     * Validate and decode JWT token
     */
    public function validate(string $token): ?array
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        list($headerEncoded, $payloadEncoded, $signature) = $parts;
        
        // Verify signature
        $expectedSignature = $this->sign("{$headerEncoded}.{$payloadEncoded}");
        
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }
        
        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            return null;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        // Verify issuer
        if (isset($payload['iss']) && $payload['iss'] !== $this->config['issuer']) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Get user ID from token
     */
    public function getUserId(string $token): ?int
    {
        $payload = $this->validate($token);
        
        if (!$payload || !isset($payload['sub'])) {
            return null;
        }
        
        return (int) $payload['sub'];
    }
    
    /**
     * Check if token is expired
     */
    public function isExpired(string $token): bool
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return true;
        }
        
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        
        if (!$payload || !isset($payload['exp'])) {
            return true;
        }
        
        return $payload['exp'] < time();
    }
    
    /**
     * Check if token is refresh token
     */
    public function isRefreshToken(string $token): bool
    {
        $payload = $this->validate($token);
        return $payload && isset($payload['type']) && $payload['type'] === 'refresh';
    }
    
    /**
     * Sign data with secret
     */
    private function sign(string $data): string
    {
        return $this->base64UrlEncode(
            hash_hmac('sha256', $data, $this->config['secret'], true)
        );
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64UrlDecode(string $data): string
    {
        $padding = 4 - strlen($data) % 4;
        if ($padding !== 4) {
            $data .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Extract token from Authorization header
     */
    public static function fromRequest(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
