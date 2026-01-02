<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Application Configuration
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Main application configuration file
 * @author       Senior Architect
 * @version      1.0.0
 * @security     Production-Ready Configuration
 * 
 * ⚠️ SECURITY NOTES:
 * - Never commit this file with real credentials to version control
 * - Use environment variables in production
 * - Keep APP_DEBUG = false in production
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Prevent direct access
if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

return [
    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    | Core application configuration parameters
    */
    'app' => [
        'name'          => getenv('APP_NAME') ?: 'ABOUD Store',
        'version'       => '1.0.0',
        'url'           => getenv('APP_URL') ?: 'https://aboud-store.com',
        'env'           => getenv('APP_ENV') ?: 'production',
        'debug'         => (bool)(getenv('APP_DEBUG') ?: false),
        'timezone'      => 'Asia/Riyadh',
        'locale'        => 'ar',
        'charset'       => 'UTF-8',
        'key'           => getenv('APP_KEY') ?: 'CHANGE_THIS_TO_32_CHAR_SECRET_KEY!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    | MySQL connection settings with security options
    */
    'database' => [
        'driver'    => 'mysql',
        'host'      => getenv('DB_HOST') ?: 'localhost',
        'port'      => getenv('DB_PORT') ?: 3306,
        'database'  => getenv('DB_DATABASE') ?: 'aboud_store',
        'username'  => getenv('DB_USERNAME') ?: 'root',
        'password'  => getenv('DB_PASSWORD') ?: '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Important for security
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    | Security-related settings for the application
    */
    'security' => [
        // Password hashing
        'password_algo'     => PASSWORD_ARGON2ID, // Most secure algorithm
        'password_options'  => [
            'memory_cost' => 65536,  // 64MB
            'time_cost'   => 4,      // 4 iterations
            'threads'     => 3,      // 3 parallel threads
        ],
        
        // Session security
        'session_lifetime'  => 120,  // minutes
        'session_secure'    => true, // HTTPS only
        'session_httponly'  => true, // No JavaScript access
        'session_samesite'  => 'Strict',
        
        // CSRF Protection
        'csrf_token_length' => 64,
        'csrf_token_expire' => 3600, // 1 hour
        
        // Rate Limiting
        'rate_limit' => [
            'enabled'      => true,
            'max_requests' => 60,      // per minute
            'decay_time'   => 60,      // seconds
            'login_max'    => 5,       // login attempts
            'login_decay'  => 900,     // 15 minutes lockout
        ],
        
        // JWT Configuration
        'jwt' => [
            'secret'     => getenv('JWT_SECRET') ?: 'CHANGE_THIS_JWT_SECRET_KEY_64_CHARS_MINIMUM!!',
            'algorithm'  => 'HS256',
            'expire'     => 3600,      // 1 hour
            'refresh'    => 604800,    // 7 days
            'issuer'     => 'aboud-store',
        ],
        
        // Encryption
        'encryption_method' => 'AES-256-GCM',
        
        // Content Security
        'allowed_origins'   => [
            'https://aboud-store.com',
            'https://www.aboud-store.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    */
    'upload' => [
        'max_size'      => 5242880, // 5MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'path'          => '/public/uploads/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'default' => 'stripe',
        'gateways' => [
            'stripe' => [
                'public_key' => getenv('STRIPE_PUBLIC_KEY') ?: '',
                'secret_key' => getenv('STRIPE_SECRET_KEY') ?: '',
                'webhook'    => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
            ],
            'paypal' => [
                'client_id'  => getenv('PAYPAL_CLIENT_ID') ?: '',
                'secret'     => getenv('PAYPAL_SECRET') ?: '',
                'sandbox'    => (bool)(getenv('PAYPAL_SANDBOX') ?: true),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled'   => true,
        'level'     => 'warning', // debug, info, warning, error, critical
        'path'      => __DIR__ . '/../storage/logs/',
        'max_files' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'driver'  => 'file', // file, redis, memcached
        'path'    => __DIR__ . '/../storage/cache/',
        'prefix'  => 'aboud_',
        'ttl'     => 3600,
    ],
];
