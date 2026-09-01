<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';

$stmt = $pdo->prepare("SELECT * FROM site_content_items WHERE id=? AND type='foundation' AND is_active=1 LIMIT 1");
$stmt->execute([(int)($_GET['id'] ?? 0)]);
$person = $stmt->fetch();

if (!$person) {
    http_response_code(404);
    $page_title = 'Profil Pengurus Tidak Ditemukan';
    require __DIR__ . '/../components/header.php';
    echo '<section class="section"><div class="container empty-public-state"><h2>Profil pengurus yayasan tidak ditemukan.</h2><a class="btn btn-primary" href="tentang.php#struktur-yayasan">Kembali ke Pengurus Yayasan</a></div></section>';
    require __DIR__ . '/../components/footer.php';
    return;
}

$person['image'] = public_media_url(
    $person['image'] ?? null,
    SITE_URL . '/frontend/assets/images/foundation-profile-placeholder.svg'
);
$education = trim((string)($person['education'] ?? '')) ?: 'Data pendidikan akan diperbarui oleh tim Humas.';
$responsibility = trim((string)($person['teaching_scope'] ?? '')) ?: (trim((string)$person['subtitle']) ?: 'Pengurus Yayasan');
$experience = trim((string)($person['extra'] ?? ''));
$page_title = $person['title'];
require __DIR__ . '/../components/header.php';
?>

<section class="leader-detail-hero foundation-detail-hero">
    <div class="container leader-detail-grid">
        <button type="button" class="leader-detail-photo image-preview-trigger" data-lightbox-src="<?php echo esc($person['image']); ?>" data-lightbox-title="<?php echo esc($person['title']); ?>">
            <img src="<?php echo esc($person['image']); ?>" alt="<?php echo esc($person['title']); ?>">
        </button>
        <div class="leader-detail-copy">
            <a class="back-link light" href="tentang.php#struktur-yayasan">&larr; Kembali ke pengurus yayasan</a>
            <span class="section-eyebrow">Struktur Yayasan &middot; <?php echo esc($person['subtitle']); ?></span>
            <h1><?php echo esc($person['title']); ?></h1>
            <p><?php echo nl2br(esc($person['description'])); ?></p>
            <div class="leader-facts">
                <div><small>Jabatan</small><strong><?php echo esc($person['subtitle']); ?></strong></div>
                <div><small>Riwayat pendidikan</small><strong><?php echo esc($education); ?></strong></div>
                <div><small>Bidang &amp; tanggung jawab</small><strong><?php echo esc($responsibility); ?></strong></div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container leader-detail-note foundation-detail-note">
        <span class="section-eyebrow">Profil Pengurus</span>
        <h2>Peran dalam Tata Kelola Yayasan</h2>
        <p><?php echo nl2br(esc($person['description'])); ?></p>
        <?php if ($experience !== ''): ?>
        <div class="foundation-experience">
            <h3>Riwayat dan pengalaman</h3>
            <p><?php echo nl2br(esc($experience)); ?></p>
        </div>
        <?php endif; ?>
        <a class="btn btn-outline" href="tentang.php#struktur-yayasan">Lihat Semua Pengurus</a>
    </div>
</section>

<?php require __DIR__ . '/../components/footer.php'; ?>
