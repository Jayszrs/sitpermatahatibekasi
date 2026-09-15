<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';

$activityId = (int)($_GET['id'] ?? 0);
$activityStmt = $pdo->prepare("SELECT * FROM site_content_items WHERE id=? AND type='activity' AND is_active=1 LIMIT 1");
$activityStmt->execute([$activityId]);
$activity = $activityStmt->fetch() ?: null;

if (!$activity) {
    http_response_code(404);
    $page_title = 'Kegiatan Tidak Ditemukan';
    require_once __DIR__ . '/../components/header.php';
    ?>
    <section class="page-header"><div class="container"><h1>Kegiatan Tidak Ditemukan</h1><p class="breadcrumb"><a href="index.php">Beranda</a> / Kegiatan</p></div></section>
    <section class="section"><div class="container empty-state"><p>Kegiatan yang kamu cari sudah tidak tersedia.</p><a class="btn btn-primary" href="kegiatan.php">Lihat kegiatan lainnya</a></div></section>
    <?php
    require_once __DIR__ . '/../components/footer.php';
    return;
}

$unitSlug = strtolower(trim((string)($activity['unit_slug'] ?: $activity['subtitle'])));
$unitSlug = ['tk' => 'tkit', 'sd' => 'sdit', 'smp' => 'smpit'][$unitSlug] ?? $unitSlug;
if (!in_array($unitSlug, ['daycare','tkit','sdit','smpit'], true)) $unitSlug = '';

$documentation = [];
if ($unitSlug !== '') {
    $documentationStmt = $pdo->prepare('SELECT * FROM unit_gallery_photos WHERE unit_slug=? AND is_active=1 ORDER BY sort_order,id LIMIT 6');
    $documentationStmt->execute([$unitSlug]);
    $documentation = $documentationStmt->fetchAll();
}

$page_title = $activity['title'];
require_once __DIR__ . '/../components/header.php';
$articleBody = trim((string)($activity['extra'] ?: $activity['description']));
$activityImage = public_media_url($activity['image'] ?? null, '');
?>

<section class="page-header activity-detail-header">
    <div class="container">
        <span class="section-eyebrow">Kegiatan <?php echo esc($activity['subtitle'] ?: 'Sekolah'); ?></span>
        <h1><?php echo esc($activity['title']); ?></h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / <a href="kegiatan.php">Kegiatan</a> / <?php echo esc($activity['title']); ?></p>
    </div>
</section>

<section class="section activity-article-section">
    <div class="container activity-article-layout">
        <article class="activity-article">
            <?php if ($activityImage !== ''): ?>
                <img class="activity-article-cover" src="<?php echo esc($activityImage); ?>" data-fallback="<?php echo SITE_URL; ?>/frontend/assets/images/school/gedung-sekolah.optimized.webp" alt="Dokumentasi <?php echo esc($activity['title']); ?>" decoding="async">
            <?php endif; ?>
            <div class="activity-article-content">
                <p class="activity-article-lead"><?php echo esc($activity['description']); ?></p>
                <?php if ($articleBody !== $activity['description']): ?>
                    <?php foreach (preg_split('/\R{2,}/', $articleBody) as $paragraph): ?>
                        <?php if (trim($paragraph) !== ''): ?><p><?php echo nl2br(esc(trim($paragraph))); ?></p><?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <aside class="activity-article-aside">
            <span class="section-eyebrow">Informasi</span>
            <h2>Tentang kegiatan</h2>
            <dl>
                <div><dt>Unit</dt><dd><?php echo esc($activity['subtitle'] ?: 'SIT Permata Hati'); ?></dd></div>
                <?php if (!empty($activity['year'])): ?><div><dt>Tahun</dt><dd><?php echo esc($activity['year']); ?></dd></div><?php endif; ?>
                <div><dt>Publikasi</dt><dd>Artikel resmi website</dd></div>
            </dl>
            <a class="btn btn-outline btn-block" href="kegiatan.php">Kembali ke semua kegiatan</a>
        </aside>
    </div>
</section>

<section class="section section-alt activity-documentation-section">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Dokumentasi</span>
            <h2>Potret Kegiatan</h2>
            <p>Dokumentasi resmi kegiatan yang dikelola langsung melalui galeri website.</p>
        </div>
        <div class="activity-documentation-grid">
            <?php foreach ($documentation as $photo): ?>
                <figure>
                    <img src="<?php echo esc(public_media_url($photo['image'] ?? null)); ?>" alt="<?php echo esc($photo['title']); ?>" loading="lazy" decoding="async">
                    <figcaption><strong><?php echo esc($photo['title']); ?></strong><?php if (!empty($photo['description'])): ?><span><?php echo esc($photo['description']); ?></span><?php endif; ?></figcaption>
                </figure>
            <?php endforeach; ?>
            <?php if (!$documentation): ?>
                <div class="activity-documentation-empty">
                    <strong>Dokumentasi tambahan sedang disiapkan</strong>
                    <p>Foto utama di atas merupakan dokumentasi kegiatan. Foto lain dapat ditambahkan melalui menu Galeri Unit di portal.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
