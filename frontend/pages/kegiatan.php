<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Kegiatan Sekolah';
$activities = $pdo->query("SELECT item.*
    FROM site_content_items item
    INNER JOIN (
        SELECT MIN(id) AS id
        FROM site_content_items
        WHERE type='activity' AND is_active=1
        GROUP BY LOWER(TRIM(title)),LOWER(TRIM(COALESCE(subtitle,'')))
    ) unique_activity ON unique_activity.id=item.id
    ORDER BY item.sort_order,item.id")->fetchAll();
foreach ($activities as &$activityItem) $activityItem['image'] = public_media_url($activityItem['image'] ?? null);
unset($activityItem);
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
            <article class="card activity-card" id="kegiatan-<?php echo (int)$a['id']; ?>">
                <div class="activity-photo">
                    <img src="<?php echo esc($a['image'] ?: SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg'); ?>" data-fallback="<?php echo SITE_URL; ?>/frontend/assets/images/school/gedung-sekolah.jpeg" alt="<?php echo esc($a['title']); ?>" loading="lazy">
                    <?php if($a['subtitle']): ?><span><?php echo esc($a['subtitle']); ?></span><?php endif; ?>
                </div>
                <div class="card-body">
                    <h3><?php echo esc($a['title']); ?></h3>
                    <p><?php echo nl2br(esc($a['description'])); ?></p>
                    <a class="program-link" href="<?php echo esc(SITE_URL . '/kegiatan-detail.php?id=' . (int)$a['id']); ?>">Lihat detail kegiatan &rarr;</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
