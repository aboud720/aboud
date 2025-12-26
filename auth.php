<?php
// التحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// الحصول على معلومات المستخدم الحالي
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// تسجيل الدخول
function login($email, $password) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // التحقق من حالة الحساب
            if ($user['status'] === 'banned' || $user['status'] === 'suspended') {
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
            $updateStmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // تسجيل العملية
            logAction($user['id'], 'login', 'تسجيل دخول ناجح');
            
            return ['success' => true, 'user' => $user];
        } else {
            return ['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'];
        }
    } catch (Exception $e) {
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
function register($data) {
    try {
        $pdo = getDBConnection();
        
        // التحقق من وجود البريد
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
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
            $refStmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
            $refStmt->execute([$data['referral_code']]);
            $refUser = $refStmt->fetch();
            if ($refUser) {
                $referredBy = $refUser['id'];
            }
        }
        
        // إدراج المستخدم الجديد
        $insertStmt = $pdo->prepare("
            INSERT INTO users (full_name, email, password, phone, country, referral_code, referred_by, role, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 'active')
        ");
        
        $insertStmt->execute([
            $data['full_name'],
            $data['email'],
            $hashedPassword,
            $data['phone'] ?? null,
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
        // Log the actual error for debugging
        error_log("Registration error: " . $e->getMessage());
        // Return generic message to users
        return ['success' => false, 'message' => 'حدث خطأ أثناء التسجيل. يرجى المحاولة لاحقاً.'];
    }
}

// تسجيل العمليات
function logAction($userId, $action, $details = '') {
    try {
        $pdo = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $action, $details, $ip, $userAgent]);
    } catch (Exception $e) {
        // تجاهل أخطاء السجلات
    }
}

// التحقق من IP المحظور
function isIPBanned($ip = null) {
    if ($ip === null) {
        // Fixed: Always use REMOTE_ADDR for security-critical IP checks
        // Headers like X-Forwarded-For can be spoofed by attackers to bypass IP bans
        // Only use proxy headers if you're behind a trusted reverse proxy AND
        // you validate the request comes from the proxy
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Validate IP format to prevent injection
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '';
        }
    }
    
    // Fixed: Return true (banned) if IP is empty/invalid to fail securely
    if (empty($ip)) {
        error_log("IP ban check failed: Invalid or empty IP address");
        return true; // Fail secure - treat invalid IP as banned
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM banned_ips WHERE ip_address = ?");
        $stmt->execute([$ip]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        // Fixed: Fail secure on database errors - treat as banned
        error_log("IP ban check error: " . $e->getMessage());
        return true;
    }
}
?>
