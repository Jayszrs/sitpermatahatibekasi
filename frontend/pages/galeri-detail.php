<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM gallery_albums WHERE id = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$id]);
$album = $stmt->fetch();

if (!$album) {
    header('Location: galeri.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM gallery_photos
    WHERE album_id = ?
    ORDER BY sort_order ASC, created_at DESC, id DESC
");
$stmt->execute([(int)$album['id']]);
$photos = $stmt->fetchAll();
foreach ($photos as &$photoItem) $photoItem['image'] = public_media_url($photoItem['image'] ?? null);
unset($photoItem);

$lightboxPhotos = array_map(static function (array $photo): array {
    return [
        'title' => $photo['title'],
        'image' => $photo['image'],
        'description' => $photo['description'] ?: '',
    ];
}, $photos);

$page_title = $album['title'];
require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1><?php echo esc($album['title']); ?></h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / <a href="galeri.php">Galeri</a> / <?php echo esc($album['title']); ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="album-detail-head">
            <span class="section-eyebrow">Album Galeri</span>
            <h2><?php echo esc($album['title']); ?></h2>
            <?php if (!empty($album['description'])): ?>
            <p><?php echo esc($album['description']); ?></p>
            <?php endif; ?>
            <a href="galeri.php" class="btn btn-outline btn-sm">Kembali ke Galeri</a>
        </div>

        <?php if (count($photos) > 0): ?>
        <div class="album-photo-grid">
            <?php foreach ($photos as $index => $photo): ?>
            <button type="button" class="album-photo-item" data-lightbox-index="<?php echo (int)$index; ?>">
                <img src="<?php echo esc($photo['image']); ?>" alt="<?php echo esc($photo['title']); ?>" loading="lazy">
                <span><?php echo esc($photo['title']); ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="text-align:center; color: var(--muted);">Belum ada foto di album ini.</p>
        <?php endif; ?>
    </div>
</section>

<div class="album-lightbox" id="albumLightbox" aria-hidden="true">
    <button type="button" class="album-lightbox-backdrop" data-album-close aria-label="Tutup preview foto"></button>
    <div class="album-lightbox-dialog" role="dialog" aria-modal="true" aria-label="Preview foto album">
        <button type="button" class="album-lightbox-close" data-album-close aria-label="Tutup preview foto">&times;</button>
        <button type="button" class="album-lightbox-nav album-lightbox-prev" data-album-prev aria-label="Foto sebelumnya">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <img src="" alt="" id="albumLightboxImage">
        <button type="button" class="album-lightbox-nav album-lightbox-next" data-album-next aria-label="Foto berikutnya">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </button>
        <div class="album-lightbox-caption">
            <strong id="albumLightboxTitle"></strong>
            <span id="albumLightboxDescription"></span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script>
const albumPhotos = <?php echo json_encode($lightboxPhotos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

document.addEventListener('DOMContentLoaded', () => {
    const lightbox = document.getElementById('albumLightbox');
    const image = document.getElementById('albumLightboxImage');
    const title = document.getElementById('albumLightboxTitle');
    const description = document.getElementById('albumLightboxDescription');
    let activeIndex = 0;

    const showPhoto = (index) => {
        if (!albumPhotos.length) return;
        activeIndex = (index + albumPhotos.length) % albumPhotos.length;
        const photo = albumPhotos[activeIndex];
        image.src = photo.image;
        image.alt = photo.title;
        title.textContent = photo.title;
        description.textContent = photo.description || '';
    };

    const openLightbox = (index) => {
        showPhoto(index);
        lightbox.classList.add('open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeLightbox = () => {
        lightbox.classList.remove('open');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        image.src = '';
    };

    document.querySelectorAll('[data-lightbox-index]').forEach((button) => {
        button.addEventListener('click', () => openLightbox(Number(button.dataset.lightboxIndex || 0)));
    });
    document.querySelectorAll('[data-album-close]').forEach((button) => button.addEventListener('click', closeLightbox));
    document.querySelector('[data-album-prev]')?.addEventListener('click', () => showPhoto(activeIndex - 1));
    document.querySelector('[data-album-next]')?.addEventListener('click', () => showPhoto(activeIndex + 1));

    document.addEventListener('keydown', (event) => {
        if (!lightbox.classList.contains('open')) return;
        if (event.key === 'Escape') closeLightbox();
        if (event.key === 'ArrowLeft') showPhoto(activeIndex - 1);
        if (event.key === 'ArrowRight') showPhoto(activeIndex + 1);
    });
});
</script>
