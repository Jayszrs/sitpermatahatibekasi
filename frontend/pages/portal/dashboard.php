<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth();

$user = portal_user();
$stats = [];
$stats['news'] = (int)$pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();
$stats['gallery'] = (int)$pdo->query('SELECT COUNT(*) FROM gallery_photos')->fetchColumn();
$stats['registrations'] = (int)$pdo->query('SELECT COUNT(*) FROM spmb_registrations')->fetchColumn();
$stats['paid'] = (int)$pdo->query("SELECT COUNT(*) FROM spmb_registrations WHERE payment_status = 'lunas'")->fetchColumn();
$logs = $pdo->query("SELECT l.*, u.name FROM portal_activity_logs l LEFT JOIN portal_users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT 7")->fetchAll();

$portalTitle = 'Dashboard';
$portalActive = 'dashboard';
require __DIR__ . '/../../components/portal-header.php';
?>
<div class="portal-welcome">
    <div>
        <h2>Assalamu'alaikum, <?php echo esc($user['name']); ?>!</h2>
        <p><?php echo $user['role'] === 'humas' ? 'Kelola publikasi sekolah dari satu tempat.' : ($user['role'] === 'kasir' ? 'Pantau administrasi pembayaran SPMB hari ini.' : 'Berikut ringkasan aktivitas portal sekolah.'); ?></p>
    </div>
    <?php if (in_array($user['role'], ['admin', 'humas'], true)): ?>
        <a class="portal-action" href="<?php echo SITE_URL; ?>/portal/content?new=news">+ Tambah Berita</a>
    <?php else: ?>
        <a class="portal-action" href="<?php echo SITE_URL; ?>/portal/payments">Kelola Pembayaran</a>
    <?php endif; ?>
</div>
<div class="stat-grid">
    <div class="stat-card"><small>Berita terbit</small><strong><?php echo $stats['news']; ?></strong><span>Konten website</span></div>
    <div class="stat-card"><small>Foto galeri</small><strong><?php echo $stats['gallery']; ?></strong><span>Dokumentasi</span></div>
    <div class="stat-card"><small>Pendaftar SPMB</small><strong><?php echo $stats['registrations']; ?></strong><span>Total pendaftar</span></div>
    <div class="stat-card"><small>Sudah lunas</small><strong><?php echo $stats['paid']; ?></strong><span>Pembayaran terverifikasi</span></div>
</div>
<div class="panel-grid">
    <section class="portal-panel">
        <div class="panel-head"><h3>Aktivitas Terbaru</h3></div>
        <div class="activity-list">
            <?php if (!$logs): ?><p class="empty-state">Belum ada aktivitas.</p><?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <div class="activity-item"><span class="activity-dot"></span><div><p><?php echo esc($log['description']); ?></p><small><?php echo esc($log['name'] ?: 'Sistem'); ?> &middot; <?php echo esc(date('d/m/Y H:i', strtotime($log['created_at']))); ?></small></div></div>
            <?php endforeach; ?>
        </div>
    </section>
    <aside class="portal-panel">
        <div class="panel-head"><h3>Akses Cepat</h3></div>
        <div class="quick-links">
            <?php if (in_array($user['role'], ['admin', 'humas'], true)): ?>
                <a class="quick-link" href="<?php echo SITE_URL; ?>/portal/content"><strong>Berita &amp; Galeri</strong><small>Publikasi informasi dan dokumentasi sekolah.</small></a>
                <a class="quick-link" href="<?php echo SITE_URL; ?>/portal/site-content?type=unit"><strong>Unit, Program &amp; Prestasi</strong><small>Kelola gambar Daycare, TKIT, SDIT, SMPIT, dan konten pendidikan.</small></a>
                <a class="quick-link" href="<?php echo SITE_URL; ?>/portal/site-content?type=leadership"><strong>Profil &amp; Pimpinan</strong><small>Perbarui sejarah, visi misi, foto, nama, dan jabatan.</small></a>
                <a class="quick-link" href="<?php echo SITE_URL; ?>/portal/careers"><strong>Karir &amp; Lamaran</strong><small>Publikasikan lowongan dan proses kandidat yang masuk.</small></a>
            <?php endif; ?>
            <?php if (in_array($user['role'], ['admin', 'kasir'], true)): ?>
                <a class="quick-link" href="<?php echo SITE_URL; ?>/portal/payments"><strong>Pembayaran SPMB</strong><small>Cek dan ubah status pembayaran.</small></a>
            <?php endif; ?>
            <?php if ($user['role'] === 'admin'): ?>
                <a class="quick-link" href="<?php echo SITE_URL; ?>/portal/users"><strong>Pengguna Portal</strong><small>Kelola akun dan role petugas.</small></a>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php require __DIR__ . '/../../components/portal-footer.php'; ?>
