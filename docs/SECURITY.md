# 🔒 دليل الأمان - ABOUD Store

## نظرة عامة

هذا المستند يوضح جميع التدابير الأمنية المطبقة في نظام ABOUD Store.

---

## 1. أمان كلمات المرور

### الخوارزمية: Argon2ID

```php
// core/Security.php
PASSWORD_ARGON2ID مع:
- memory_cost: 65536 (64MB)
- time_cost: 4 iterations
- threads: 3 parallel
```

### لماذا Argon2ID؟
- ✅ الفائز في Password Hashing Competition
- ✅ مقاوم لهجمات GPU
- ✅ مقاوم لهجمات side-channel
- ✅ يستهلك ذاكرة عالية (memory-hard)

### التنفيذ
```php
// التشفير
$hash = password_hash($password, PASSWORD_ARGON2ID, $options);

// التحقق
$valid = password_verify($password, $hash);

// إعادة التشفير إذا تحديث الإعدادات
if (password_needs_rehash($hash, PASSWORD_ARGON2ID, $options)) {
    $newHash = password_hash($password, PASSWORD_ARGON2ID, $options);
}
```

---

## 2. تشفير البيانات

### الخوارزمية: AES-256-GCM

```php
// Authenticated Encryption
- Algorithm: AES-256-GCM
- Key: SHA-256 من APP_KEY
- IV: عشوائي لكل عملية
- Tag: 16 bytes للتحقق من سلامة البيانات
```

### لماذا AES-256-GCM؟
- ✅ تشفير مع مصادقة (Authenticated Encryption)
- ✅ يكشف أي تلاعب بالبيانات
- ✅ معيار صناعي معتمد

### التنفيذ
```php
// التشفير
$encrypted = $security->encrypt($plaintext);
// الناتج: base64(IV + Tag + Ciphertext)

// فك التشفير
$plaintext = $security->decrypt($encrypted);
```

### البيانات المشفرة
- أكواد الشحن في `stock_codes.code_encrypted`
- مفاتيح 2FA في `users.two_factor_secret`
- بيانات الدفع الحساسة

---

## 3. حماية SQL Injection

### المبدأ: Prepared Statements فقط

```php
// ✅ آمن - Prepared Statement
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// ❌ خطير - لا نستخدمه أبداً
$query = "SELECT * FROM users WHERE email = '$email'";
```

### الإعدادات
```php
PDO::ATTR_EMULATE_PREPARES => false // مهم جداً
```

### التطبيق في Database Class
```php
public function query(string $sql, array $params = []): PDOStatement
{
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
```

---

## 4. حماية XSS

### استراتيجية متعددة الطبقات

#### 1. Output Encoding
```php
// HTML
htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// JavaScript
json_encode($string, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// URL
rawurlencode($string);
```

#### 2. Content Security Policy
```
Content-Security-Policy: 
  default-src 'self'; 
  script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; 
  style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
```

#### 3. HTTP Headers
```
X-XSS-Protection: 1; mode=block
X-Content-Type-Options: nosniff
```

---

## 5. حماية CSRF

### Token-Based Protection

```php
// توليد Token
$token = bin2hex(random_bytes(32));
$_SESSION['_csrf_token'] = $token;

// التحقق
hash_equals($_SESSION['_csrf_token'], $_POST['_csrf_token']);
```

### التنفيذ في النماذج
```html
<form method="POST">
    <?= $csrf->field() ?>
    <!-- باقي الحقول -->
</form>
```

### AJAX Requests
```html
<meta name="csrf-token" content="<?= $csrf->getToken() ?>">

<script>
fetch('/api/endpoint', {
    headers: {
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
    }
});
</script>
```

---

## 6. Rate Limiting

### الحماية من Brute Force

```php
// الإعدادات
'rate_limit' => [
    'max_requests' => 60,     // طلب/دقيقة
    'decay_time'   => 60,     // ثانية
    'login_max'    => 5,      // محاولات تسجيل دخول
    'login_decay'  => 900,    // 15 دقيقة قفل
]
```

