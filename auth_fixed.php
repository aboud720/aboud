<?php
/**
 * Authentication and Database Configuration
 * Fixed version with corrected syntax and security improvements
 */

// استخدام متغيرات البيئة بدلاً من بيانات الاعتماد المضمنة في الكود
// Fixed: Use environment variables instead of hardcoded credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_CHARSET', 'utf8mb4');

// متغير لحفظ الاتصال
$pdo_connection = null;

// الاتصال بقاعدة البيانات (Singleton Pattern)
function getDBConnection() {
    global $pdo_connection;
    
    // إذا كان الاتصال موجوداً، إرجاعه
    if ($pdo_connection !== null) {
        return $pdo_connection;
    }
    
    // إضافة التحقق من صحة بيانات الاعتماد المطلوبة
    // Fixed: Validate required credentials before attempting connection
    if (empty(DB_USER) || empty(DB_NAME)) {
        error_log("خطأ في تهيئة قاعدة البيانات: متغيرات البيئة المطلوبة مفقودة");
        throw new Exception("خطأ في تهيئة قاعدة البيانات. يرجى التحقق من تهيئة الخادم.");
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ];
        $pdo_connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo_connection;
    } catch (PDOException $e) {  // Fixed: lowercase "catch" instead of "Catch"
        // إصلاح معالجة الأخطاء - تسجيل الخطأ الفعلي، وعرض رسالة عامة للمستخدمين
        error_log("خطأ في اتصال قاعدة البيانات: " . $e->getMessage());
        throw new Exception("غير قادر على الاتصال بقاعدة البيانات. يرجى المحاولة مرة أخرى لاحقًا.");
    }
}

// التحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// الحصول على معلومات المستخدم الحالية
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {  // Fixed: "try" instead of "حاول" (Arabic)
        $pdo = getDBConnection();
        // Fixed: Consistent lowercase "status" instead of "Status"
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");  // Fixed: "?" instead of "؟"
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();  // Fixed: "return" instead of "إرجاع"
    } catch (Exception $e) {  // Fixed: "catch (Exception" instead of "Catch (استثناء"
        return null;
    }
}

// تسجيل الدخول
function login($email, $password) {  // Fixed: "function" instead of "وظيفة"
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");  // Fixed: "?" instead of "؟"
        $stmt->execute([$email]);  // Fixed: "execute" instead of "تنفيذ"
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // التحقق من حالة الحساب
            // Fixed: Consistent lowercase "status" and "suspended" spelling
            if ($user['status'] === 'banned' || $user['status'] === 'suspended') {  // Fixed: "suspended" not "suspending"
                return ['success' => false, 'message' => 'الحساب موقوف. يرجى التواصل مع الدعم.'];
            }
            
            // Fixed: Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);
            
            // إنشاء الجلسة
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // تحديث آخر تسجيل دخول
            // Fixed: "updated_at" column name consistency
            $updateStmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");  // Fixed: "?" instead of "؟"
            $updateStmt->execute([$user['id']]);
            
            // تسجيل العملية
            logAction($user['id'], 'login', 'تسجيل دخول ناجح');
            
            return ['success' => true, 'user' => $user];  // Fixed: "true" instead of "صحيح"
        } else {
            return ['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'];  // Fixed: "false" instead of "خطأ"
        }
    } catch (Exception $e) {  // Fixed: lowercase "catch"
        return ['success' => false, 'message' => 'حدث خطأ أثناء تسجيل الدخول.'];
    }
}

// تسجيل الخروج
function logout() {
    if (isLoggedIn()) {
        logAction($_SESSION['user_id'], 'logout', 'تسجيل خروج');
    }
    
    session_unset();
    session_destroy();
    session_start();
}

// تسجيل مستخدم جديد
function register($data) {  // Fixed: lowercase "register" for consistency
    try {
        $pdo = getDBConnection();
        
        // التحقق من وجود البريد
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");  // Fixed: "?" instead of "؟"
        $checkStmt->execute([$data['email']]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'البريد الإلكتروني مستخدم بالفعل.'];
        }
        
        // تشفير كلمة المرور
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // إنشاء referral code
        $referralCode = strtoupper(substr(md5($data['email'] . time()), 0, 8));
        
        // التحقق من كود الوكيل إن وجد
        $referredBy = null;
        if (!empty($data['referral_code'])) {
            // Fixed: Consistent column name "referral_code" (lowercase)
            $refStmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");  // Fixed: "?" instead of "؟"
            $refStmt->execute([$data['referral_code']]);
            $refUser = $refStmt->fetch();
            if ($refUser) {
                $referredBy = $refUser['id'];
            }
        }
        
        // إدراج المستخدم الجديد
        // Fixed: Consistent column name "referred_by" (not "referral_by")
        $insertStmt = $pdo->prepare("
            INSERT INTO users (full_name, email, password, phone, country, referral_code, referred_by, role, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 'active')
        ");
        
        $insertStmt->execute([
            $data['full_name'],
            $data['email'],
            $hashedPassword,
            $data['phone'] ?? null,  // Fixed: "??" instead of "؟؟"
            $data['country'] ?? null,
            $referralCode,
            $referredBy
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // تسجيل الدخول تلقائياً
        $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        
        // Fixed: Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        
        logAction($userId, 'register', 'تسجيل حساب جديد');
        
        return ['success' => true, 'user' => $user];
        
    } catch (Exception $e) {
        // Fixed: Don't expose exception details to users (information disclosure vulnerability)
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'حدث خطأ أثناء التسجيل. يرجى المحاولة لاحقاً.'];
    }
}

// تسجيل العمليات
function logAction($userId, $action, $details = '') {
    try {
        $pdo = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';  // Fixed: "??" instead of "؟؟", 'unknown' instead of 'مجهول'
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $action, $details, $ip, $userAgent]);
    } catch (Exception $e) {
        // تجاهل أخطاء السجلات - silent failure for logging is acceptable
    }
}

// التحقق من IP المحظور
function isIPBanned($ip = null) {
    if ($ip === null) {
        // Fixed: Always use REMOTE_ADDR for security-critical IP checks
        // Headers like X-Forwarded-For can be spoofed by attackers
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';  // Fixed: "??" instead of "؟؟"
        
        // Validate IP format to prevent injection
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '';
        }
    }
    
    // Fixed: Return true (banned) if IP is empty/invalid to fail securely
    if (empty($ip)) {
        error_log("IP ban check failed: Invalid or empty IP address");
        return true;
    }
    
    try {  // Fixed: "try" instead of "حاول"
        $pdo = getDBConnection();
        // Fixed: Correct table name "banned_ips" and proper "?" placeholder
        $stmt = $pdo->prepare("SELECT id FROM banned_ips WHERE ip_address = ?");  // Fixed: "banned_ips" not "Bann_ips"
        $stmt->execute([$ip]);  // Fixed: "execute" instead of "تنفيذ"
        return $stmt->fetch() !== false;  // Fixed: "return" and "false" in English
    } catch (Exception $e) {  // Fixed: "catch (Exception" instead of "قبض (استثناء"
        // Fixed: Fail secure on database errors
        error_log("IP ban check error: " . $e->getMessage());
        return true;  // Fixed: "return true" instead of "إرجاع خطأ"
    }
}
?>
