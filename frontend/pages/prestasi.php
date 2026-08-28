<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Prestasi';
$achievements = $pdo->query("SELECT * FROM site_content_items WHERE type='achievement' AND is_active=1 ORDER BY sort_order,id")->fetchAll();
$achievement_units = ['Semua', 'Daycare', 'TKIT', 'SDIT', 'SMPIT'];
require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Prestasi</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Prestasi</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Prestasi Siswa</span>
            <h2>Pencapaian yang Membanggakan</h2>
        </div>
        <div class="achievement-tabs" role="tablist" aria-label="Filter prestasi berdasarkan unit">
            <?php foreach ($achievement_units as $unit): ?>
                <button type="button" class="achievement-tab<?php echo $unit === 'Semua' ? ' active' : ''; ?>" data-achievement-filter="<?php echo esc(strtolower($unit)); ?>"><?php echo esc($unit); ?></button>
            <?php endforeach; ?>
        </div>
        <div class="achievement-grid">
            <?php foreach ($achievements as $a): ?>
                <?php
                    $unit = $a['extra'] ?: 'SDIT';
                    $unitKey = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $unit));
                    $level = $a['badge'] ?: 'Prestasi';
                    $image = $a['image'] ?: SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg';
                ?>
            <div class="card achieve-card" id="prestasi-<?php echo (int)$a['id']; ?>" data-achievement-unit="<?php echo esc($unitKey); ?>">
                <div class="achieve-image-wrap">
                    <img src="<?php echo esc($image); ?>" data-fallback="<?php echo SITE_URL; ?>/frontend/assets/images/school/gedung-sekolah.jpeg" alt="<?php echo esc($a['title']); ?>" loading="lazy">
                    <span class="achieve-tag"><?php echo esc($level); ?></span>
                    <span class="achieve-unit-badge"><?php echo esc($unit); ?></span>
                    <div class="achieve-overlay">
                        <h3><?php echo esc($a['title']); ?></h3>
                    </div>
                </div>
                <div class="achieve-info">
                    <span><strong>Unit</strong><?php echo esc($unit); ?></span>
                    <span><strong>Tingkat</strong><?php echo esc($level); ?></span>
                    <span><strong>Tahun</strong><?php echo esc($a['year'] ?: date('Y')); ?></span>
                </div>
                <div class="card-body achievement-detail-body">
                    <p><?php echo esc($a['description']); ?></p>
                    <?php if($a['link_url']): ?><a class="program-link" href="<?php echo esc($a['link_url']); ?>" target="_blank" rel="noopener"><?php echo esc($a['link_label'] ?: 'Lihat Publikasi'); ?> &rarr;</a><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.achievement-tabs').forEach((tabs) => {
        const cards = tabs.parentElement.querySelectorAll('.achieve-card[data-achievement-unit]');
        tabs.querySelectorAll('[data-achievement-filter]').forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.achievementFilter;
                tabs.querySelectorAll('[data-achievement-filter]').forEach((tab) => tab.classList.remove('active'));
                button.classList.add('active');

                cards.forEach((card) => {
                    const isVisible = filter === 'semua' || card.dataset.achievementUnit === filter;
                    window.clearTimeout(card._achievementFilterTimer);
                    if (isVisible) {
                        card.classList.remove('is-hidden');
                        requestAnimationFrame(() => card.classList.remove('is-faded'));
                    } else {
                        card.classList.add('is-faded');
                        card._achievementFilterTimer = window.setTimeout(() => card.classList.add('is-hidden'), 240);
                    }
                });
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
