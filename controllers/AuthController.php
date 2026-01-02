<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Auth Controller (Web)
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Controllers;

use Core\Application;
use Core\Response;
use Services\AuthService;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class AuthController extends BaseController
{
    /**
     * Auth service
     */
    private AuthService $authService;
    
    /**
     * Constructor
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->authService = new AuthService();
    }
    
    /**
     * Show login page
     */
    public function showLogin(): string
    {
        if ($this->app->session()->isLoggedIn()) {
            Response::redirect('/');
        }
        
        return $this->view('auth.login');
    }
    
    /**
     * Handle login
     */
    public function login(): void
    {
        $email = $this->input('email');
        $password = $this->input('password');
        $remember = $this->input('remember') === '1';
        
        $result = $this->authService->attempt($email, $password, $remember);
        
        if (!$result['success']) {
            $messages = [
                'INVALID_CREDENTIALS' => 'بيانات الدخول غير صحيحة',
                'ACCOUNT_LOCKED' => 'الحساب مقفل مؤقتاً، يرجى المحاولة لاحقاً',
                'EMAIL_NOT_VERIFIED' => 'يرجى تأكيد بريدك الإلكتروني أولاً',
                'ACCOUNT_SUSPENDED' => 'الحساب موقوف',
                'ACCOUNT_BANNED' => 'الحساب محظور',
                'TOO_MANY_ATTEMPTS' => 'محاولات كثيرة جداً، يرجى الانتظار'
            ];
            
            $this->app->session()->flash('error', $messages[$result['error']] ?? 'خطأ غير معروف');
            Response::redirect('/login');
        }
        
        $this->app->session()->flash('success', 'مرحباً بعودتك!');
        Response::redirect('/');
    }
    
    /**
     * Show register page
     */
    public function showRegister(): string
    {
        if ($this->app->session()->isLoggedIn()) {
            Response::redirect('/');
        }
        
        return $this->view('auth.register');
    }
    
    /**
     * Handle registration
     */
    public function register(): void
    {
        $data = [
            'username' => $this->input('username'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'password' => $this->input('password'),
            'password_confirmation' => $this->input('password_confirmation'),
        ];
        
        $result = $this->authService->register($data);
        
        if (!$result['success']) {
            if ($result['error'] === 'VALIDATION_ERROR') {
                $errors = [];
                foreach ($result['errors'] as $field => $fieldErrors) {
                    $errors = array_merge($errors, $fieldErrors);
                }
                $this->app->session()->flash('errors', $errors);
            } else {
                $this->app->session()->flash('error', 'فشل التسجيل، يرجى المحاولة لاحقاً');
            }
            Response::redirect('/register');
        }
        
        $this->app->session()->flash('success', 'تم إنشاء الحساب بنجاح! يرجى تأكيد بريدك الإلكتروني.');
        Response::redirect('/login');
    }
    
    /**
     * Logout
     */
    public function logout(): void
    {
        $this->authService->logout();
        $this->app->session()->flash('success', 'تم تسجيل الخروج بنجاح');
        Response::redirect('/login');
    }
    
    /**
     * Show forgot password page
     */
    public function showForgotPassword(): string
    {
        return $this->view('auth.forgot-password');
    }
    
    /**
     * Handle forgot password
     */
    public function forgotPassword(): void
    {
        $email = $this->input('email');
        
        $this->authService->forgotPassword($email);
        
        // Always show success (don't reveal if email exists)
        $this->app->session()->flash('success', 'إذا كان البريد مسجلاً، سيتم إرسال رابط إعادة التعيين');
        Response::redirect('/forgot-password');
    }
    
    /**
     * Show reset password page
     */
    public function showResetPassword(array $params): string
    {
        $token = $params['token'] ?? '';
        return $this->view('auth.reset-password', ['token' => $token]);
    }
    
    /**
     * Handle reset password
     */
    public function resetPassword(): void
    {
        $email = $this->input('email');
        $token = $this->input('token');
        $password = $this->input('password');
        
        $result = $this->authService->resetPassword($email, $token, $password);
        
        if (!$result['success']) {
            $this->app->session()->flash('error', 'رابط إعادة التعيين غير صالح أو منتهي');
            Response::redirect('/forgot-password');
        }
        
        $this->app->session()->flash('success', 'تم تغيير كلمة المرور بنجاح');
        Response::redirect('/login');
    }
    
    /**
     * Verify email
     */
    public function verifyEmail(array $params): void
    {
        $token = $params['token'] ?? '';
        
        $result = $this->authService->verifyEmail($token);
        
        if (!$result['success']) {
            $this->app->session()->flash('error', 'رابط التحقق غير صالح أو منتهي');
            Response::redirect('/login');
        }
        
        $this->app->session()->flash('success', 'تم تأكيد بريدك الإلكتروني بنجاح!');
        Response::redirect('/login');
    }
}
