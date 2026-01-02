<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Auth API Controller
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Api;

use Controllers\BaseController;
use Core\Application;
use Core\Response;
use Core\JWT;
use Services\AuthService;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class AuthApiController extends BaseController
{
    /**
     * Auth service
     */
    private AuthService $authService;
    
    /**
     * JWT handler
     */
    private JWT $jwt;
    
    /**
     * Constructor
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->authService = new AuthService();
        $this->jwt = new JWT($app->config('security.jwt'));
    }
    
    /**
     * POST /api/auth/login
     */
    public function login(): void
    {
        $validator = $this->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);
        
        $result = $this->authService->attempt(
            $validator->get('email'),
            $validator->get('password')
        );
        
        if (!$result['success']) {
            $messages = [
                'INVALID_CREDENTIALS' => 'بيانات الدخول غير صحيحة',
                'ACCOUNT_LOCKED' => 'الحساب مقفل مؤقتاً، يرجى المحاولة لاحقاً',
                'EMAIL_NOT_VERIFIED' => 'يرجى تأكيد بريدك الإلكتروني أولاً',
                'ACCOUNT_SUSPENDED' => 'الحساب موقوف',
                'ACCOUNT_BANNED' => 'الحساب محظور',
                'TOO_MANY_ATTEMPTS' => 'محاولات كثيرة جداً، يرجى الانتظار'
            ];
            
            $this->error(
                $messages[$result['error']] ?? 'خطأ غير معروف',
                $result['error'],
                $result['error'] === 'TOO_MANY_ATTEMPTS' ? 429 : 401
            );
        }
        
        $user = $result['user'];
        
        // Generate tokens
        $accessToken = $this->jwt->generate([
            'sub' => $user->id,
            'uuid' => $user->uuid,
            'role' => $user->role
        ]);
        
        $refreshToken = $this->jwt->generateRefreshToken($user->id);
        
        $this->success([
            'user' => $user->toArray(),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->app->config('security.jwt.expire')
        ], 'تم تسجيل الدخول بنجاح');
    }
    
    /**
     * POST /api/auth/register
     */
    public function register(): void
    {
        $validator = $this->validate([
            'username' => 'required|username|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|password|confirmed',
            'phone' => 'nullable|phone'
        ]);
        
        $result = $this->authService->register($validator->validated());
        
        if (!$result['success']) {
            if ($result['error'] === 'VALIDATION_ERROR') {
                Response::validationError($result['errors']);
            }
            $this->error('فشل التسجيل', $result['error']);
        }
        
        $user = $result['user'];
        
        $this->success([
            'user' => $user->toArray(),
            'message' => 'تم إنشاء الحساب بنجاح. يرجى تأكيد بريدك الإلكتروني.'
        ], 'تم التسجيل بنجاح');
    }
    
    /**
     * POST /api/auth/refresh
     */
    public function refresh(): void
    {
        $refreshToken = $this->input('refresh_token');
        
        if (!$refreshToken) {
            $this->error('Refresh token required', 'MISSING_TOKEN', 400);
        }
        
        if (!$this->jwt->isRefreshToken($refreshToken)) {
            $this->error('Invalid refresh token', 'INVALID_TOKEN', 401);
        }
        
        $userId = $this->jwt->getUserId($refreshToken);
        
        if (!$userId) {
            $this->error('Invalid refresh token', 'INVALID_TOKEN', 401);
        }
        
        $user = \Models\User::find($userId);
        
        if (!$user || !$user->isActive()) {
            $this->error('User not found or inactive', 'USER_INACTIVE', 401);
        }
        
        // Generate new tokens
        $accessToken = $this->jwt->generate([
            'sub' => $user->id,
            'uuid' => $user->uuid,
            'role' => $user->role
        ]);
        
        $newRefreshToken = $this->jwt->generateRefreshToken($user->id);
        
        $this->success([
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->app->config('security.jwt.expire')
        ]);
    }
    
    /**
     * POST /api/auth/logout
     */
    public function logout(): void
    {
        // For JWT, logout is handled client-side by deleting the token
        // We can optionally blacklist the token here
        
        $this->success(null, 'تم تسجيل الخروج بنجاح');
    }
    
    /**
     * GET /api/auth/me
     */
    public function me(): void
    {
        // This route requires ApiAuthMiddleware
        $user = $this->app->container['api_user'] ?? null;
        
        if (!$user) {
            $this->error('Unauthorized', 'UNAUTHORIZED', 401);
        }
        
        $this->success([
            'user' => $user->toArray()
        ]);
    }
    
    /**
     * POST /api/auth/verify-email
     */
    public function verifyEmail(): void
    {
        $token = $this->input('token');
        
        if (!$token) {
            $this->error('Token required', 'MISSING_TOKEN', 400);
        }
        
        $result = $this->authService->verifyEmail($token);
        
        if (!$result['success']) {
            $this->error('رابط التحقق غير صالح أو منتهي', $result['error']);
        }
        
        $this->success([
            'user' => $result['user']->toArray()
        ], 'تم تأكيد البريد الإلكتروني بنجاح');
    }
    
    /**
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(): void
    {
        $validator = $this->validate([
            'email' => 'required|email'
        ]);
        
        $result = $this->authService->forgotPassword($validator->get('email'));
        
        // Always return success (don't reveal if email exists)
        $this->success(null, 'إذا كان البريد الإلكتروني مسجلاً، سيتم إرسال رابط إعادة التعيين');
    }
    
    /**
     * POST /api/auth/reset-password
     */
    public function resetPassword(): void
    {
        $validator = $this->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|password|confirmed'
        ]);
        
        $result = $this->authService->resetPassword(
            $validator->get('email'),
            $validator->get('token'),
            $validator->get('password')
        );
        
        if (!$result['success']) {
            $this->error('رابط إعادة التعيين غير صالح أو منتهي', $result['error']);
        }
        
        $this->success(null, 'تم تغيير كلمة المرور بنجاح');
    }
    
    /**
     * POST /api/auth/change-password (Authenticated)
     */
    public function changePassword(): void
    {
        $user = $this->app->container['api_user'] ?? null;
        
        if (!$user) {
            $this->error('Unauthorized', 'UNAUTHORIZED', 401);
        }
        
        $validator = $this->validate([
            'current_password' => 'required|string',
            'password' => 'required|password|confirmed'
        ]);
        
        $result = $this->authService->changePassword(
            $user->id,
            $validator->get('current_password'),
            $validator->get('password')
        );
        
        if (!$result['success']) {
            $messages = [
                'INVALID_CURRENT_PASSWORD' => 'كلمة المرور الحالية غير صحيحة'
            ];
            
            $this->error(
                $messages[$result['error']] ?? 'خطأ غير معروف',
                $result['error']
            );
        }
        
        $this->success(null, 'تم تغيير كلمة المرور بنجاح');
    }
}
