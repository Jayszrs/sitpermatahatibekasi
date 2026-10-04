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
    foreach ($albumSlides[(int)$album['id']] as &$albumPhoto) $albumPhoto['image'] = public_media_url($albumPhoto['image'] ?? null);
    unset($albumPhoto);
}

$instagramAccounts = [
    'daycare' => instagram_profile_username(SITE_DAYCARE_INSTAGRAM) ?: '',
    'tkit' => instagram_profile_username(SITE_TKIT_INSTAGRAM) ?: '',
    'sdit' => instagram_profile_username(SITE_SDIT_INSTAGRAM) ?: '',
    'smpit' => instagram_profile_username(SITE_SMPIT_INSTAGRAM) ?: '',
];
$instagramRows = $pdo->query("SELECT * FROM instagram_gallery WHERE is_active=1 AND media_type='embed' AND scope IN ('daycare','tkit','sdit','smpit') ORDER BY FIELD(scope,'daycare','tkit','sdit','smpit'),sort_order,id")->fetchAll();
$verifiedRows = instagram_verified_gallery($instagramRows, $instagramAccounts);
$instagramGroups = [];
foreach ($verifiedRows as $row) $instagramGroups[$row['scope']][] = $row;
$publications = [];
do {
    $added = false;
    foreach (['daycare','tkit','sdit','smpit'] as $scope) {
        if (!empty($instagramGroups[$scope])) {
            $publications[] = array_shift($instagramGroups[$scope]);
            $added = true;
        }
    }
} while ($added);
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
            <p>Postingan terbaru dari akun resmi Daycare, TKIT, SDIT, dan SMPIT. Buka postingan langsung dari galeri atau kunjungi akun Instagram resminya.</p>
        </div>
        <div class="achievement-tabs gallery-unit-tabs" aria-label="Filter publikasi berdasarkan unit">
            <?php foreach ($publicationUnits as $slug => $label): ?><button type="button" class="achievement-tab<?php echo $slug==='semua'?' active':''; ?>" data-gallery-unit-filter="<?php echo esc($slug); ?>"><?php echo esc($label); ?></button><?php endforeach; ?>
        </div>
        <div class="ig-gallery-grid gallery-instagram-grid">
            <?php foreach ($publications as $publication):
                instagram_embed_card($publication, $publicationUnits[$publication['scope']] ?? strtoupper($publication['scope']), ' data-gallery-unit="'.esc($publication['scope']).'"');
            endforeach; ?>
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

<script src="<?php echo esc(asset_url('frontend/assets/js/instagram-gallery.js')); ?>"></script>
<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterButtons = document.querySelectorAll('[data-gallery-unit-filter]');
    const publicationCards = document.querySelectorAll('[data-gallery-unit]');
    filterButtons.forEach((button) => button.addEventListener('click', () => {
        const filter = button.dataset.galleryUnitFilter;
        filterButtons.forEach((item) => item.classList.toggle('active', item === button));
        publicationCards.forEach((card) => {
            const shouldHide = filter !== 'semua' && card.dataset.galleryUnit !== filter;
            card.hidden = shouldHide;
            card.classList.toggle('is-hidden', shouldHide);
        });
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
