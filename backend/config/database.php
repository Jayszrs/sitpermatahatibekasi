<?php

require_once __DIR__ . '/environment.php';

date_default_timezone_set(app_env('APP_TIMEZONE', 'Asia/Jakarta'));

function app_database_unavailable(string $reason): never
{
    error_log('[database] ' . $reason);
    if (PHP_SAPI === 'cli') throw new RuntimeException('Database configuration or connection is unavailable.');

    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Retry-After: 30');
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Layanan sementara tidak tersedia</title><body><main><h1>Layanan sementara tidak tersedia</h1><p>Silakan coba kembali beberapa saat lagi.</p></main></body></html>';
    exit;
}

$railwayDatabaseConfigured = app_env('MYSQLHOST') !== null;
$database = [
    'host' => app_env($railwayDatabaseConfigured ? 'MYSQLHOST' : 'DB_HOST'),
    'port' => app_env($railwayDatabaseConfigured ? 'MYSQLPORT' : 'DB_PORT', '3306'),
    'user' => app_env($railwayDatabaseConfigured ? 'MYSQLUSER' : 'DB_USER'),
    'pass' => app_env($railwayDatabaseConfigured ? 'MYSQLPASSWORD' : 'DB_PASS', ''),
    'name' => app_env($railwayDatabaseConfigured ? 'MYSQLDATABASE' : 'DB_NAME'),
];

foreach (['host', 'port', 'user', 'name'] as $required) {
    if ($database[$required] === null) app_database_unavailable('Missing required database setting: ' . $required);
}
if (!ctype_digit((string) $database['port']) || (int) $database['port'] < 1 || (int) $database['port'] > 65535) {
    app_database_unavailable('Invalid database port.');
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $database['name'])) app_database_unavailable('Invalid database name.');

// Defined with literal names (instead of a dynamic-key loop) so static analysis
// tools such as intelephense can actually see these constants get declared -
// a computed define($variable, ...) call is invisible to them and was showing
// up as "Undefined constant" everywhere DB_NAME/DB_HOST/etc. are used.
if (!defined('DB_HOST')) define('DB_HOST', (string) $database['host']);
if (!defined('DB_PORT')) define('DB_PORT', (string) $database['port']);
if (!defined('DB_USER')) define('DB_USER', (string) $database['user']);
if (!defined('DB_PASS')) define('DB_PASS', (string) $database['pass']);
if (!defined('DB_NAME')) define('DB_NAME', (string) $database['name']);

if (!defined('APP_BASE_PATH')) {
    $projectRoot = realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2);
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? (realpath((string) $_SERVER['DOCUMENT_ROOT']) ?: '') : '';
    $basePath = '/' . basename($projectRoot);
    if ($documentRoot !== '') {
        $normalizedProject = str_replace('\\', '/', $projectRoot);
        $normalizedDocument = rtrim(str_replace('\\', '/', $documentRoot), '/');
        if (stripos($normalizedProject, $normalizedDocument) === 0) {
            $relativeProject = trim(substr($normalizedProject, strlen($normalizedDocument)), '/');
            $basePath = $relativeProject === '' ? '' : '/' . $relativeProject;
        }
    }
    define('APP_BASE_PATH', rtrim($basePath, '/'));
}
if (!defined('SITE_URL')) {
    $railwayPublicDomain = trim((string) app_env('RAILWAY_PUBLIC_DOMAIN', ''));
    if ($railwayPublicDomain !== '' && preg_match('/^[a-zA-Z0-9.-]+(?::[0-9]+)?$/', $railwayPublicDomain)) {
        define('SITE_URL', 'https://' . $railwayPublicDomain . APP_BASE_PATH);
    } else {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $scheme = $isSecure ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        define('SITE_URL', $scheme . '://' . $host . APP_BASE_PATH);
    }
}
if (!defined('APP_COOKIE_PATH')) define('APP_COOKIE_PATH', APP_BASE_PATH === '' ? '/' : APP_BASE_PATH . '/');

if (!extension_loaded('pdo_mysql')) app_database_unavailable('The pdo_mysql extension is not loaded.');

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, (int) DB_PORT, DB_NAME),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $exception) {
    app_database_unavailable('Connection failed (' . get_class($exception) . ').');
}

require_once __DIR__ . '/../migrations/public_schema.php';
try {
    ensure_public_schema($pdo);
} catch (Throwable $exception) {
    app_database_unavailable('Schema bootstrap failed (' . get_class($exception) . ').');
}
