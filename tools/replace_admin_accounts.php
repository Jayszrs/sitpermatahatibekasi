<?php
/** CLI only: replace the two legacy logins with one username in both portals. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/backend/config/environment.php';

$username = strtolower(trim((string) app_env('NEW_ADMIN_USERNAME', 'admin.yayasan')));
$password = app_env('NEW_ADMIN_PASSWORD');
if (!preg_match('/^[a-z0-9._-]{3,30}$/', $username)
    || in_array($username, ['admin', 'superadmin', 'humas', 'kasir'], true)) {
    fwrite(STDERR, "Pilih username baru (3-30 karakter) selain akun bawaan.\n");
    exit(1);
}
if ($password === null || strlen($password) < 8) {
    fwrite(STDERR, "Atur NEW_ADMIN_PASSWORD, minimal 8 karakter.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/backend/config/database.php';
require_once dirname(__DIR__) . '/backend/config/unit_database.php';
require_once dirname(__DIR__) . '/backend/helpers/admin_accounts.php';
$unitPdo = unit_database_connection();

admin_replacement_plan($pdo, $unitPdo, $username, $password);
if (!in_array('--apply', $argv, true)) {
    echo "Koneksi siap. Akan membuat {$username} (admin yayasan + superadmin 4 unit), lalu menghapus login admin dan superadmin.\n";
    echo "Jalankan dengan --apply untuk menerapkan. Belum ada akun yang diubah.\n";
    exit;
}
admin_replace_accounts($pdo, $unitPdo, $username, $password);
echo "Akun {$username} aktif di portal yayasan dan empat CMS unit. Login admin dan superadmin dihapus.\n";
