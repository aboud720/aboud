<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Authentication Service
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Handles all authentication logic
 * @security     
 *   - Rate limiting on login
 *   - Account lockout
 *   - Secure session management
 *   - Activity logging
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Services;

use Core\Application;
use Core\Validator;
use Models\User;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class AuthService
{
    /**
     * Application instance
     */
    private Application $app;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->app = Application::getInstance();
    }
    
    /**
     * Attempt login
     * 
     * @return array{success: bool, user?: User, error?: string}
     */
    public function attempt(string $credential, string $password, bool $remember = false): array
    {
        // Rate limiting check
        $rateLimiter = $this->app->resolve('rateLimit');
        if (!$rateLimiter->checkLogin()) {
            return [
                'success' => false,
                'error' => 'TOO_MANY_ATTEMPTS',
                'retry_after' => $rateLimiter->getRetryAfter()
            ];
        }
        
        // Find user
        $user = User::findByCredential($credential);
        
        if (!$user) {
            // Don't reveal if email exists
            return [
                'success' => false,
                'error' => 'INVALID_CREDENTIALS'
            ];
        }
        
        // Check if account is locked
        if ($user->isLocked()) {
            return [
                'success' => false,
                'error' => 'ACCOUNT_LOCKED',
                'retry_after' => $user->getLockTimeRemaining()
            ];
        }
        
        // Verify password
        if (!$user->verifyPassword($password)) {
            $user->recordFailedLogin();
            
            return [
                'success' => false,
                'error' => 'INVALID_CREDENTIALS'
            ];
        }
        
        // Check account status
        if (!$user->isActive()) {
            if ($user->status === User::STATUS_PENDING) {
                return [
                    'success' => false,
                    'error' => 'EMAIL_NOT_VERIFIED'
                ];
            }
            
            if ($user->status === User::STATUS_SUSPENDED) {
                return [
                    'success' => false,
                    'error' => 'ACCOUNT_SUSPENDED'
                ];
            }
            
            if ($user->status === User::STATUS_BANNED) {
                return [
                    'success' => false,
                    'error' => 'ACCOUNT_BANNED'
                ];
            }
        }
        
        // Check if password needs rehash
        if ($user->passwordNeedsRehash()) {
            $user->updatePassword($password);
        }
        
        // Record successful login
        $user->recordLogin();
        
        // Create session
        $this->createSession($user, $remember);
        
        // Log activity
        $user->logActivity('user.login');
        
        // Clear rate limit
        $rateLimiter->clear('login');
        
        return [
            'success' => true,
            'user' => $user
        ];
    }
    
    /**
     * Register new user
     */
    public function register(array $data): array
    {
        // Validate input
        $validator = Validator::make($data, [
            'username' => 'required|username|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|password|confirmed',
            'phone'    => 'nullable|phone',
        ]);
        
        if (!$validator->validate()) {
            return [
                'success' => false,
                'error' => 'VALIDATION_ERROR',
                'errors' => $validator->errors()
            ];
        }
        
        try {
            // Create user
            $user = User::register($validator->validated());
            
            // Generate email verification token
            $verificationToken = $user->generateEmailVerificationToken();
            
            // TODO: Send verification email
            // EmailService::sendVerificationEmail($user, $verificationToken);
            
            // Log activity
            $user->logActivity('user.register');
            
            return [
                'success' => true,
                'user' => $user,
                'verification_token' => $verificationToken // Remove in production
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'REGISTRATION_FAILED'
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout(): void
    {
        $user = $this->getCurrentUser();
        
        if ($user) {
            $user->logActivity('user.logout');
        }
        
        $this->app->session()->logout();
    }
    
    /**
     * Create user session
     */
    private function createSession(User $user, bool $remember = false): void
    {
        $session = $this->app->session();
        
        $session->login($user->id, [
            'uuid' => $user->uuid,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
        ]);
        
        // Set remember token if requested
        if ($remember) {
            $this->setRememberToken($user);
        }
    }
    
    /**
     * Set remember me token
     */
    private function setRememberToken(User $user): void
    {
        $security = $this->app->security();
        $token = $security->generateToken(32);
        
        $user->remember_token = $security->hashToken($token);
        $user->save();
        
        // Set cookie (30 days)
        setcookie(
            'remember_token',
            $user->id . '|' . $token,
            [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }
    
    /**
     * Try to login from remember token
     */
    public function loginFromRememberToken(): bool
    {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $parts = explode('|', $_COOKIE['remember_token']);
        if (count($parts) !== 2) {
            $this->clearRememberToken();
            return false;
        }
        
        list($userId, $token) = $parts;
        
        $user = User::find($userId);
        if (!$user || !$user->isActive()) {
            $this->clearRememberToken();
            return false;
        }
        
        $security = $this->app->security();
        $tokenHash = $security->hashToken($token);
        
        if (!$security->timingSafeEquals($user->remember_token ?? '', $tokenHash)) {
            $this->clearRememberToken();
            return false;
        }
        
        // Login user
        $user->recordLogin();
        $this->createSession($user, true);
        
        return true;
    }
    
    /**
     * Clear remember token
     */
    private function clearRememberToken(): void
    {
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    /**
     * Get current logged in user
     */
    public function getCurrentUser(): ?User
    {
        $session = $this->app->session();
        
        if (!$session->isLoggedIn()) {
            return null;
        }
        
        $userId = $session->userId();
        return User::find($userId);
    }
    
    /**
     * Check if user is logged in
     */
    public function check(): bool
    {
        return $this->app->session()->isLoggedIn();
    }
    
    /**
     * Check if user is guest
     */
    public function guest(): bool
    {
        return !$this->check();
    }
    
    /**
     * Get current user's ID
     */
    public function id(): ?int
    {
        return $this->app->session()->userId();
    }
    
    /**
     * Verify email
     */
    public function verifyEmail(string $token): array
    {
        $user = User::verifyEmailToken($token);
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'INVALID_TOKEN'
            ];
        }
        
        $user->logActivity('user.email_verified');
        
        return [
            'success' => true,
            'user' => $user
        ];
    }
    
    /**
     * Request password reset
     */
    public function forgotPassword(string $email): array
    {
        // Rate limit
        $rateLimiter = $this->app->resolve('rateLimit');
        if (!$rateLimiter->check('password_reset', 3, 3600)) {
            return [
                'success' => false,
                'error' => 'TOO_MANY_ATTEMPTS'
            ];
        }
        
        $user = User::findByEmail($email);
        
        // Always return success (don't reveal if email exists)
        if ($user) {
            $token = $user->generatePasswordResetToken();
            
            // TODO: Send reset email
            // EmailService::sendPasswordResetEmail($user, $token);
            
            $user->logActivity('user.password_reset_requested');
        }
        
        return [
            'success' => true,
            'message' => 'If the email exists, you will receive a password reset link'
        ];
    }
    
    /**
     * Reset password
     */
    public function resetPassword(string $email, string $token, string $newPassword): array
    {
        $validator = Validator::make(
            ['password' => $newPassword, 'password_confirmation' => $newPassword],
            ['password' => 'required|password']
        );
        
        if (!$validator->validate()) {
            return [
                'success' => false,
                'error' => 'VALIDATION_ERROR',
                'errors' => $validator->errors()
            ];
        }
        
        $result = User::resetPasswordWithToken($email, $token, $newPassword);
        
        if (!$result) {
            return [
                'success' => false,
                'error' => 'INVALID_TOKEN'
            ];
        }
        
        $user = User::findByEmail($email);
        $user->logActivity('user.password_reset');
        
        return [
            'success' => true
        ];
    }
    
    /**
     * Change password (authenticated user)
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'USER_NOT_FOUND'
            ];
        }
        
        // Verify current password
        if (!$user->verifyPassword($currentPassword)) {
            return [
                'success' => false,
                'error' => 'INVALID_CURRENT_PASSWORD'
            ];
        }
        
        // Validate new password
        $validator = Validator::make(
            ['password' => $newPassword, 'password_confirmation' => $newPassword],
            ['password' => 'required|password']
        );
        
        if (!$validator->validate()) {
            return [
                'success' => false,
                'error' => 'VALIDATION_ERROR',
                'errors' => $validator->errors()
            ];
        }
        
        $user->updatePassword($newPassword);
        $user->logActivity('user.password_changed');
        
        return [
            'success' => true
        ];
    }
}
