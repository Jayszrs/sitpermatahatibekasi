<?php

date_default_timezone_set('Asia/Jakarta');
$unit_config = require __DIR__ . '/config.php';

function unit_base_url(): string {
    $root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $folder = realpath(__DIR__) ?: __DIR__;
    $relative = $root !== '' && str_starts_with(str_replace('\\', '/', $folder), str_replace('\\', '/', $root))
        ? substr(str_replace('\\', '/', $folder), strlen(rtrim(str_replace('\\', '/', $root), '/')))
        : '/school-website/' . basename(__DIR__);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim($relative, '/');
}

if (!defined('UNIT_BASE_URL')) define('UNIT_BASE_URL', unit_base_url());
if (!defined('UNIT_SLUG')) define('UNIT_SLUG', $unit_config['slug']);

function unit_e(?string $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function unit_url(string $path = 'index.php'): string { return UNIT_BASE_URL . '/' . ltrim($path, '/'); }
function unit_asset(string $path): string { return unit_url($path) . '?v=' . rawurlencode((string) (@filemtime(__DIR__ . '/' . ltrim($path, '/')) ?: 1)); }
function unit_media(?string $path, string $fallback = 'assets/images/hero.jpeg'): string {
    $path = trim((string) $path);
    if ($path === '') $path = $fallback;
    if (preg_match('~^https?://~i', $path)) return $path;
    return unit_url($path);
}

function unit_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = 'localhost'; $user = 'root'; $pass = ''; $name = 'school_units_portal';
    $server = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    unit_ensure_schema($pdo);
    unit_seed_defaults($pdo);
    return $pdo;
}

function unit_ensure_schema(PDO $pdo): void {
    $queries = [
        "CREATE TABLE IF NOT EXISTS unit_users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(60) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, role ENUM('superadmin','unit_admin') NOT NULL DEFAULT 'unit_admin', unit_slug VARCHAR(24) NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS unit_settings (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, unit_slug VARCHAR(24) NOT NULL, setting_key VARCHAR(80) NOT NULL, setting_value TEXT NOT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY unit_setting (unit_slug, setting_key))",
        "CREATE TABLE IF NOT EXISTS unit_content (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, unit_slug VARCHAR(24) NOT NULL, content_type VARCHAR(24) NOT NULL, title VARCHAR(180) NOT NULL, summary TEXT NULL, body MEDIUMTEXT NULL, image VARCHAR(255) NULL, meta VARCHAR(255) NULL, published_at DATE NULL, sort_order INT NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX public_content (unit_slug, content_type, is_active, sort_order, id))",
        "CREATE TABLE IF NOT EXISTS unit_gallery_albums (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, unit_slug VARCHAR(24) NOT NULL, title VARCHAR(160) NOT NULL, description TEXT NULL, cover_image VARCHAR(255) NULL, sort_order INT NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX public_albums (unit_slug, is_active, sort_order, id))",
        "CREATE TABLE IF NOT EXISTS unit_gallery_photos (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, album_id INT UNSIGNED NOT NULL, title VARCHAR(160) NULL, description TEXT NULL, image VARCHAR(255) NOT NULL, sort_order INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CONSTRAINT unit_gallery_photo_album FOREIGN KEY (album_id) REFERENCES unit_gallery_albums(id) ON DELETE CASCADE)",
        "CREATE TABLE IF NOT EXISTS unit_enrollments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, unit_slug VARCHAR(24) NOT NULL, parent_name VARCHAR(160) NOT NULL, child_name VARCHAR(160) NOT NULL, phone VARCHAR(40) NOT NULL, email VARCHAR(160) NULL, message TEXT NULL, status VARCHAR(32) NOT NULL DEFAULT 'baru', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX unit_enrollment (unit_slug, status, created_at))",
    ];
    foreach ($queries as $query) $pdo->exec($query);
}

