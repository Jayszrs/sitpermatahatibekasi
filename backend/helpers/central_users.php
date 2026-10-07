<?php
require_once __DIR__ . '/access_control.php';
require_once __DIR__ . '/admin_accounts.php';

function central_save_portal_user(PDO $pdo, array $input, array $actor): int
{
    $id = (int)($input['id'] ?? 0);
    $name = trim((string)($input['name'] ?? ''));
    $username = strtolower(trim((string)($input['username'] ?? '')));
    $role = (string)($input['role'] ?? '');
    $password = (string)($input['password'] ?? '');
    if ($name === '' || mb_strlen($name)>120 || !preg_match('/^[a-z0-9._-]{3,30}$/',$username)
        || !in_array($role,['admin','humas','kasir'],true)) throw new RuntimeException('Nama, username, atau role tidak valid.');
    if ((!$id || $password !== '') && strlen($password)<8) throw new RuntimeException('Password minimal 8 karakter.');
    $active = (int)!empty($input['is_active'] ?? 1);
    if ($id === (int)$actor['id'] && ($role !== 'admin' || !$active)) throw new RuntimeException('Akun sendiri harus tetap aktif sebagai superadmin yayasan.');
    $pdo->beginTransaction();
    try {
        $admins=$pdo->query("SELECT id FROM portal_users WHERE role='admin' AND is_active=1 FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
        $query=$pdo->prepare('SELECT * FROM portal_users WHERE id=? FOR UPDATE');$query->execute([$id]);$old=$query->fetch();
        if ($id && !$old) throw new RuntimeException('Akun yayasan tidak ditemukan.');
        if ($old && $old['role']==='admin' && $old['is_active'] && ($role!=='admin' || !$active) && count($admins)<=1) throw new RuntimeException('Superadmin yayasan terakhir tidak boleh dinonaktifkan.');
        if ($id) {
            $sql='UPDATE portal_users SET name=?,username=?,role=?,is_active=?,managed_at=NOW(),session_version=session_version+1';
            $values=[$name,$username,$role,$active];
            if ($password!=='') {$sql.=',password=?';$values[]=password_hash($password,PASSWORD_DEFAULT);}
            $pdo->prepare($sql.' WHERE id=?')->execute([...$values,$id]);
        } else {
            $pdo->prepare('INSERT INTO portal_users (name,username,password,role,is_active,managed_at) VALUES (?,?,?,?,?,NOW())')->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT),$role,$active]);
            $id=(int)$pdo->lastInsertId();
        }
        $pdo->commit();
        if ($id===(int)$actor['id']) {
            $q=$pdo->prepare('SELECT id,name,username,role,session_version FROM portal_users WHERE id=?');$q->execute([$id]);
            $_SESSION['portal_user']=$q->fetch();$_SESSION['portal_user']['id']=$id;
            session_regenerate_id(true);
        }
        return $id;
    } catch (Throwable $error) { if($pdo->inTransaction())$pdo->rollBack();throw $error; }
}

function central_save_role(PDO $pdo, array $input): string
{
    $key=trim((string)($input['role_key']??''));$name=trim((string)($input['name']??''));
    if (!preg_match('/^[a-z][a-z0-9_-]{2,59}$/',$key) || $key==='superadmin' || $name==='' || mb_strlen($name)>100) throw new RuntimeException('Kode role (3-60 karakter) dan nama wajib valid.');
    $raw=$input['permissions']??[];
    if (!is_array($raw) || array_diff($raw,array_keys(unit_permission_labels()))) throw new RuntimeException('Hak akses role tidak valid.');
    $permissions=array_values(array_unique(['dashboard',...$raw]));
    $pdo->beginTransaction();
    try {
        $query=$pdo->prepare('SELECT * FROM unit_roles WHERE role_key=? FOR UPDATE');$query->execute([$key]);$old=$query->fetch();
        if ($old && $old['is_system']) throw new RuntimeException('Role bawaan tidak dapat diubah; buat role khusus.');
        if (empty($input['editing']) && $old) throw new RuntimeException('Kode role sudah dipakai.');
        if (!empty($input['editing']) && !$old) throw new RuntimeException('Role tidak ditemukan.');
        $pdo->prepare('INSERT INTO unit_roles (role_key,name,permissions) VALUES (?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),permissions=VALUES(permissions)')
            ->execute([$key,$name,json_encode($permissions)]);
        $pdo->prepare('UPDATE unit_users SET session_version=session_version+1 WHERE role_key=?')->execute([$key]);
        $pdo->commit();
        return $key;
    } catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
}

function central_delete_role(PDO $pdo, string $key): void
{
    $pdo->beginTransaction();
    try {
        $query=$pdo->prepare('SELECT is_system FROM unit_roles WHERE role_key=? FOR UPDATE');$query->execute([$key]);$system=$query->fetchColumn();
        if ($system===false || $system) throw new RuntimeException('Role tidak ditemukan atau merupakan role bawaan.');
        $query=$pdo->prepare('SELECT id FROM unit_users WHERE role_key=? FOR UPDATE');$query->execute([$key]);
        if($query->fetch())throw new RuntimeException('Role masih dipakai akun. Pindahkan akun ke role lain terlebih dahulu.');
        $pdo->prepare('DELETE FROM unit_roles WHERE role_key=?')->execute([$key]);$pdo->commit();
    } catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
}
