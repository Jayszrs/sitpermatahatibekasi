<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';

portal_require_guest();

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    portal_verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (portal_attempt_login($pdo, $username, $password)) {
        header('Location: ' . portal_home_for_role(portal_user()['role']));
        exit;
    }
    $error = 'Username atau kata sandi tidak sesuai.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?php echo esc(asset_url('frontend/assets/images/logo-sit-round.png')); ?>">
    <title>Login Portal Internal | PHB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo esc(asset_url('frontend/assets/css/portal.css')); ?>">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-visual">
        <div class="login-visual-overlay"></div>
        <div class="login-visual-content">
            <div class="portal-brand">
                <span class="portal-brand-logo"><img src="<?php echo esc(asset_url('frontend/assets/images/logo-sit-round.png')); ?>" alt="Logo SIT Permata Hati Bekasi"></span>
                <span>PortalPHB</span>
            </div>
            <span class="version-pill">Sistem Internal</span>
            <div class="visual-copy">
                <span class="shield-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.6 2.9 8.3 7 10 4.1-1.7 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                </span>
                <p class="visual-kicker">Sistem Informasi Terpadu</p>
                <h1>Administrasi Sekolah<br><span>Lebih Terarah</span></h1>
                <p>Kelola publikasi sekolah dan administrasi SPMB melalui satu ruang kerja yang aman, efisien, dan terintegrasi.</p>
                <div class="visual-role-card">
                    <span>Akses berbasis peran</span>
                    <small>Admin, Humas, dan Kasir SPMB memperoleh menu sesuai tanggung jawab masing-masing.</small>
                </div>
            </div>
            <small class="portal-copyright">&copy; 2026 SIT Permata Hati Bekasi. Portal administrasi internal.</small>
        </div>
    </section>

    <section class="login-form-side">
        <div class="login-box">
            <span class="mobile-brand">PortalPHB</span>
            <div class="login-heading-mark"></div>
            <span class="role-badge">Akses Petugas</span>
            <h2>Selamat datang</h2>
            <p class="login-subtitle">Silakan masuk menggunakan akun portal sekolah Anda.</p>

            <?php if ($error): ?>
                <div class="portal-alert danger"><?php echo esc($error); ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="on">
                <input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>">
                <div class="portal-field">
                    <label for="username">USERNAME</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3-7 8-7s8 3 8 7"/></svg>
                        <input id="username" name="username" type="text" value="<?php echo esc($username); ?>" placeholder="Masukkan username" autocomplete="username" required autofocus>
                    </div>
                </div>
                <div class="portal-field">
                    <label for="password">PASSWORD</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" id="passwordToggle" aria-label="Lihat kata sandi">
                            <svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </div>
                </div>
                <button class="login-submit" type="submit">Masuk <span>&rarr;</span></button>
            </form>
            <p class="login-help">Akun portal dikelola oleh Administrator SIT Permata Hati Bekasi.</p>
        </div>
    </section>
  </main>
  <script>
    document.getElementById('passwordToggle').addEventListener('click', function () {
      const input = document.getElementById('password');
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  </script>
</body>
</html>
