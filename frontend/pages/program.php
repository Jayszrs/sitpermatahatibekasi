<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Program Unggulan';
$programs = $pdo->query("SELECT * FROM site_content_items WHERE type='program' AND is_active=1 ORDER BY sort_order,id")->fetchAll();
require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Program Unggulan</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Program</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Program Pendidikan</span>
            <h2>Program yang Kami Tawarkan</h2>
            <p>Dirancang untuk mengembangkan potensi siswa secara akademik, spiritual, dan sosial.</p>
        </div>
        <div class="grid-3">
            <?php foreach ($programs as $p): ?>
            <?php $programUrl=$p['link_url'] ?: 'detail-program.php?id='.$p['id']; ?>
            <a class="card program-page-card" id="program-<?php echo (int)$p['id']; ?>" href="<?php echo esc($programUrl); ?>" <?php echo preg_match('~^https?://~',$programUrl)?'target="_blank" rel="noopener"':''; ?>>
                <?php if($p['image']): ?><img src="<?php echo esc($p['image']); ?>" alt="<?php echo esc($p['title']); ?>" loading="lazy"><?php endif; ?>
                <div class="card-body">
                    <div class="program-icon" style="margin-bottom:16px;"><?php echo esc($p['subtitle'] ?: mb_substr($p['title'],0,1)); ?></div>
                    <h3><?php echo esc($p['title']); ?></h3>
                    <p><?php echo nl2br(esc($p['description'])); ?></p>
                    <strong class="btn btn-outline btn-sm"><?php echo esc($p['link_label'] ?: 'Pelajari Program'); ?></strong>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
