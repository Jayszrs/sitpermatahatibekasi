<?php
/**
 * One-time recovery for the unit superadmin and foundation portal admin.
 * Run inside the deployed service with --apply after setting both passwords
 * as private environment variables. Without --apply this only checks setup.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/backend/config/environment.php';

$passwords = [
    'admin yayasan' => app_env('PORTAL_ADMIN_PASSWORD'),
    'superadmin unit' => app_env('UNIT_SUPERADMIN_PASSWORD'),
];
foreach ($passwords as $label => $password) {
    if ($password === null || strlen($password) < 12) {
        fwrite(STDERR, "Password {$label} belum diatur atau kurang dari 12 karakter.\n");
        exit(1);
    }
}
if ($passwords['admin yayasan'] === $passwords['superadmin unit']
    || in_array($passwords['admin yayasan'], ['AdminPHB#2026', 'AdminTBZ#2026'], true)
    || $passwords['superadmin unit'] === 'SuperUnit#2026') {
    fwrite(STDERR, "Gunakan password baru yang berbeda untuk kedua akun.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/backend/config/database.php';
require_once dirname(__DIR__) . '/backend/helpers/functions.php';
require_once dirname(__DIR__) . '/backend/auth.php';
require_once dirname(__DIR__) . '/daycare/bootstrap.php';
$unitPdo = unit_database_connection();
unit_ensure_schema($unitPdo);

if (in_array('admin', admin_retired_usernames($pdo, 'portal'), true)
    || in_array('superadmin', admin_retired_usernames($unitPdo, 'unit'), true)) {
    fwrite(STDERR, "Akun lama sudah diganti. Kelola akun penggantinya; jangan hidupkan kembali login lama.\n");
    exit(1);
}

if (!in_array('--apply', $argv, true)) {
    echo "Konfigurasi dan koneksi database siap. Jalankan lagi dengan --apply untuk mereset dua akun.\n";
    exit(0);
}

$mainHash = password_hash($passwords['admin yayasan'], PASSWORD_DEFAULT);
$unitHash = password_hash($passwords['superadmin unit'], PASSWORD_DEFAULT);

$pdo->beginTransaction();
try {
    $findMain = $pdo->prepare('SELECT id FROM portal_users WHERE username=? LIMIT 1');
    $findMain->execute(['admin']);
    $mainId = $findMain->fetchColumn();
    if ($mainId) {
        $pdo->prepare("UPDATE portal_users SET password=?,role='admin',is_active=1 WHERE id=?")
            ->execute([$mainHash, $mainId]);
    } else {
        $pdo->prepare("INSERT INTO portal_users (name,username,password,role,is_active) VALUES ('Administrator','admin',?,'admin',1)")
            ->execute([$mainHash]);
    }
    $pdo->commit();
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}

$unitPdo->beginTransaction();
try {
    $findUnit = $unitPdo->prepare('SELECT id FROM unit_users WHERE username=? LIMIT 1');
    $findUnit->execute(['superadmin']);
    $unitId = $findUnit->fetchColumn();
    if ($unitId) {
        $unitPdo->prepare("UPDATE unit_users SET password_hash=?,role='superadmin',unit_slug=NULL,is_active=1 WHERE id=?")
            ->execute([$unitHash, $unitId]);
    } else {
        $unitPdo->prepare("INSERT INTO unit_users (username,password_hash,role,unit_slug,is_active) VALUES ('superadmin',?,'superadmin',NULL,1)")
            ->execute([$unitHash]);
    }
    $unitPdo->commit();
} catch (Throwable $error) {
    $unitPdo->rollBack();
    throw $error;
}

echo "Password admin yayasan dan superadmin unit diperbarui. Coba login pada deployment yang memakai database ini.\n";
