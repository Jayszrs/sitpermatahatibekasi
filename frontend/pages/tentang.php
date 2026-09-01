<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Tentang Kami';
$profile = $pdo->query('SELECT * FROM site_profile WHERE id=1')->fetch();
$leaders = $pdo->query("SELECT * FROM site_content_items WHERE type='leadership' AND is_active=1 AND unit_slug IN ('daycare','tkit','sdit','smpit') ORDER BY sort_order,id")->fetchAll();
$leaderUnits = ['semua'=>'Semua','daycare'=>'Daycare','tkit'=>'TKIT','sdit'=>'SDIT','smpit'=>'SMPIT'];
$foundation = $pdo->query("SELECT * FROM site_content_items WHERE type='foundation' AND is_active=1 ORDER BY sort_order,id")->fetchAll();
if ($profile) $profile['image'] = public_media_url($profile['image'] ?? null, SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg');
foreach ($leaders as &$leaderItem) $leaderItem['image'] = public_media_url($leaderItem['image'] ?? null, SITE_URL . '/frontend/assets/images/logo-sit-round.png');
unset($leaderItem);
foreach ($foundation as &$foundationItem) if (!empty($foundationItem['image'])) $foundationItem['image'] = public_media_url($foundationItem['image'], '');
unset($foundationItem);
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
            <p>Kenali pimpinan dan koordinator yang mendampingi setiap jenjang. Pilih unit untuk melihat struktur yang relevan.</p>
        </div>
        <div class="achievement-tabs leadership-tabs" aria-label="Filter pimpinan per unit"><?php foreach($leaderUnits as $slug=>$label): ?><button type="button" class="achievement-tab<?php echo $slug==='semua'?' active':''; ?>" data-leader-filter="<?php echo $slug; ?>"><?php echo $label; ?></button><?php endforeach; ?></div>
        <div class="leadership-grid">
            <?php foreach($leaders as $leader): ?><a class="leadership-card" data-leader-unit="<?php echo esc($leader['unit_slug']); ?>" href="detail-pimpinan.php?id=<?php echo (int)$leader['id']; ?>"><div class="leadership-photo"><img src="<?php echo esc($leader['image']); ?>" alt="<?php echo esc($leader['title']); ?>" loading="lazy"><span><?php echo esc(strtoupper($leader['unit_slug'])); ?></span></div><div class="leadership-copy"><small><?php echo esc($leader['subtitle']); ?></small><h3><?php echo esc($leader['title']); ?></h3><p><?php echo esc($leader['education']); ?></p><strong>Lihat profil lengkap &rarr;</strong></div></a><?php endforeach; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('[data-leader-filter]');
    const cards = document.querySelectorAll('[data-leader-unit]');
    buttons.forEach((button) => button.addEventListener('click', function () {
        const filter = button.dataset.leaderFilter;
        buttons.forEach((item) => item.classList.toggle('active', item === button));
        cards.forEach((card) => card.classList.toggle('is-hidden', filter !== 'semua' && card.dataset.leaderUnit !== filter));
    }));
});
</script>
