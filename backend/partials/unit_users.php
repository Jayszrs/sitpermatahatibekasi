<?php
$staffRoles = ['unit_editor'=>'Humas Unit','unit_admissions'=>'Petugas SPMB Unit'];
$roles = $pdo->query('SELECT role_key,name FROM unit_roles ORDER BY is_system DESC,name')->fetchAll();
$roleLabels = ['superadmin'=>'Superadmin Semua Unit'];
foreach ($roles as $role) $roleLabels[$role['role_key']] = $role['name'];
if ($isSuper) {
    $users = $pdo->query('SELECT * FROM unit_users ORDER BY role DESC,unit_slug,username')->fetchAll();
} else {
    $query = $pdo->prepare("SELECT * FROM unit_users WHERE unit_slug=? AND role_key IN ('unit_editor','unit_admissions') ORDER BY username");
    $query->execute([$scope]);
    $users = $query->fetchAll();
}
$editId = (int) ($_GET['edit_user'] ?? 0);
$editUser = null;
foreach ($users as $user) if ((int)$user['id'] === $editId) $editUser = $user;
?>
<section class="panel"><h2>Manajemen Pengguna</h2><p class="muted"><?php echo $isSuper ? 'Kelola seluruh akun, role, penugasan unit, dan password.' : 'Admin unit dapat mengubah role Humas atau SPMB dan mengganti password petugas di unit ini.'; ?></p>
<?php if($isSuper): ?><p><a href="<?php echo unit_e(SITE_URL.'/portal/users?scope=unit'); ?>">Buka manajemen pengguna yayasan dan seluruh unit</a></p><?php endif; ?>
<div style="overflow:auto"><table><thead><tr><th>Nama / Username</th><th>Role</th><th>Unit</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($users as $user): ?><tr><td><strong><?php echo unit_e($user['name'] ?: $user['username']); ?></strong><br><small><?php echo unit_e($user['username']); ?></small></td><td><?php echo unit_e($roleLabels[$user['role_key'] ?: $user['role']] ?? $user['role']); ?></td><td><?php echo unit_e($allUnits[$user['unit_slug']] ?? 'Semua Unit'); ?></td><td><?php echo $user['is_active']?'Aktif':'Nonaktif'; ?></td><td><a href="?tab=users&amp;unit=<?php echo unit_e($scope); ?>&amp;edit_user=<?php echo (int)$user['id']; ?>">Ubah role / password</a></td></tr><?php endforeach; ?>
<?php if(!$users): ?><tr><td colspan="5">Belum ada akun petugas yang dapat dikelola.</td></tr><?php endif; ?></tbody></table></div></section>
<?php if($isSuper || $editUser): ?>
<section class="panel"><h2><?php echo $editUser?'Ubah Akun':'Tambah Akun Unit'; ?></h2><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>"><input type="hidden" name="action" value="<?php echo $isSuper?'save_user':'manage_staff'; ?>"><input type="hidden" name="id" value="<?php echo (int)($editUser['id'] ?? 0); ?>">
<?php if($isSuper): ?>
<label>Nama<input required name="name" maxlength="120" value="<?php echo unit_e($editUser['name'] ?? ''); ?>"></label><label>Username<input required name="username" pattern="[a-z0-9._-]{3,30}" value="<?php echo unit_e($editUser['username'] ?? ''); ?>"></label>
<label>Role<select name="role_key"><?php foreach($roleLabels as $key=>$label): ?><option value="<?php echo unit_e($key); ?>" <?php echo ($editUser['role_key'] ?? $editUser['role'] ?? 'unit_admin')===$key?'selected':''; ?>><?php echo unit_e($label); ?></option><?php endforeach; ?></select></label>
<label>Penugasan Unit<select name="user_unit"><?php foreach($allUnits as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo ($editUser['unit_slug'] ?? $scope)===$key?'selected':''; ?>><?php echo unit_e($label); ?></option><?php endforeach; ?></select></label>
<label>Status<select name="is_active"><option value="1" <?php echo !$editUser || $editUser['is_active']?'selected':''; ?>>Aktif</option><option value="0" <?php echo $editUser && !$editUser['is_active']?'selected':''; ?>>Nonaktif</option></select></label>
<?php else: ?>
<p class="full">Akun <strong><?php echo unit_e($editUser['username']); ?></strong> · <?php echo unit_e($allUnits[$scope]); ?></p>
<label>Role<select name="role_key"><?php foreach($staffRoles as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo $editUser['role_key']===$key?'selected':''; ?>><?php echo unit_e($label); ?></option><?php endforeach; ?></select></label>
<?php endif; ?>
<?php if($isSuper) require __DIR__ . '/replace_admin_form.php'; ?>
<?php if(!$isSuper): ?><section class="panel"><h2>Ganti Password Saya</h2><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>"><input type="hidden" name="action" value="change_own_password"><label>Password saat ini<input type="password" name="current_password" autocomplete="current-password" required></label><label>Password baru<input type="password" name="new_password" autocomplete="new-password" minlength="8" required></label><div class="full"><button>Ganti Password</button></div></form></section><?php endif; ?>
<label>Password <?php echo $editUser?'baru (kosongkan jika tidak diubah)':'*'; ?><input type="password" name="password" minlength="8" autocomplete="new-password" <?php echo $editUser?'':'required'; ?>></label>
<div class="full actions"><button>Simpan Akun</button><a href="?tab=users&amp;unit=<?php echo unit_e($scope); ?>">Batal</a></div></form></section>
<?php endif; ?>
