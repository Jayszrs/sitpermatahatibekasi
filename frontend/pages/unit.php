<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Unit Sekolah';
$units = $pdo->query("SELECT * FROM site_content_items WHERE type='unit' AND is_active=1 ORDER BY sort_order,id")->fetchAll();
$unit_image_map = [
    'daycare' => SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg',
    'tkit' => SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg',
    'sdit' => SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg',
    'smpit' => SITE_URL . '/frontend/assets/images/school/gedung-smpit.jpeg',
];
require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Unit Sekolah</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Unit Sekolah</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php foreach ($units as $index=>$unit): ?>
        <?php $unit_key = strtolower($unit['subtitle'] ?: 'unit-'.$unit['id']); ?>
        <?php $unit_image = $unit['image'] ?: ($unit_image_map[$unit_key] ?? SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg'); ?>
        <div class="unit-block <?php echo $index % 2 ? 'reverse' : ''; ?>" id="<?php echo esc($unit_key); ?>">
            <button type="button" class="unit-media image-preview-trigger" data-lightbox-src="<?php echo esc($unit_image); ?>" data-lightbox-title="<?php echo esc($unit['title']); ?>">
                <img src="<?php echo esc($unit_image); ?>" alt="<?php echo esc($unit['title']); ?>">
                <span class="unit-media-hint">Klik untuk lihat foto penuh</span>
            </button>
            <div class="unit-text"><span class="section-eyebrow"><?php echo esc($unit['subtitle'] ?: 'Unit Pendidikan'); ?></span><h2><?php echo esc($unit['title']); ?></h2><p><?php echo nl2br(esc($unit['description'])); ?></p>
            <?php $tags=array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',(string)$unit['extra']))); if($tags): ?><div class="unit-tags"><?php foreach($tags as $tag): ?><span><?php echo esc($tag); ?></span><?php endforeach; ?></div><?php endif; ?>
            <a href="form-spmb.php?level=<?php echo urlencode($unit['subtitle']); ?>" class="btn btn-primary">Daftar di Unit Ini</a></div>
        </div>
        <?php endforeach; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
