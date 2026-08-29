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
$advantageImages = [
    'pendidikan-islami' => SITE_URL.'/frontend/assets/images/gallery/masjid-sekolah/masjid-01.jpeg',
    'guru-profesional' => SITE_URL.'/frontend/assets/images/brochures/tkit-promo.png',
    'kurikulum-berkualitas' => SITE_URL.'/frontend/assets/images/brochures/sdit-promo.png',
    'lingkungan-nyaman' => SITE_URL.'/frontend/assets/images/school/hero-school.png',
    'fasilitas-lengkap' => SITE_URL.'/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-03.jpeg',
    'pengembangan-karakter' => SITE_URL.'/frontend/assets/images/brochures/smpit-promo.png',
];
$advantageImage=$advantageImages[$slug];
$page_title = $advantage['title'];
$meta_description = $advantage['intro'];
$meta_image = $advantageImage;
$canonical_url = SITE_URL.'/keunggulan.php?slug='.rawurlencode($slug);
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
        <button type="button" class="advantage-highlight image-preview-trigger" data-lightbox-src="<?php echo esc($advantageImage); ?>" data-lightbox-title="<?php echo esc($advantage['title']); ?>" style="--advantage-image:url('<?php echo esc($advantageImage); ?>')"><span><?php echo esc($advantage['number']); ?></span><strong><?php echo esc($advantage['title']); ?></strong><small>Lihat suasana pembelajaran</small></button>
    </div>
</section>

<section class="section advantage-process-section"><div class="container"><div class="section-head"><span class="section-eyebrow">Dari Prinsip Menjadi Pengalaman</span><h2>Bagaimana keunggulan ini hadir setiap hari</h2><p>Kualitas sekolah dibangun melalui perencanaan, pelaksanaan, komunikasi, dan evaluasi yang konsisten.</p></div><div class="advantage-process-grid"><article><span>01</span><h3>Dirancang</h3><p>Target perkembangan diterjemahkan menjadi aktivitas yang sesuai usia dan kebutuhan siswa.</p></article><article><span>02</span><h3>Dijalankan</h3><p>Guru mendampingi proses dengan metode aktif, hangat, dan tetap terarah.</p></article><article><span>03</span><h3>Diamati</h3><p>Perkembangan dicatat agar dukungan berikutnya berbasis kebutuhan nyata.</p></article><article><span>04</span><h3>Dikomunikasikan</h3><p>Orang tua memperoleh gambaran proses dan dapat melanjutkan pembiasaan di rumah.</p></article></div></div></section>

<section class="section section-alt"><div class="container advantage-result-card"><div><span class="section-eyebrow">Hasil yang Dituju</span><h2>Pengalaman sekolah yang terasa bagi anak dan orang tua</h2></div><p><?php echo esc($advantage['intro']); ?> Pendekatan ini membantu siswa belajar dengan rasa aman, memahami tujuan, serta tumbuh menjadi pribadi yang bertanggung jawab.</p><a class="btn btn-primary" href="form-spmb.php">Pilih Jenjang &amp; Daftar</a></div></section>

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
