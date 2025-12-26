<?php
// إعدادات قاعدة البيانات
// انسخ هذا الملف إلى database.php وقم بتعديل البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database_name');
define('DB_CHARSET', 'utf8mb4');

// متغير static لحفظ الاتصال
static $pdo_connection = null;

// الاتصال بقاعدة البيانات (Singleton Pattern)
function getDBConnection() {
    global $pdo_connection;
    
    // إذا كان الاتصال موجوداً، إرجاعه
    if ($pdo_connection !== null) {
        return $pdo_connection;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false, // تعطيل الاتصال المستمر لتجنب المشاكل
        ];
        $pdo_connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo_connection;
    } catch (PDOException $e) {
        die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
    }
}
?>
