<?php
// إعدادات قاعدة البيانات
// Fixed: Use environment variables instead of hardcoded credentials (Security vulnerability)
// Set these environment variables in your server configuration or .env file:
// DB_HOST, DB_USER, DB_PASS, DB_NAME
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
    
    // Fixed: Validate that required credentials are provided
    if (empty(DB_USER) || empty(DB_NAME)) {
        error_log("Database configuration error: Missing required environment variables");
        throw new Exception("Database configuration error. Please check server configuration.");
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
        // Fixed: Don't expose database error details to users (security vulnerability)
        // Log the actual error for debugging
        error_log("Database connection error: " . $e->getMessage());
        // Return a generic error message to users
        throw new Exception("Unable to connect to database. Please try again later.");
    }
}
?>