function unit_seed_defaults(PDO $pdo): void {
    global $unit_config;
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM unit_users')->fetchColumn();
    if ($userCount === 0) {
        $users = [
            ['superadmin', 'SuperUnit#2026', 'superadmin', null],
            ['daycare-admin', 'Daycare#2026', 'unit_admin', 'daycare'],
            ['tkit-admin', 'TKIT#2026', 'unit_admin', 'tkit'],
            ['sdit-admin', 'SDIT#2026', 'unit_admin', 'sdit'],
            ['smpit-admin', 'SMPIT#2026', 'unit_admin', 'smpit'],
        ];
        $statement = $pdo->prepare('INSERT INTO unit_users (username,password_hash,role,unit_slug) VALUES (?,?,?,?)');
        foreach ($users as [$username, $password, $role, $slug]) $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $slug]);
    }
    $settings = array_merge(['name' => $unit_config['name'], 'tagline' => $unit_config['tagline'], 'description' => $unit_config['description']], $unit_config['contact']);
    $setting = $pdo->prepare('INSERT IGNORE INTO unit_settings (unit_slug,setting_key,setting_value) VALUES (?,?,?)');
    foreach ($settings as $key => $value) $setting->execute([UNIT_SLUG, $key, $value]);
    $check = $pdo->prepare('SELECT COUNT(*) FROM unit_content WHERE unit_slug=?'); $check->execute([UNIT_SLUG]);
    if ((int) $check->fetchColumn() === 0) {
        $insert = $pdo->prepare('INSERT INTO unit_content (unit_slug,content_type,title,summary,image,meta,published_at,sort_order) VALUES (?,?,?,?,?,?,?,?)');
        foreach (['programs' => 'program', 'activities' => 'activity', 'achievements' => 'achievement', 'brochures' => 'brochure'] as $source => $type) foreach ($unit_config[$source] as $index => $item) {
            $year = (string) ($item['year'] ?? date('Y'));
            $insert->execute([UNIT_SLUG, $type, $item['title'], $item['summary'] ?? '', $item['image'] ?? ($item['file'] ?? ''), $item['level'] ?? '', $year . '-01-01', $index]);
        }
        $album = $pdo->prepare('INSERT INTO unit_gallery_albums (unit_slug,title,description,cover_image,sort_order) VALUES (?,?,?,?,0)');
        $album->execute([UNIT_SLUG, 'Kegiatan ' . $unit_config['short_name'], 'Potret kegiatan belajar, bermain, dan bertumbuh bersama.', $unit_config['gallery'][0]['image']]);
        $albumId = (int) $pdo->lastInsertId(); $photo = $pdo->prepare('INSERT INTO unit_gallery_photos (album_id,title,description,image,sort_order) VALUES (?,?,?,?,?)');
        foreach ($unit_config['gallery'] as $index => $item) $photo->execute([$albumId, $item['title'], $item['description'], $item['image'], $index]);
    }
}

function unit_settings(PDO $pdo): array { global $unit_config; $result = ['name' => $unit_config['name'], 'tagline' => $unit_config['tagline'], 'description' => $unit_config['description']] + $unit_config['contact']; $stmt = $pdo->prepare('SELECT setting_key,setting_value FROM unit_settings WHERE unit_slug=?'); $stmt->execute([UNIT_SLUG]); foreach ($stmt as $row) $result[$row['setting_key']] = $row['setting_value']; return $result; }
function unit_content(PDO $pdo, string $type, int $limit = 0): array { $sql='SELECT * FROM unit_content WHERE unit_slug=? AND content_type=? AND is_active=1 ORDER BY sort_order,id DESC'; if($limit) $sql.=' LIMIT '.(int)$limit; $stmt=$pdo->prepare($sql);$stmt->execute([UNIT_SLUG,$type]);return $stmt->fetchAll(); }
function unit_albums(PDO $pdo): array { $stmt=$pdo->prepare('SELECT a.*,COUNT(p.id) photo_count FROM unit_gallery_albums a LEFT JOIN unit_gallery_photos p ON p.album_id=a.id WHERE a.unit_slug=? AND a.is_active=1 GROUP BY a.id ORDER BY a.sort_order,a.id DESC');$stmt->execute([UNIT_SLUG]);return $stmt->fetchAll(); }
function unit_album_photos(PDO $pdo, int $albumId): array {$stmt=$pdo->prepare('SELECT p.* FROM unit_gallery_photos p JOIN unit_gallery_albums a ON a.id=p.album_id WHERE p.album_id=? AND a.unit_slug=? ORDER BY p.sort_order,p.id DESC');$stmt->execute([$albumId,UNIT_SLUG]);return $stmt->fetchAll();}
function unit_csrf(): string { if(session_status()!==PHP_SESSION_ACTIVE){session_name('unit_public_'.UNIT_SLUG);session_start();} if(empty($_SESSION['unit_csrf']))$_SESSION['unit_csrf']=bin2hex(random_bytes(24));return $_SESSION['unit_csrf']; }
function unit_verify_csrf(string $token): bool { return !empty($_SESSION['unit_csrf']) && hash_equals($_SESSION['unit_csrf'], $token); }
