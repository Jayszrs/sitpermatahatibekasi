<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Kegiatan Sekolah';
$activities = $pdo->query("SELECT * FROM site_content_items WHERE type='activity' AND is_active=1 ORDER BY sort_order,id")->fetchAll();
require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Kegiatan Sekolah</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Kegiatan</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Kegiatan</span>
            <h2>Kegiatan Rutin &amp; Tahunan</h2>
        </div>
        <div class="activity-grid activity-page-grid">
            <?php foreach ($activities as $a): ?>
            <div class="card activity-card">
                <div class="activity-photo">
                    <img src="<?php echo esc($a['image'] ?: SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg'); ?>" data-fallback="<?php echo SITE_URL; ?>/frontend/assets/images/school/gedung-sekolah.jpeg" alt="<?php echo esc($a['title']); ?>" loading="lazy">
                    <?php if($a['subtitle']): ?><span><?php echo esc($a['subtitle']); ?></span><?php endif; ?>
                </div>
                <div class="card-body">
                    <h3><?php echo esc($a['title']); ?></h3>
                    <p><?php echo nl2br(esc($a['description'])); ?></p>
                    <?php if($a['link_url']): ?><a class="program-link" href="<?php echo esc($a['link_url']); ?>" target="_blank" rel="noopener"><?php echo esc($a['link_label'] ?: 'Lihat di Media Sosial'); ?> &rarr;</a><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
