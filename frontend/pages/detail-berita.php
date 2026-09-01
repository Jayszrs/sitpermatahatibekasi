<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

$stmt = $pdo->prepare("SELECT * FROM news WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$news = $stmt->fetch();

if (!$news) {
    header('Location: berita.php');
    exit;
}
$news['image'] = public_media_url($news['image'] ?? null);

// Berita lain (untuk rekomendasi)
$stmt2 = $pdo->prepare("SELECT * FROM news WHERE id != ? ORDER BY CASE WHEN unit=? THEN 0 ELSE 1 END, published_at DESC LIMIT 3");
$stmt2->execute([$news['id'], $news['unit'] ?? 'SDIT']);
$other_news = $stmt2->fetchAll();
foreach ($other_news as &$otherNewsItem) $otherNewsItem['image'] = public_media_url($otherNewsItem['image'] ?? null);
unset($otherNewsItem);

$page_title = $news['title'];
$meta_description = $news['excerpt'] ?: mb_strimwidth(strip_tags($news['content']), 0, 180, '...');
$meta_image = $news['image'];
$meta_type = 'article';
$canonical_url = SITE_URL . '/detail-berita.php?slug=' . rawurlencode($news['slug']);
$shareUrl = urlencode($canonical_url);
$shareText = urlencode($news['title']);
require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1><?php echo esc($news['title']); ?></h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / <a href="berita.php">Berita</a> / <?php echo esc(mb_strimwidth($news['title'], 0, 40, '...')); ?></p>
    </div>
</section>

<section class="section news-article-section"><div class="container news-article-layout"><article class="news-article"><div class="news-article-meta"><span class="news-date"><?php echo tanggal_indo($news['published_at']); ?></span><span>Unit <?php echo esc($news['unit'] ?? 'SDIT'); ?></span></div><img class="news-article-cover" src="<?php echo esc($news['image']); ?>" alt="<?php echo esc($news['title']); ?>"><div class="news-article-content"><?php echo nl2br(esc($news['content'])); ?></div><div class="social-share"><span>Bagikan informasi</span><a target="_blank" rel="noopener" href="https://wa.me/?text=<?php echo $shareText.'%20'.$shareUrl; ?>">WhatsApp</a><a target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>">Facebook</a><a target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text=<?php echo $shareText; ?>&url=<?php echo $shareUrl; ?>">X</a><a target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $shareUrl; ?>">LinkedIn</a><button type="button" data-native-share data-share-title="<?php echo esc($news['title']); ?>" data-share-url="<?php echo esc($canonical_url); ?>">Lainnya</button></div><div class="news-article-footer"><a href="berita.php" class="btn btn-outline">&larr; Kembali ke Berita</a></div></article><aside class="news-article-aside"><span class="section-eyebrow">Berita <?php echo esc($news['unit'] ?? 'SDIT'); ?></span><h3>Kabar Resmi Unit</h3><p>Ikuti kegiatan dan informasi terbaru dari unit <?php echo esc($news['unit'] ?? 'SDIT'); ?> SIT Permata Hati Bekasi.</p><a class="btn btn-primary btn-block" href="berita.php">Lihat Semua Berita</a></aside></div></section>

<?php if (count($other_news) > 0): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Baca Juga</span>
            <h2>Berita Lainnya</h2>
        </div>
        <div class="grid-3">
            <?php foreach ($other_news as $n): ?>
            <div class="card">
                <img src="<?php echo esc($n['image']); ?>" alt="<?php echo esc($n['title']); ?>" loading="lazy">
                <div class="card-body">
                    <span class="news-unit-chip"><?php echo esc($n['unit'] ?? 'SDIT'); ?></span><div class="news-date"><?php echo tanggal_indo($n['published_at']); ?></div>
                    <h3><?php echo esc($n['title']); ?></h3>
                    <a href="detail-berita.php?slug=<?php echo esc($n['slug']); ?>" class="news-link">Baca Selengkapnya &rarr;</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
