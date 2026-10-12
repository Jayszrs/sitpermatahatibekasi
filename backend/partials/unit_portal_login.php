<?php
$unitTitle = $unit_config['name'];
$unitLogo = unit_asset('assets/images/logo.png');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login <?php echo unit_e($unit_config['short_name']); ?> | Portal PHB</title>
    <link rel="icon" href="<?php echo unit_e($unitLogo); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo unit_e(asset_url('frontend/assets/css/portal.css')); ?>">
    <link rel="stylesheet" href="<?php echo unit_e(asset_url('frontend/assets/css/unit-portal.css')); ?>">
</head>
<body class="login-page unit-portal">
<main class="login-shell">
    <section class="login-visual">
        <div class="login-visual-overlay"></div>
        <div class="login-visual-content">
            <div class="portal-brand">
                <span class="portal-brand-logo"><img src="<?php echo unit_e($unitLogo); ?>" alt="Logo <?php echo unit_e($unit_config['short_name']); ?>"></span>
                <span>PortalPHB <small><?php echo unit_e($unit_config['short_name']); ?></small></span>
            </div>
            <div class="visual-copy">
                <span class="shield-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.6 2.9 8.3 7 10 4.1-1.7 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                <p class="visual-kicker">Sistem Informasi Terpadu</p>
                <h1>Administrasi Sekolah<br><span>Lebih Terarah</span></h1>
                <p>Kelola publikasi sekolah dan administrasi SPMB melalui satu ruang kerja.</p>
                <div class="visual-role-card"><span><?php echo unit_e($unitTitle); ?></span><small>Menu tersedia sesuai peran dan tanggung jawab akun Anda.</small></div>
            </div>
            <small class="portal-copyright">&copy; <?php echo date('Y'); ?> SIT Permata Hati Bekasi.</small>
        </div>
    </section>
    <section class="login-form-side">
        <div class="login-box">
            <div class="mobile-brand unit-mobile-brand"><img src="<?php echo unit_e($unitLogo); ?>" alt=""><span>PortalPHB <small><?php echo unit_e($unitTitle); ?></small></span></div>
            <div class="login-heading-mark"></div>
            <span class="role-badge">Akses Petugas <?php echo unit_e($unit_config['short_name']); ?></span>
            <h2>Selamat datang</h2>
            <p class="login-subtitle">Silakan masuk menggunakan akun portal unit Anda.</p>
            <?php if ($error): ?><div class="portal-alert danger" role="alert"><?php echo unit_e($error); ?></div><?php endif; ?>
            <form method="post" autocomplete="on">
                <input type="hidden" name="action" value="login">
                <div class="portal-field">
                    <label for="unitUsername">USERNAME</label>
                    <div class="input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3-7 8-7s8 3 8 7"/></svg><input id="unitUsername" name="username" value="<?php echo unit_e((string)($_POST['username'] ?? '')); ?>" placeholder="Masukkan username" autocomplete="username" required autofocus></div>
                </div>
                <div class="portal-field">
                    <label for="unitPassword">PASSWORD</label>
                    <div class="input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="unitPassword" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required><button class="password-toggle" type="button" id="unitPasswordToggle" aria-label="Lihat kata sandi" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div>
                </div>
                <button class="login-submit" type="submit">Masuk</button>
            </form>
            <p class="login-help">Akun portal dikelola oleh administrator sekolah.</p>
        </div>
    </section>
</main>
<script>
document.getElementById('unitPasswordToggle').addEventListener('click', function () {
    const input = document.getElementById('unitPassword');
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    this.setAttribute('aria-pressed', String(visible));
    this.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
});
</script>
</body>
</html>
