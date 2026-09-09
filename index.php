<?php
/**
 * Router Sederhana (Entry Point)
 * Mengarahkan request ke halaman di dalam frontend/pages/
 */

// Health check tidak menjalankan migrasi dan tidak membuka detail koneksi.
$incomingPath = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
if ($incomingPath === 'health' || str_ends_with($incomingPath, '/health')) {
    require __DIR__ . '/backend/health.php';
    exit;
}

// Load konfigurasi backend dan koneksi database
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/helpers/functions.php';

// Tentukan halaman dari URL (default ke beranda)
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = $_SERVER['SCRIPT_NAME'];

// Dapatkan path relatif terhadap script
$path = str_replace(dirname($script_name), '', $request_uri);
$path = trim($path, '/');

// Jika kosong atau index.php, arahkan ke beranda
if (empty($path) || $path === 'index.php') {
    $page = 'index.php';
} else {
    $page = $path;
    // Tambahkan ekstensi .php jika belum ada
    if (strpos($page, '.php') === false) {
        $page .= '.php';
    }
}

// Path ke file halaman di frontend
$page_path = __DIR__ . '/frontend/pages/' . $page;

// Global variabel untuk menu aktif
$current_page = $page;

if (file_exists($page_path)) {
    // Jalankan halaman
    require_once $page_path;
} else {
    // 404 Not Found
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
    echo "<p>Halaman yang Anda cari tidak ditemukan.</p>";
    echo "<a href='" . SITE_URL . "/index.php'>Kembali ke Beranda</a>";
}
