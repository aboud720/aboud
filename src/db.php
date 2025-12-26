<?php
/**
 * Database connection (PDO) using Singleton pattern.
 *
 * SECURITY NOTE:
 * - Do NOT hardcode credentials here.
 * - Set them via environment variables (or a local .env file you do NOT commit).
 */
declare(strict_types=1);

// Polyfills for PHP < 8
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}

/**
 * Get environment variable with optional default.
 */
function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    $value = trim($value);
    return $value === '' ? $default : $value;
}

/**
 * Optional lightweight .env loader (no dependencies).
 * Loads key=value pairs into getenv()/$_ENV for current process.
 *
 * @param string $path Absolute or relative path to .env file.
 */
function loadDotEnv(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));

        // Remove surrounding quotes: "value" or 'value'
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }

        if ($key === '' || getenv($key) !== false) {
            // Don't override existing env vars
            continue;
        }

        putenv($key . '=' . $val);
        $_ENV[$key] = $val;
    }
}

/**
 * Returns a shared PDO connection (Singleton).
 *
 * Required env vars:
 * - DB_HOST, DB_NAME, DB_USER, DB_PASS
 *
 * Optional:
 * - DB_CHARSET (default: utf8mb4)
 * - DB_DEBUG (true/false, default: false) -> show detailed error (DEV only)
 */
function getDBConnection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // If you keep a local .env, this makes local setup easy.
    // Safe because .env is ignored by git (see .gitignore).
    loadDotEnv(__DIR__ . '/../.env');

    $host = env('DB_HOST', 'localhost');
    $name = env('DB_NAME');
    $user = env('DB_USER');
    $pass = env('DB_PASS', '');
    $charset = env('DB_CHARSET', 'utf8mb4');
    $debug = strtolower((string)env('DB_DEBUG', 'false')) === 'true';

    if ($name === null || $user === null) {
        throw new RuntimeException('Missing required database environment variables: DB_NAME and DB_USER.');
    }

    try {
        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ];

        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Avoid leaking credentials/connection info in production responses.
        if ($debug) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
        throw new RuntimeException('Database connection failed.', 0, $e);
    }
}

