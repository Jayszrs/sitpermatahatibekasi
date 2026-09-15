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
$instagramRows = $pdo->query("SELECT * FROM instagram_gallery WHERE is_active=1 AND media_type='embed' AND scope IN ('daycare','tkit','sdit','smpit') ORDER BY FIELD(scope,'daycare','tkit','sdit','smpit'),sort_order,id LIMIT 32")->fetchAll();
$verifiedRows = instagram_verified_gallery($instagramRows, $instagramAccounts, 24);
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
            <p>Postingan terbaru dari akun resmi Daycare, TKIT, SDIT, dan SMPIT. Klik tombol putar pada reel untuk memutar videonya.</p>
        </div>
        <div class="achievement-tabs gallery-unit-tabs" aria-label="Filter publikasi berdasarkan unit">
            <?php foreach ($publicationUnits as $slug => $label): ?><button type="button" class="achievement-tab<?php echo $slug==='semua'?' active':''; ?>" data-gallery-unit-filter="<?php echo esc($slug); ?>"><?php echo esc($label); ?></button><?php endforeach; ?>
        </div>
        <div class="ig-gallery-grid gallery-instagram-grid">
            <?php foreach ($publications as $publication): ?>
            <?php $media=$publication['public_media']; $isVideo=!empty($media['video']); $caption=($media['caption']??null)?:($publication['caption']?:'Momen terbaru '.strtoupper($publication['scope']).' di Instagram.'); ?>
            <article class="ig-gallery-card ig-native-card" data-ig-card data-ig-state="<?php echo $isVideo?'video':'image'; ?>" data-gallery-unit="<?php echo esc($publication['scope']); ?>">
                <header class="ig-card-head"><span class="ig-card-brand<?php echo !empty($media['profile_image'])?' ig-card-avatar':''; ?>" aria-hidden="true"><?php if(!empty($media['profile_image'])): ?><img src="<?php echo esc($media['profile_image']); ?>" alt="" loading="lazy" decoding="async"><?php else: ?><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r=".8" class="ig-dot"></circle></svg><?php endif; ?></span><span class="ig-card-identity"><strong><?php echo esc($publicationUnits[$publication['scope']]??strtoupper($publication['scope'])); ?></strong><small data-ig-username>@<?php echo esc($media['username']); ?></small></span><a class="ig-card-open" href="<?php echo esc($publication['instagram_url']); ?>" target="_blank" rel="noopener" aria-label="Buka postingan di Instagram">&nearr;</a></header>
                <div class="ig-gallery-media"><img class="ig-media-poster" src="<?php echo esc($media['image']); ?>" alt="<?php echo esc($caption); ?>" loading="lazy" decoding="async"><?php if($isVideo): ?><video class="ig-media-video" data-ig-video-src="<?php echo esc($media['video']); ?>" poster="<?php echo esc($media['image']); ?>" muted loop playsinline controls preload="none"></video><button type="button" class="ig-media-play" data-ig-play aria-label="Putar video"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg></button><?php endif; ?><span class="ig-media-shade" aria-hidden="true"></span><span class="ig-media-kind" data-ig-kind><?php echo $isVideo?'REEL':'POST'; ?></span></div>
                <footer class="ig-gallery-foot"><p data-ig-caption><?php echo esc($caption); ?></p><a href="<?php echo esc($publication['instagram_url']); ?>" target="_blank" rel="noopener"><span>Lihat postingan</span><span aria-hidden="true">&rarr;</span></a></footer>
            </article>
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
