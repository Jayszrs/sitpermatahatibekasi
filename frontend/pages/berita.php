<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Berita';

$stmt = $pdo->query("SELECT * FROM news ORDER BY published_at DESC");
$news_list = $stmt->fetchAll();

require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Berita &amp; Informasi</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Berita</p>
    </div>
</section>

<section class="section news-index-section">
    <div class="container">
        <?php if (count($news_list) > 0): ?>
        <?php $featured=array_shift($news_list); ?>
        <a class="news-featured-card" href="detail-berita.php?slug=<?php echo urlencode($featured['slug']); ?>"><div class="news-featured-image"><img src="<?php echo esc($featured['image']); ?>" alt="<?php echo esc($featured['title']); ?>"><span>Berita Utama</span></div><div><div class="news-date"><?php echo tanggal_indo($featured['published_at']); ?></div><h2><?php echo esc($featured['title']); ?></h2><p><?php echo esc($featured['excerpt']); ?></p><strong>Baca cerita lengkap &rarr;</strong></div></a>
        <?php if($news_list): ?><div class="news-index-head"><div><span class="section-eyebrow">Informasi Lainnya</span><h2>Berita Sekolah</h2></div><p>Dokumentasi kegiatan, pencapaian, dan informasi terbaru SIT Permata Hati Bekasi.</p></div><div class="news-modern-grid">
            <?php foreach ($news_list as $news): ?><article class="news-modern-card"><a class="news-modern-image" href="detail-berita.php?slug=<?php echo urlencode($news['slug']); ?>"><img src="<?php echo esc($news['image']); ?>" alt="<?php echo esc($news['title']); ?>" loading="lazy"></a><div><div class="news-date"><?php echo tanggal_indo($news['published_at']); ?></div><h3><a href="detail-berita.php?slug=<?php echo urlencode($news['slug']); ?>"><?php echo esc($news['title']); ?></a></h3><p><?php echo esc(mb_strimwidth($news['excerpt'],0,145,'...')); ?></p><a href="detail-berita.php?slug=<?php echo urlencode($news['slug']); ?>" class="news-link">Baca Selengkapnya &rarr;</a></div></article><?php endforeach; ?>
        </div><?php endif; ?>
        <?php else: ?>
        <p style="text-align:center; color: var(--muted);">Belum ada berita.</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
