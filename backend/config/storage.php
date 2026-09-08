<?php

require_once __DIR__ . '/environment.php';

function app_upload_root(): string
{
    $configured = app_env('APP_UPLOAD_ROOT');
    return rtrim($configured ?: dirname(__DIR__, 2) . '/frontend/assets/uploads', '/\\');
}

function app_storage_path(string $relative): string
{
    $relative = str_replace('\\', '/', trim($relative, '/\\'));
    if ($relative === '' || str_contains($relative, '..')) {
        throw new InvalidArgumentException('Path penyimpanan tidak valid.');
    }
    return app_upload_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function app_ensure_storage_directory(string $relative): string
{
    $directory = app_storage_path($relative);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder upload tidak dapat dibuat.');
    }
    return $directory;
}

function app_public_media_url(string $relative): string
{
    $relative = str_replace('\\', '/', trim($relative, '/\\'));
    $basePath = defined('APP_BASE_PATH') ? (string) APP_BASE_PATH : '';
    if ($basePath === '' && defined('UNIT_BASE_URL')) {
        $unitPath = rtrim((string) parse_url(UNIT_BASE_URL, PHP_URL_PATH), '/');
        $basePath = rtrim(str_replace('\\', '/', dirname($unitPath)), '/.');
    }
    return ($basePath === '' ? '' : '/' . trim($basePath, '/')) . '/media/' . $relative;
}

function app_private_cv_path(string $storedValue): ?string
{
    $path = (string) (parse_url($storedValue, PHP_URL_PATH) ?: $storedValue);
    $filename = basename(str_replace('\\', '/', $path));
    if ($filename === '' || !preg_match('/^cv-[a-zA-Z0-9.-]+$/', $filename)) return null;
    if (str_contains(str_replace('\\', '/', $path), '/frontend/assets/uploads/careers/')) {
        return app_storage_path('careers/' . $filename);
    }
    return app_storage_path('private/careers/' . $filename);
}

function app_store_public_image(array $file, string $relativeDirectory, string $prefix = 'media'): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 6 * 1024 * 1024) {
        throw new RuntimeException('Upload gambar gagal atau ukurannya melebihi 6 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Gambar harus JPG, PNG, atau WEBP.');

    $filename = $prefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extensions[$mime];
    $relativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/');
    $directory = app_ensure_storage_directory($relativeDirectory);
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('File tidak bisa disimpan.');
    }
    return app_public_media_url($relativeDirectory . '/' . $filename);
}
