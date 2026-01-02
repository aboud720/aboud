<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Database Configuration
 * ═══════════════════════════════════════════════════════════════════════════
 */

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    */
    'default' => 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'mysql' => [
            'driver'         => 'mysql',
            'host'           => getenv('DB_HOST') ?: 'localhost',
            'port'           => getenv('DB_PORT') ?: '3306',
            'database'       => getenv('DB_DATABASE') ?: 'aboud_store',
            'username'       => getenv('DB_USERNAME') ?: 'root',
            'password'       => getenv('DB_PASSWORD') ?: '',
            'unix_socket'    => getenv('DB_SOCKET') ?: '',
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => 'InnoDB',
            'options'        => extension_loaded('pdo_mysql') ? [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ] : [],
        ],

        'mysql_read' => [
            'driver'   => 'mysql',
            'host'     => getenv('DB_READ_HOST') ?: getenv('DB_HOST') ?: 'localhost',
            'port'     => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_DATABASE') ?: 'aboud_store',
            'username' => getenv('DB_READ_USERNAME') ?: getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_READ_PASSWORD') ?: getenv('DB_PASSWORD') ?: '',
            'charset'  => 'utf8mb4',
            'collation'=> 'utf8mb4_unicode_ci',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'table' => 'migrations',
        'path'  => __DIR__ . '/../database/migrations',
    ],
];
