<?php

/** Deleted bootstrap usernames must not be recreated on the next request. */
function admin_retired_usernames(PDO $pdo, string $scope): array
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS retired_admin_accounts (
        scope VARCHAR(20) NOT NULL,
        username VARCHAR(80) NOT NULL,
        retired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (scope, username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $query = $pdo->prepare('SELECT username FROM retired_admin_accounts WHERE scope=?');
    $query->execute([$scope]);
    return $query->fetchAll(PDO::FETCH_COLUMN);
}

function admin_retire_username(PDO $pdo, string $scope, string $username): void
{
    $pdo->prepare('INSERT IGNORE INTO retired_admin_accounts (scope,username) VALUES (?,?)')
        ->execute([$scope, $username]);
}

/** Validate both targets before any account write. Identifiers are fixed here. */
function admin_replacement_plan(PDO $mainPdo, PDO $unitPdo, string $username, string $password): array
{
    if (!preg_match('/^[a-z0-9._-]{3,30}$/', $username)
        || in_array($username, ['admin', 'superadmin', 'humas', 'kasir'], true)) {
        throw new RuntimeException('Pilih username baru, 3-30 huruf kecil, angka, titik, strip, atau garis bawah.');
    }
    if (strlen($password) < 8) throw new RuntimeException('Password minimal 8 karakter.');
    $accounts = [
        ['pdo' => $mainPdo, 'scope' => 'portal', 'table' => 'portal_users', 'hash' => 'password', 'role' => 'admin', 'legacy' => 'admin'],
        ['pdo' => $unitPdo, 'scope' => 'unit', 'table' => 'unit_users', 'hash' => 'password_hash', 'role' => 'superadmin', 'legacy' => 'superadmin'],
    ];

    // Inspect both databases before changing either. A matching existing replacement
    // is accepted on reruns, but an unrelated account is never overwritten.
    foreach ($accounts as $account) {
        $query = $account['pdo']->prepare("SELECT * FROM {$account['table']} WHERE username=?");
        $query->execute([$username]);
        $existing = $query->fetch();
        if ($existing && (!password_verify($password, $existing[$account['hash']])
            || $existing['role'] !== $account['role'] || !(int) $existing['is_active']
            || ($account['scope'] === 'unit' && $existing['unit_slug'] !== null))) {
            throw new RuntimeException("Username sudah digunakan akun lain di {$account['scope']}; pilih username lain.");
        }
    }

    return $accounts;
}

/** Called only after CLI authorization or authenticated superadmin + CSRF + password. */
function admin_replace_accounts(PDO $mainPdo, PDO $unitPdo, string $username, string $password): void
{
    $accounts = admin_replacement_plan($mainPdo, $unitPdo, $username, $password);
    // Commit both replacement accounts first. If the second database fails, legacy
    // logins still exist and the command can be rerun without locking anyone out.
    foreach ($accounts as $account) {
        $connection = $account['pdo'];
        admin_retired_usernames($connection, $account['scope']);
        $query = $connection->prepare("SELECT id FROM {$account['table']} WHERE username=?");
        $query->execute([$username]);
        if (!$query->fetchColumn()) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($account['scope'] === 'portal') {
                $connection->prepare("INSERT INTO portal_users (name,username,password,role,is_active) VALUES ('Administrator Yayasan',?,?,'admin',1)")
                    ->execute([$username, $hash]);
            } else {
                $connection->prepare("INSERT INTO unit_users (username,password_hash,role,unit_slug,is_active) VALUES (?,?,'superadmin',NULL,1)")
                    ->execute([$username, $hash]);
            }
        }
    }

    foreach ($accounts as $account) {
        $query = $account['pdo']->prepare("SELECT * FROM {$account['table']} WHERE username=? AND is_active=1");
        $query->execute([$username]);
        $user = $query->fetch();
        if (!$user || !password_verify($password, $user[$account['hash']]) || $user['role'] !== $account['role']) {
            throw new RuntimeException('Verifikasi akun baru gagal; akun lama belum dihapus.');
        }
    }

    foreach ($accounts as $account) {
        $connection = $account['pdo'];
        $connection->beginTransaction();
        try {
            admin_retire_username($connection, $account['scope'], $account['legacy']);
            $connection->prepare("DELETE FROM {$account['table']} WHERE username=?")
                ->execute([$account['legacy']]);
            $connection->commit();
        } catch (Throwable $error) {
            if ($connection->inTransaction()) $connection->rollBack();
            throw $error;
        }
    }
}