### تطبيق على Login
```php
if (!$rateLimiter->checkLogin()) {
    // مقفل - إرجاع خطأ 429
}
```

### قفل الحساب
```php
// بعد 5 محاولات فاشلة
if ($user->failed_login_attempts >= 5) {
    $user->locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
}
```

---

## 7. أمان الجلسات

### إعدادات الجلسة
```php
session_set_cookie_params([
    'lifetime' => 7200,        // ساعتين
    'path'     => '/',
    'secure'   => true,        // HTTPS فقط
    'httponly' => true,        // لا JavaScript
    'samesite' => 'Strict',    // CSRF protection
]);
```

### حماية إضافية
```php
// Session Fixation Protection
session_regenerate_id(true); // عند كل login

// Session Hijacking Protection
$_SESSION['_ip'] = hash('sha256', $ip);
$_SESSION['_ua'] = hash('sha256', $userAgent);

// Idle Timeout (30 دقيقة)
if ((time() - $_SESSION['_last_activity']) > 1800) {
    session_destroy();
}

// Absolute Timeout (8 ساعات)
if ((time() - $_SESSION['_created']) > 28800) {
    session_destroy();
}
```

---

## 8. JWT Authentication

### البنية
```
Header.Payload.Signature
```

### الإعدادات
```php
'jwt' => [
    'secret'    => 'مفتاح 64 حرف على الأقل',
    'algorithm' => 'HS256',
    'expire'    => 3600,      // ساعة
    'refresh'   => 604800,    // أسبوع
]
```

### Payload
```json
{
    "sub": 123,           // User ID
    "uuid": "xxx-xxx",    // User UUID
    "role": "customer",
    "iat": 1234567890,    // Issued At
    "exp": 1234571490,    // Expires
    "jti": "random-id"    // Unique Token ID
}
```

---

## 9. التحقق من المدخلات

### Validator Class
```php
$validator = Validator::make($data, [
    'username' => 'required|username|unique:users,username',
    'email'    => 'required|email|unique:users,email',
    'password' => 'required|password|confirmed',
    'phone'    => 'nullable|phone',
]);
```

### قواعد Password
```php
// يجب أن تحتوي على:
- 8 أحرف على الأقل
- حرف كبير واحد على الأقل
- حرف صغير واحد على الأقل
- رقم واحد على الأقل
- رمز خاص واحد على الأقل
```

---

## 10. HTTP Security Headers

### .htaccess
```apache
Header always set X-Frame-Options "DENY"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
```

### PHP
```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header_remove('X-Powered-By');
```

---

## 11. سجل النشاطات

### Activity Logging
```php
$user->logActivity('user.login', null, null, [
    'ip' => $ip,
    'user_agent' => $ua
]);
```

### الأحداث المسجلة
- تسجيل الدخول/الخروج
- تغيير كلمة المرور
- إنشاء/إلغاء الطلبات
- عمليات الدفع
- تغييرات الإدارة

---

## 12. أفضل الممارسات للنشر

### قائمة التحقق
- [ ] `APP_DEBUG=false`
- [ ] HTTPS مفعل
- [ ] شهادة SSL صالحة
- [ ] تغيير `APP_KEY` و `JWT_SECRET`
- [ ] تقييد صلاحيات قاعدة البيانات
- [ ] تفعيل جدار الحماية
- [ ] إعداد النسخ الاحتياطي
- [ ] مراقبة السجلات

### الصلاحيات
```bash
chmod -R 755 public/
chmod -R 700 storage/
chmod -R 700 config/
chmod 600 .env
```

---

## 13. الإبلاغ عن الثغرات

إذا وجدت ثغرة أمنية، يرجى:

1. **لا تنشر** الثغرة علناً
2. أرسل تقريراً إلى: security@aboud-store.com
3. انتظر ردنا خلال 48 ساعة

نقدر مساهمتك في أمان النظام! 🛡️
