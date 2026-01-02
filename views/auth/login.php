<?php
/**
 * Login Page
 */
$pageTitle = 'تسجيل الدخول';
ob_start();
?>

<section class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">مرحباً بعودتك</h1>
            <p class="auth-subtitle">سجّل دخولك للمتابعة</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form action="/login" method="POST" data-validate>
            <?= $csrf->field() ?>
            
            <div class="form-group">
                <label class="form-label" for="email">البريد الإلكتروني أو اسم المستخدم</label>
                <input 
                    type="text" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="أدخل بريدك الإلكتروني"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">كلمة المرور</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="أدخل كلمة المرور"
                    required
                >
            </div>
            
            <div class="form-group d-flex justify-between align-center">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="remember" value="1">
                    <span class="text-muted">تذكرني</span>
                </label>
                <a href="/forgot-password" class="text-primary">نسيت كلمة المرور؟</a>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 btn-lg">
                تسجيل الدخول
            </button>
        </form>
        
        <div class="auth-divider">أو</div>
        
        <button class="btn btn-secondary w-100" onclick="alert('قريباً')">
            <i class="bi bi-google"></i>
            الدخول عبر Google
        </button>
        
        <div class="auth-footer">
            ليس لديك حساب؟ <a href="/register">إنشاء حساب جديد</a>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
