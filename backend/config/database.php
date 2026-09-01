<?php
/**
 * Konfigurasi Database XAMPP
 * Membaca dari file .env di root directory
 */
date_default_timezone_set('Asia/Jakarta');

$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"");
        if (!defined($name)) {
            define($name, $value);
        }
    }
}

// Fallback jika tidak ada .env
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'school_website');

// URL aplikasi mengikuti lokasi folder di bawah document root. Dengan begitu
// clone/rename folder tidak lagi memerlukan perubahan manual pada source code.
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
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    $scheme = $isSecure ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    define('SITE_URL', $scheme . '://' . $host . APP_BASE_PATH);
}
if (!defined('APP_COOKIE_PATH')) {
    define('APP_COOKIE_PATH', APP_BASE_PATH === '' ? '/' : APP_BASE_PATH . '/');
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal. Silakan periksa konfigurasi .env Anda.');
}

// Pastikan tabel publik hasil pembaruan tersedia meskipun database lama belum di-import ulang.
require_once __DIR__ . '/../migrations/public_schema.php';
ensure_public_schema($pdo);
