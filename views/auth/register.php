<?php
/**
 * Register Page
 */
$pageTitle = 'إنشاء حساب';
ob_start();
?>

<section class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">إنشاء حساب جديد</h1>
            <p class="auth-subtitle">انضم إلينا واستمتع بأفضل العروض</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i>
                <ul style="margin: 0; padding-right: 1rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="/register" method="POST" data-validate>
            <?= $csrf->field() ?>
            
            <div class="form-group">
                <label class="form-label" for="username">اسم المستخدم</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                    placeholder="اختر اسم مستخدم"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                    autofocus
                >
                <span class="form-text">3-30 حرف، أحرف وأرقام و _ فقط</span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">البريد الإلكتروني</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                    placeholder="أدخل بريدك الإلكتروني"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone">رقم الهاتف (اختياري)</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    class="form-control" 
                    placeholder="05XXXXXXXX"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                    dir="ltr"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">كلمة المرور</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                    placeholder="اختر كلمة مرور قوية"
                    required
                >
                <span class="form-text">8 أحرف على الأقل، حروف كبيرة وصغيرة وأرقام ورموز</span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password_confirmation">تأكيد كلمة المرور</label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    class="form-control" 
                    placeholder="أعد كتابة كلمة المرور"
                    required
                >
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="terms" value="1" required style="margin-top: 4px;">
                    <span class="text-muted">
                        أوافق على <a href="/terms" class="text-primary">الشروط والأحكام</a>
                        و <a href="/privacy" class="text-primary">سياسة الخصوصية</a>
                    </span>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 btn-lg">
                إنشاء الحساب
            </button>
        </form>
        
        <div class="auth-divider">أو</div>
        
        <button class="btn btn-secondary w-100" onclick="alert('قريباً')">
            <i class="bi bi-google"></i>
            التسجيل عبر Google
        </button>
        
        <div class="auth-footer">
            لديك حساب بالفعل؟ <a href="/login">تسجيل الدخول</a>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
