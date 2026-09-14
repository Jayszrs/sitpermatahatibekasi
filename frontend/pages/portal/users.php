<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    portal_verify_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_user') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $username = strtolower(trim($_POST['username'] ?? ''));
            $role = $_POST['role'] ?? '';
            $password = $_POST['password'] ?? '';
            if ($name === '' || !preg_match('/^[a-z0-9._-]{3,30}$/', $username) || !in_array($role, ['admin','humas','kasir'], true)) throw new RuntimeException('Nama, username, atau role belum valid. Username minimal 3 karakter dan hanya boleh berisi huruf kecil, angka, titik, garis bawah, atau strip.');
            if (!$id && strlen($password) < 8) throw new RuntimeException('Password akun baru minimal 8 karakter.');
            if ($id) {
                if ($id === portal_user()['id'] && $role !== 'admin') throw new RuntimeException('Role akun admin yang sedang dipakai tidak dapat diubah.');
                if ($password !== '' && strlen($password) < 8) throw new RuntimeException('Password minimal 8 karakter.');
                if ($password !== '') {
                    $pdo->prepare('UPDATE portal_users SET name=?, username=?, role=?, password=? WHERE id=?')->execute([$name,$username,$role,password_hash($password,PASSWORD_DEFAULT),$id]);
                } else {
                    $pdo->prepare('UPDATE portal_users SET name=?, username=?, role=? WHERE id=?')->execute([$name,$username,$role,$id]);
                }
                if ($id === portal_user()['id']) {
                    $_SESSION['portal_user']['name'] = $name; $_SESSION['portal_user']['username'] = $username; $_SESSION['portal_user']['role'] = $role;
                }
                portal_log($pdo, 'update_user', 'Memperbarui akun: ' . $username);
            } else {
                $pdo->prepare('INSERT INTO portal_users (name,username,password,role) VALUES (?,?,?,?)')->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT),$role]);
                portal_log($pdo, 'create_user', 'Membuat akun ' . $role . ': ' . $username);
            }
            portal_flash('success', 'Akun pengguna berhasil disimpan.');
        } elseif ($action === 'toggle_user') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === portal_user()['id']) throw new RuntimeException('Akun yang sedang dipakai tidak dapat dinonaktifkan.');
            $pdo->prepare('UPDATE portal_users SET is_active = IF(is_active=1,0,1) WHERE id=?')->execute([$id]);
            portal_log($pdo, 'toggle_user', 'Mengubah status aktif pengguna #' . $id);
            portal_flash('success', 'Status akun berhasil diubah.');
        }
    } catch (PDOException $e) {
        portal_flash('danger', $e->getCode() === '23000' ? 'Username tersebut sudah dipakai akun lain.' : 'Data pengguna gagal disimpan.');
    } catch (Throwable $e) { portal_flash('danger', $e->getMessage()); }
    header('Location: ' . SITE_URL . '/portal/users'); exit;
}

$editUser = null;
if (isset($_GET['edit'])) { $stmt=$pdo->prepare('SELECT * FROM portal_users WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $editUser=$stmt->fetch() ?: null; }
$showForm = isset($_GET['new']) || $editUser;
$users = $pdo->query('SELECT * FROM portal_users ORDER BY role, name')->fetchAll();
$portalTitle = 'Manajemen Pengguna'; $portalActive = 'users';
require __DIR__ . '/../../components/portal-header.php';
?>
<div class="portal-welcome"><div><h2>Akun &amp; Hak Akses</h2><p>Admin dapat menambah petugas dan menentukan role portal.</p></div><a class="portal-action" href="<?php echo SITE_URL; ?>/portal/users?new=1">+ Tambah Akun</a></div>
<?php if ($showForm): ?><section class="portal-panel" style="margin-bottom:22px"><div class="panel-head"><h3><?php echo $editUser?'Edit Akun':'Akun Baru'; ?></h3><a href="<?php echo SITE_URL; ?>/portal/users">Tutup</a></div><form method="post" class="portal-form portal-form-grid"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save_user"><input type="hidden" name="id" value="<?php echo (int)($editUser['id']??0); ?>"><div class="field"><label>Nama petugas</label><input name="name" value="<?php echo esc($editUser['name']??''); ?>" required></div><div class="field"><label>Username login</label><input name="username" value="<?php echo esc($editUser['username']??''); ?>" minlength="3" maxlength="30" pattern="[a-z0-9._-]+" required><small style="color:var(--portal-muted)">Tanpa email, contoh: humas.sd atau kasir2</small></div><div class="field"><label>Role</label><select name="role" required><?php foreach(['admin'=>'Admin','humas'=>'Humas','kasir'=>'Kasir SPMB'] as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo ($editUser['role']??'')===$key?'selected':''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div><div class="field"><label>Password <?php echo $editUser?'(kosongkan jika tetap)':''; ?></label><input type="password" name="password" <?php echo $editUser?'':'required'; ?> minlength="8"></div><div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/users">Batal</a><button class="portal-action">Simpan Akun</button></div></form></section><?php endif; ?>
<section class="portal-panel"><div class="panel-head"><h3>Daftar Pengguna (<?php echo count($users); ?>)</h3></div><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Nama</th><th class="hide-mobile">Username</th><th>Role</th><th>Status</th><th class="hide-mobile">Login terakhir</th><th>Aksi</th></tr></thead><tbody><?php foreach($users as $item): ?><tr><td><strong><?php echo esc($item['name']); ?></strong></td><td class="hide-mobile"><code><?php echo esc($item['username']); ?></code></td><td><?php echo esc(ucfirst($item['role'])); ?></td><td><span class="status <?php echo $item['is_active']?'active':'inactive'; ?>"><?php echo $item['is_active']?'Aktif':'Nonaktif'; ?></span></td><td class="hide-mobile"><?php echo $item['last_login_at']?esc(date('d/m/Y H:i',strtotime($item['last_login_at']))):'-'; ?></td><td><div class="table-actions"><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/portal/users?edit=<?php echo (int)$item['id']; ?>">Edit</a><?php if((int)$item['id']!==portal_user()['id']): ?><form method="post"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="toggle_user"><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>"><button class="portal-action <?php echo $item['is_active']?'danger':'secondary'; ?> small"><?php echo $item['is_active']?'Nonaktifkan':'Aktifkan'; ?></button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/../../components/portal-footer.php'; ?>
