<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';

$advantages = school_advantages();
$current_page = 'index.php';
$slug = trim($_GET['slug'] ?? '');
if (!isset($advantages[$slug])) {
    http_response_code(404);
    $page_title = 'Keunggulan Tidak Ditemukan';
    require_once __DIR__ . '/../components/header.php';
    ?>
    <section class="page-header"><div class="container"><h1>Keunggulan Tidak Ditemukan</h1><p class="breadcrumb"><a href="index.php">Beranda</a> / Keunggulan</p></div></section>
    <section class="section"><div class="container empty-public-state"><h2>Halaman yang Anda cari tidak tersedia.</h2><a class="btn btn-primary" href="index.php#keunggulan">Kembali ke Beranda</a></div></section>
    <?php require_once __DIR__ . '/../components/footer.php'; return;
}

$advantage = $advantages[$slug];
$page_title = $advantage['title'];
require_once __DIR__ . '/../components/header.php';
?>

<section class="advantage-hero">
    <div class="container advantage-hero-grid">
        <div>
            <a class="back-link" href="index.php#keunggulan">&larr; Kembali ke semua keunggulan</a>
            <span class="section-eyebrow">Keunggulan <?php echo esc($advantage['number']); ?></span>
            <h1><?php echo esc($advantage['title']); ?></h1>
            <p><?php echo esc($advantage['intro']); ?></p>
            <div class="hero-actions advantage-actions"><a class="btn btn-primary" href="spmb.php">Lihat Informasi SPMB</a><a class="btn btn-outline" href="kontak.php">Kunjungi Sekolah</a></div>
        </div>
        <div class="advantage-highlight" aria-hidden="true"><span><?php echo esc($advantage['number']); ?></span><strong><?php echo esc($advantage['title']); ?></strong></div>
    </div>
</section>

<section class="section section-alt">
    <div class="container advantage-detail-grid">
        <div>
            <span class="section-eyebrow">Yang Kami Hadirkan</span>
            <h2>Pengalaman Pendidikan yang Terarah</h2>
            <p class="advantage-lead"><?php echo esc($advantage['summary']); ?> Setiap program dijalankan secara konsisten, dievaluasi, dan disesuaikan dengan tahap perkembangan siswa.</p>
        </div>
        <div class="advantage-points">
            <?php foreach ($advantage['points'] as $index => $point): ?>
            <div><span><?php echo str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT); ?></span><p><?php echo esc($point); ?></p></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head"><span class="section-eyebrow">Keunggulan Lainnya</span><h2>Jelajahi Lebih Banyak</h2></div>
        <div class="advantage-nav-grid">
            <?php foreach ($advantages as $otherSlug => $other): if ($otherSlug === $slug) continue; ?>
            <a href="keunggulan.php?slug=<?php echo urlencode($otherSlug); ?>"><span><?php echo esc($other['number']); ?></span><strong><?php echo esc($other['title']); ?></strong><small><?php echo esc($other['summary']); ?></small></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
