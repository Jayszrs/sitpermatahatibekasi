<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Galeri';

$stmt = $pdo->query("
    SELECT a.*,
        (SELECT COUNT(*) FROM gallery_photos p WHERE p.album_id = a.id) AS photo_count
    FROM gallery_albums a
    WHERE a.is_active = 1 AND a.slug <> 'publikasi-unit'
    ORDER BY a.sort_order ASC, a.created_at DESC, a.id DESC
");
$albums = $stmt->fetchAll();

$photoStmt = $pdo->prepare("
    SELECT title, image
    FROM gallery_photos
    WHERE album_id = ?
    ORDER BY sort_order ASC, created_at DESC, id DESC
    LIMIT 5
");
$albumSlides = [];
foreach ($albums as $album) {
    $photoStmt->execute([(int)$album['id']]);
    $albumSlides[(int)$album['id']] = $photoStmt->fetchAll();
}

$publications = $pdo->query("SELECT * FROM gallery_photos
    WHERE unit_slug IN ('daycare','tkit','sdit','smpit')
      AND instagram_url IS NOT NULL AND instagram_url<>''
    ORDER BY COALESCE(published_at,DATE(created_at)) DESC, sort_order,id DESC")->fetchAll();
$publicationUnits = ['semua'=>'Semua','daycare'=>'Daycare','tkit'=>'TKIT','sdit'=>'SDIT','smpit'=>'SMPIT'];

require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Galeri Sekolah</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Galeri</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head gallery-publication-head">
            <span class="section-eyebrow">Publikasi Instagram</span>
            <h2>Cerita Terbaru dari Setiap Unit</h2>
            <p>Pilih jenjang untuk melihat dokumentasi Daycare, TKIT, SDIT, atau SMPIT, lalu buka publikasi aslinya di Instagram.</p>
        </div>
        <div class="achievement-tabs gallery-unit-tabs" aria-label="Filter publikasi berdasarkan unit">
            <?php foreach ($publicationUnits as $slug => $label): ?><button type="button" class="achievement-tab<?php echo $slug==='semua'?' active':''; ?>" data-gallery-unit-filter="<?php echo esc($slug); ?>"><?php echo esc($label); ?></button><?php endforeach; ?>
        </div>
        <div class="gallery-publication-grid">
            <?php foreach ($publications as $publication): ?>
            <a class="gallery-publication-card" data-gallery-unit="<?php echo esc($publication['unit_slug']); ?>" href="<?php echo esc($publication['instagram_url']); ?>" target="_blank" rel="noopener">
                <div class="gallery-publication-media"><img src="<?php echo esc($publication['image']); ?>" alt="<?php echo esc($publication['title']); ?>" loading="lazy"><span><?php echo esc(strtoupper($publication['unit_slug'])); ?></span></div>
                <div class="gallery-publication-copy"><small><?php echo esc(tanggal_indo($publication['published_at'] ?: $publication['created_at'])); ?></small><h3><?php echo esc($publication['title']); ?></h3><p><?php echo esc($publication['description'] ?: 'Lihat dokumentasi kegiatan terbaru unit sekolah.'); ?></p><strong>Buka di Instagram &nearr;</strong></div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="section-head gallery-album-head"><span class="section-eyebrow">Album Sekolah</span><h2>Jelajahi Dokumentasi Lengkap</h2></div>
        <?php if (count($albums) > 0): ?>
        <div class="album-grid">
            <?php foreach ($albums as $album): ?>
            <?php $slides = $albumSlides[(int)$album['id']] ?? []; ?>
            <a href="galeri-detail.php?id=<?php echo (int)$album['id']; ?>" class="gallery-album-card">
                <div class="album-carousel" data-album-carousel>
                    <?php if ($slides): ?>
                        <?php foreach ($slides as $index => $slide): ?>
                        <img class="album-slide<?php echo $index === 0 ? ' active' : ''; ?>" src="<?php echo esc($slide['image']); ?>" alt="<?php echo esc($slide['title']); ?>" loading="lazy">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="album-empty-cover">Belum ada foto</div>
                    <?php endif; ?>
                </div>
                <div class="album-card-content">
                    <span class="album-count"><?php echo (int)$album['photo_count']; ?> Foto</span>
                    <h2><?php echo esc($album['title']); ?></h2>
                    <?php if (!empty($album['description'])): ?>
                    <p><?php echo esc($album['description']); ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="text-align:center; color: var(--muted);">Belum ada album galeri.</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterButtons = document.querySelectorAll('[data-gallery-unit-filter]');
    const publicationCards = document.querySelectorAll('[data-gallery-unit]');
    filterButtons.forEach((button) => button.addEventListener('click', () => {
        const filter = button.dataset.galleryUnitFilter;
        filterButtons.forEach((item) => item.classList.toggle('active', item === button));
        publicationCards.forEach((card) => card.classList.toggle('is-hidden', filter !== 'semua' && card.dataset.galleryUnit !== filter));
    }));

    document.querySelectorAll('[data-album-carousel]').forEach((carousel) => {
        const slides = carousel.querySelectorAll('.album-slide');
        if (slides.length <= 1) return;
        let activeIndex = 0;
        window.setInterval(() => {
            slides[activeIndex].classList.remove('active');
            activeIndex = (activeIndex + 1) % slides.length;
            slides[activeIndex].classList.add('active');
        }, 3200);
    });
});
</script>
