<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Tentang Kami';
$profile = $pdo->query('SELECT * FROM site_profile WHERE id=1')->fetch();
$leaders = $pdo->query("SELECT * FROM site_content_items WHERE type='leadership' AND is_active=1 ORDER BY sort_order,id")->fetchAll();
$foundation = $pdo->query("SELECT * FROM site_content_items WHERE type='foundation' AND is_active=1 ORDER BY sort_order,id")->fetchAll();
require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Tentang Kami</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Tentang Kami</p>
    </div>
</section>

<section class="section">
    <div class="container about-grid">
        <img src="<?php echo esc($profile['image'] ?: SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg'); ?>" data-fallback="<?php echo SITE_URL; ?>/frontend/assets/images/school/gedung-sekolah.jpeg" alt="Sejarah Sekolah">
        <div class="about-text">
            <span class="section-eyebrow">Sejarah Kami</span>
            <h2><?php echo esc($profile['history_title']); ?></h2>
            <p><?php echo nl2br(esc($profile['history_content'])); ?></p>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container grid-2">
        <div class="card">
            <div class="card-body">
                <h3>Visi</h3>
                <p><?php echo nl2br(esc($profile['vision'])); ?></p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3>Misi</h3>
                <p><?php echo nl2br(esc($profile['mission'])); ?></p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Struktur</span>
            <h2>Kepemimpinan Sekolah</h2>
            <p>Dipimpin oleh tenaga pendidik profesional dan berpengalaman.</p>
        </div>
        <div class="grid-3">
            <?php foreach($leaders as $leader): ?><div class="card"><img src="<?php echo esc($leader['image'] ?: SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg'); ?>" data-fallback="<?php echo SITE_URL; ?>/frontend/assets/images/school/gedung-sekolah.jpeg" alt="<?php echo esc($leader['title']); ?>"><div class="card-body"><span class="section-eyebrow"><?php echo esc($leader['subtitle']); ?></span><h3><?php echo esc($leader['title']); ?></h3><p><?php echo nl2br(esc($leader['description'])); ?></p></div></div><?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section foundation-section">
    <div class="container">
        <div class="section-head"><span class="section-eyebrow">Struktur Yayasan</span><h2>Pengurus Yayasan</h2><p>Tata kelola lembaga yang amanah, profesional, dan berorientasi pada mutu pendidikan.</p></div>
        <div class="grid-3 foundation-grid"><?php foreach($foundation as $person): ?><article class="card foundation-card"><?php if($person['image']): ?><img src="<?php echo esc($person['image']); ?>" alt="<?php echo esc($person['title']); ?>"><?php else: ?><div class="foundation-avatar"><?php echo esc(mb_substr($person['title'],0,1)); ?></div><?php endif; ?><div class="card-body"><span class="section-eyebrow"><?php echo esc($person['subtitle']); ?></span><h3><?php echo esc($person['title']); ?></h3><p><?php echo esc($person['description']); ?></p></div></article><?php endforeach; ?></div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
