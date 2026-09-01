<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';

$stmt = $pdo->prepare("SELECT * FROM site_content_items WHERE id=? AND type='leadership' AND is_active=1 LIMIT 1");
$stmt->execute([(int)($_GET['id'] ?? 0)]);
$leader = $stmt->fetch();
if (!$leader) {
    http_response_code(404);
    $page_title = 'Profil Pimpinan Tidak Ditemukan';
    require __DIR__ . '/../components/header.php';
    echo '<section class="section"><div class="container empty-public-state"><h2>Profil pimpinan tidak ditemukan.</h2><a class="btn btn-primary" href="tentang.php">Kembali ke Tentang Kami</a></div></section>';
    require __DIR__ . '/../components/footer.php';
    return;
}

$page_title = $leader['title'];
$leader['image'] = public_media_url($leader['image'] ?? null, SITE_URL . '/frontend/assets/images/logo-sit-round.png');
$unitLabel = strtoupper((string)$leader['unit_slug']);
require __DIR__ . '/../components/header.php';
?>
<section class="leader-detail-hero">
    <div class="container leader-detail-grid">
        <button type="button" class="leader-detail-photo image-preview-trigger" data-lightbox-src="<?php echo esc($leader['image']); ?>" data-lightbox-title="<?php echo esc($leader['title']); ?>"><img src="<?php echo esc($leader['image']); ?>" alt="<?php echo esc($leader['title']); ?>"></button>
        <div class="leader-detail-copy"><a class="back-link light" href="tentang.php">&larr; Kembali ke struktur pimpinan</a><span class="section-eyebrow"><?php echo esc($unitLabel.' · '.$leader['subtitle']); ?></span><h1><?php echo esc($leader['title']); ?></h1><p><?php echo nl2br(esc($leader['description'])); ?></p><div class="leader-facts"><div><small>Unit</small><strong><?php echo esc($unitLabel); ?></strong></div><div><small>Pendidikan</small><strong><?php echo esc($leader['education']); ?></strong></div><div><small>Tempat &amp; bidang mengajar</small><strong><?php echo esc($leader['teaching_scope']); ?></strong></div></div></div>
    </div>
</section>
<section class="section"><div class="container leader-detail-note"><span class="section-eyebrow">Profil Pendidik</span><h2>Peran dalam Ekosistem Sekolah</h2><p><?php echo esc($leader['description']); ?> Profil ini dapat diperbarui oleh tim Humas melalui Portal PHB saat data resmi pimpinan tersedia.</p><a class="btn btn-outline" href="unit.php#<?php echo esc($leader['unit_slug']); ?>">Lihat Unit <?php echo esc($unitLabel); ?></a></div></section>
<?php require __DIR__ . '/../components/footer.php'; ?>
