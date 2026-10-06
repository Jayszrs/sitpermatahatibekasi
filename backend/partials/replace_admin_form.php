<?php if (empty($isSuper)) return; ?>
<section class="panel">
    <h2>Ganti Admin Yayasan &amp; Superadmin</h2>
    <p>Buat satu ID dan password untuk portal yayasan serta keempat CMS unit. Setelah akun baru berhasil dibuat, login lama <code>admin</code> dan <code>superadmin</code> dihapus. Anda akan diminta masuk menggunakan akun baru.</p>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>">
        <input type="hidden" name="action" value="replace_foundation_admin">
        <label>ID admin baru<input name="new_username" required minlength="3" maxlength="30" pattern="[a-z0-9._-]+" autocomplete="off" placeholder="admin.yayasan"></label>
        <label>Password baru<input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
        <label>Ulangi password baru<input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></label>
        <label>Password superadmin saat ini<input type="password" name="current_password" required autocomplete="current-password"></label>
        <div><button type="submit">Buat Akun Pengganti &amp; Hapus Login Lama</button></div>
    </form>
</section>
