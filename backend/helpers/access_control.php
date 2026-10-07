<?php

function school_admin_units(): array
{
    return ['daycare'=>'Daycare', 'tkit'=>'TKIT', 'sdit'=>'SDIT', 'smpit'=>'SMPIT'];
}

function unit_permission_labels(): array
{
    return ['dashboard'=>'Dashboard', 'content'=>'Konten', 'gallery'=>'Galeri', 'social'=>'Galeri Instagram',
        'enrollments'=>'Pendaftar SPMB', 'careers'=>'Karir & Lamaran', 'settings'=>'Identitas, Kontak & Hero'];
}

function admin_add_columns(PDO $pdo, string $table, array $columns): void
{
    $check = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $check->execute([$table]);
    $existing = $check->fetchAll(PDO::FETCH_COLUMN);
    foreach ($columns as $name=>$definition) {
        if (!in_array($name, $existing, true)) $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$name` $definition");
    }
}

function portal_access_schema(PDO $pdo): void
{
    admin_add_columns($pdo, 'portal_users', ['session_version'=>'INT NOT NULL DEFAULT 1', 'managed_at'=>'DATETIME NULL']);
}

function unit_access_schema(PDO $pdo): void
{
    static $ready = [];
    if (isset($ready[spl_object_id($pdo)])) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS unit_users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(60) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL,
        role ENUM('superadmin','unit_admin') NOT NULL DEFAULT 'unit_admin', unit_slug VARCHAR(24) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
        ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    admin_add_columns($pdo, 'unit_users', ['name'=>'VARCHAR(120) NULL', 'role_key'=>'VARCHAR(60) NULL',
        'last_login_at'=>'DATETIME NULL', 'session_version'=>'INT NOT NULL DEFAULT 1', 'managed_at'=>'DATETIME NULL']);
    $pdo->exec("CREATE TABLE IF NOT EXISTS unit_roles (role_key VARCHAR(60) PRIMARY KEY,
        name VARCHAR(100) NOT NULL, permissions TEXT NOT NULL, is_system TINYINT(1) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
        ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $seed = $pdo->prepare('INSERT IGNORE INTO unit_roles (role_key,name,permissions,is_system) VALUES (?,?,?,1)');
    $seed->execute(['unit_admin', 'Admin Unit', json_encode(array_keys(unit_permission_labels()))]);
    $seed->execute(['unit_editor', 'Editor / Humas Unit', json_encode(['dashboard','content','gallery','social','careers'])]);
    $seed->execute(['unit_admissions', 'Petugas SPMB Unit', json_encode(['dashboard','enrollments'])]);
    $ready[spl_object_id($pdo)] = true;
}

function unit_permissions(PDO $pdo, array $user): array
{
    if ($user['role'] === 'superadmin') return [...array_keys(unit_permission_labels()), 'users'];
    $query = $pdo->prepare('SELECT permissions FROM unit_roles WHERE role_key=?');
    $query->execute([$user['role_key'] ?: 'unit_admin']);
    $permissions = json_decode((string) $query->fetchColumn(), true);
    return is_array($permissions) ? array_values(array_intersect(array_keys(unit_permission_labels()), $permissions)) : [];
}

function unit_action_permission(string $action): ?string
{
    return match ($action) {
        'save_content','delete_content' => 'content',
        'save_album','delete_album','save_photo','delete_photo' => 'gallery',
        'save_social','save_social_bulk','toggle_social','delete_social' => 'social',
        'save_job','archive_job','update_application' => 'careers',
        'update_spmb_status' => 'enrollments',
        'save_settings','save_hero','reset_hero' => 'settings',
        'save_user','replace_foundation_admin' => 'users',
        default => null,
    };
}

/** Both the legacy CMS form and central portal use the same validation. */
function admin_save_unit_user(PDO $pdo, array $input): int
{
    $pdo->beginTransaction();
    try {
        $pdo->query("SELECT id FROM unit_users WHERE role='superadmin' AND is_active=1 FOR UPDATE")->fetchAll();
        $id=admin_save_unit_user_locked($pdo,$input);
        $pdo->commit();
        return $id;
    } catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
}

function admin_save_unit_user_locked(PDO $pdo, array $input): int
{
    $id = (int) ($input['id'] ?? 0);
    $username = strtolower(trim((string) ($input['username'] ?? '')));
    $name = trim((string) ($input['name'] ?? $username));
    $roleKey = (string) ($input['role_key'] ?? $input['role'] ?? '');
    $unit = (string) ($input['user_unit'] ?? '');
    $password = (string) ($input['password'] ?? '');
    if (!preg_match('/^[a-z0-9._-]{3,30}$/', $username) || $name === '' || mb_strlen($name) > 120) throw new RuntimeException('Nama atau username tidak valid.');
    $query = $pdo->prepare('SELECT * FROM unit_users WHERE id=?'); $query->execute([$id]); $old = $query->fetch();
    if ($id && !$old) throw new RuntimeException('Akun unit tidak ditemukan.');
    if ((!$id || $password !== '') && strlen($password) < 8) throw new RuntimeException('Password minimal 8 karakter.');
    if ($roleKey !== 'superadmin') {
        $query = $pdo->prepare('SELECT role_key FROM unit_roles WHERE role_key=? FOR UPDATE'); $query->execute([$roleKey]);
        if (!$query->fetchColumn() || !isset(school_admin_units()[$unit])) throw new RuntimeException('Role atau unit tidak valid.');
    }
    $active = (int)!empty($input['is_active'] ?? 1);
    $role = $roleKey === 'superadmin' ? 'superadmin' : 'unit_admin';
    if ($old && $old['role'] === 'superadmin' && (int)$old['is_active'] && ($role !== 'superadmin' || !$active)
        && (int)$pdo->query("SELECT COUNT(*) FROM unit_users WHERE role='superadmin' AND is_active=1")->fetchColumn() <= 1) {
        throw new RuntimeException('Superadmin unit terakhir tidak boleh dinonaktifkan atau diturunkan rolenya.');
    }
    $values = [$name,$username,$role,$roleKey === 'superadmin' ? null : $roleKey,$role === 'superadmin' ? null : $unit];
    if ($id) {
        $sql = 'UPDATE unit_users SET name=?,username=?,role=?,role_key=?,unit_slug=?,is_active=?,managed_at=NOW(),session_version=session_version+1';
        $values[] = $active;
        if ($password !== '') { $sql .= ',password_hash=?'; $values[] = password_hash($password,PASSWORD_DEFAULT); }
        $pdo->prepare($sql.' WHERE id=?')->execute([...$values,$id]);
    } else {
        $pdo->prepare('INSERT INTO unit_users (name,username,role,role_key,unit_slug,password_hash,is_active,managed_at) VALUES (?,?,?,?,?,?,?,NOW())')
            ->execute([...$values,password_hash($password,PASSWORD_DEFAULT),$active]);
        $id = (int) $pdo->lastInsertId();
    }
    return $id;
}
