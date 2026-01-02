<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - User Model
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Models;

use Core\Model;
use Core\Application;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class User extends Model
{
    /**
     * Table name
     */
    protected static string $table = 'users';
    
    /**
     * Fillable fields
     */
    protected static array $fillable = [
        'uuid',
        'username',
        'email',
        'phone',
        'password_hash',
        'role',
        'status',
        'balance',
        'avatar',
        'locale',
        'timezone',
    ];
    
    /**
     * Hidden fields (never expose)
     */
    protected static array $hidden = [
        'password_hash',
        'two_factor_secret',
        'remember_token',
    ];
    
    /**
     * Soft deletes enabled
     */
    protected static bool $softDeletes = true;
    
    /**
     * User roles
     */
    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_VENDOR = 'vendor';
    public const ROLE_SUPPORT = 'support';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';
    
    /**
     * User statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_BANNED = 'banned';
    
    // ═══════════════════════════════════════════════════════════════════════
    // AUTHENTICATION METHODS
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Find by email
     */
    public static function findByEmail(string $email): ?self
    {
        return static::findBy('email', $email);
    }
    
    /**
     * Find by username
     */
    public static function findByUsername(string $username): ?self
    {
        return static::findBy('username', $username);
    }
    
    /**
     * Find by email or username
     */
    public static function findByCredential(string $credential): ?self
    {
        $user = static::findByEmail($credential);
        
        if (!$user) {
            $user = static::findByUsername($credential);
        }
        
        return $user;
    }
    
    /**
     * Register new user
     */
    public static function register(array $data): self
    {
        $security = Application::getInstance()->security();
        
        $user = new self([
            'uuid'          => static::db()->uuid(),
            'username'      => $data['username'],
            'email'         => strtolower($data['email']),
            'phone'         => $data['phone'] ?? null,
            'password_hash' => $security->hashPassword($data['password']),
            'role'          => self::ROLE_CUSTOMER,
            'status'        => self::STATUS_PENDING,
            'balance'       => 0,
            'locale'        => $data['locale'] ?? 'ar',
            'timezone'      => $data['timezone'] ?? 'Asia/Riyadh',
        ]);
        
        $user->save();
        
        return $user;
    }
    
    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        $security = Application::getInstance()->security();
        return $security->verifyPassword($password, $this->password_hash);
    }
    
    /**
     * Update password
     */
    public function updatePassword(string $newPassword): bool
    {
        $security = Application::getInstance()->security();
        $this->password_hash = $security->hashPassword($newPassword);
        return $this->save();
    }
    
    /**
     * Check if password needs rehash
     */
    public function passwordNeedsRehash(): bool
    {
        return Application::getInstance()->security()->needsRehash($this->password_hash);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // LOGIN TRACKING
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Record successful login
     */
    public function recordLogin(): void
    {
        $security = Application::getInstance()->security();
        
        $this->last_login_at = date('Y-m-d H:i:s');
        $this->last_login_ip = $security->getClientIp();
        $this->last_activity_at = date('Y-m-d H:i:s');
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }
    
    /**
     * Record failed login attempt
     */
    public function recordFailedLogin(): void
    {
        $this->failed_login_attempts++;
        
        // Lock after 5 failed attempts
        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        }
        
        $this->save();
    }
    
    /**
     * Check if account is locked
     */
    public function isLocked(): bool
    {
        if ($this->locked_until === null) {
            return false;
        }
        
        return strtotime($this->locked_until) > time();
    }
    
    /**
     * Get remaining lock time in seconds
     */
    public function getLockTimeRemaining(): int
    {
        if (!$this->isLocked()) {
            return 0;
        }
        
        return max(0, strtotime($this->locked_until) - time());
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // AUTHORIZATION
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Check if user has role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }
    
    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }
    
    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
    
    /**
     * Check if email is verified
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // WALLET / BALANCE
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get balance in currency format
     */
    public function getFormattedBalance(): string
    {
        return number_format($this->balance / 100, 2) . ' ر.س';
    }
    
    /**
     * Add to balance (with transaction)
     */
    public function addBalance(int $amount, string $type, ?string $reference = null): bool
    {
        return static::db()->transaction(function($db) use ($amount, $type, $reference) {
            $balanceBefore = $this->balance;
            $this->balance += $amount;
            $this->save();
            
            // Create transaction record
            $db->insert('transactions', [
                'uuid' => $db->uuid(),
                'user_id' => $this->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'currency' => 'SAR',
                'reference_type' => $reference ? 'manual' : null,
                'description' => $reference,
                'ip_address' => Application::getInstance()->security()->getClientIp(),
            ]);
            
            return true;
        });
    }
    
    /**
     * Deduct from balance
     */
    public function deductBalance(int $amount, string $type, ?int $orderId = null): bool
    {
        if ($this->balance < $amount) {
            return false;
        }
        
        return static::db()->transaction(function($db) use ($amount, $type, $orderId) {
            $balanceBefore = $this->balance;
            $this->balance -= $amount;
            $this->save();
            
            // Create transaction record
            $db->insert('transactions', [
                'uuid' => $db->uuid(),
                'user_id' => $this->id,
                'type' => $type,
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'currency' => 'SAR',
                'reference_type' => $orderId ? 'order' : null,
                'reference_id' => $orderId,
                'ip_address' => Application::getInstance()->security()->getClientIp(),
            ]);
            
            return true;
        });
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // EMAIL VERIFICATION
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Generate email verification token
     */
    public function generateEmailVerificationToken(): string
    {
        $security = Application::getInstance()->security();
        $token = $security->generateUrlSafeToken(32);
        
        static::db()->insert('email_verifications', [
            'user_id' => $this->id,
            'email' => $this->email,
            'token_hash' => $security->hashToken($token),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
        ]);
        
        return $token;
    }
    
    /**
     * Verify email token
     */
    public static function verifyEmailToken(string $token): ?self
    {
        $security = Application::getInstance()->security();
        $tokenHash = $security->hashToken($token);
        
        $verification = static::db()->selectOne(
            "SELECT * FROM email_verifications 
             WHERE token_hash = ? 
             AND verified_at IS NULL 
             AND expires_at > NOW()",
            [$tokenHash]
        );
        
        if (!$verification) {
            return null;
        }
        
        $user = static::find($verification['user_id']);
        
        if ($user) {
            // Mark as verified
            $user->email_verified_at = date('Y-m-d H:i:s');
            if ($user->status === self::STATUS_PENDING) {
                $user->status = self::STATUS_ACTIVE;
            }
            $user->save();
            
            // Mark token as used
            static::db()->update(
                'email_verifications',
                ['verified_at' => date('Y-m-d H:i:s')],
                ['id' => $verification['id']]
            );
        }
        
        return $user;
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // PASSWORD RESET
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken(): string
    {
        $security = Application::getInstance()->security();
        $token = $security->generateUrlSafeToken(32);
        
        // Invalidate old tokens
        static::db()->query(
            "DELETE FROM password_resets WHERE email = ?",
            [$this->email]
        );
        
        static::db()->insert('password_resets', [
            'email' => $this->email,
            'token_hash' => $security->hashToken($token),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
        
        return $token;
    }
    
    /**
     * Verify password reset token
     */
    public static function verifyPasswordResetToken(string $email, string $token): bool
    {
        $security = Application::getInstance()->security();
        $tokenHash = $security->hashToken($token);
        
        $reset = static::db()->selectOne(
            "SELECT * FROM password_resets 
             WHERE email = ? 
             AND token_hash = ? 
             AND used_at IS NULL 
             AND expires_at > NOW()",
            [$email, $tokenHash]
        );
        
        return $reset !== null;
    }
    
    /**
     * Reset password with token
     */
    public static function resetPasswordWithToken(string $email, string $token, string $newPassword): bool
    {
        if (!static::verifyPasswordResetToken($email, $token)) {
            return false;
        }
        
        $user = static::findByEmail($email);
        if (!$user) {
            return false;
        }
        
        $security = Application::getInstance()->security();
        $tokenHash = $security->hashToken($token);
        
        return static::db()->transaction(function($db) use ($user, $newPassword, $email, $tokenHash) {
            // Update password
            $user->updatePassword($newPassword);
            
            // Mark token as used
            $db->update(
                'password_resets',
                ['used_at' => date('Y-m-d H:i:s')],
                ['email' => $email, 'token_hash' => $tokenHash]
            );
            
            return true;
        });
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // ACTIVITY LOGGING
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Log user activity
     */
    public function logActivity(string $action, ?string $subjectType = null, ?int $subjectId = null, ?array $properties = null): void
    {
        $security = Application::getInstance()->security();
        
        static::db()->insert('activity_logs', [
            'user_id' => $this->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties ? json_encode($properties) : null,
            'ip_address' => $security->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
    
    /**
     * Update last activity
     */
    public function touch(): void
    {
        $this->last_activity_at = date('Y-m-d H:i:s');
        $this->save();
    }
}
