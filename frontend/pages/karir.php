<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Karir & Lowongan Kerja';

$q = trim($_GET['q'] ?? '');
$unit = trim($_GET['unit'] ?? '');
$employmentType = trim($_GET['type'] ?? '');
$where = ["is_active=1", "(deadline IS NULL OR deadline>=CURDATE())"];
$params = [];
if ($q !== '') {
    $where[] = '(title LIKE ? OR department LIKE ? OR summary LIKE ? OR work_location LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($unit !== '') { $where[] = 'unit=?'; $params[] = $unit; }
if ($employmentType !== '') { $where[] = 'employment_type=?'; $params[] = $employmentType; }
$stmt = $pdo->prepare('SELECT * FROM job_vacancies WHERE ' . implode(' AND ', $where) . ' ORDER BY is_featured DESC, deadline ASC, created_at DESC');
$stmt->execute($params);
$jobs = $stmt->fetchAll();
$units = $pdo->query("SELECT DISTINCT unit FROM job_vacancies WHERE is_active=1 ORDER BY unit")->fetchAll(PDO::FETCH_COLUMN);
$types = $pdo->query("SELECT DISTINCT employment_type FROM job_vacancies WHERE is_active=1 ORDER BY employment_type")->fetchAll(PDO::FETCH_COLUMN);
$jobStats = $pdo->query("SELECT COUNT(*) total, COUNT(DISTINCT unit) units, SUM(is_featured=1) featured FROM job_vacancies WHERE is_active=1 AND (deadline IS NULL OR deadline>=CURDATE())")->fetch();
$careerHeroImage = public_media_url($jobs[0]['image'] ?? null, SITE_URL.'/frontend/assets/images/brochures/smpit-promo.png');
$meta_description = 'Temukan lowongan pendidik dan tenaga profesional di SIT Permata Hati Bekasi.';
$meta_image = $careerHeroImage;

require_once __DIR__ . '/../components/header.php';
?>

<section class="career-board-hero">
    <div class="container career-board-hero-grid">
        <div><span class="career-kicker">Karir di SIT Permata Hati Bekasi</span><h1>Tumbuh Bersama,<br><span>Mendidik dengan Makna.</span></h1><p>Temukan peran yang sesuai dengan keahlian Anda dan ikut membangun generasi sholeh, cerdas, mandiri, dan berwawasan global.</p><div class="career-hero-points"><span>Budaya Islami</span><span>Ruang Bertumbuh</span><span>Dampak Nyata</span></div></div>
        <div class="career-hero-visual"><img src="<?php echo esc($careerHeroImage); ?>" alt="Tim pendidikan SIT Permata Hati Bekasi"><div class="career-hero-card"><span>Kesempatan Terbuka</span><strong><?php echo (int)$jobStats['total']; ?> Posisi</strong><p>Di <?php echo (int)$jobStats['units']; ?> unit dan bidang kerja.</p></div></div>
    </div>
    <div class="container">
        <form class="job-search-bar" id="careerSearch" method="get" action="karir.php">
            <label><span>Cari posisi</span><input type="search" name="q" value="<?php echo esc($q); ?>" placeholder="Contoh: Guru, Humas, Al Quran"></label>
            <label><span>Unit</span><select name="unit"><option value="">Semua unit</option><?php foreach ($units as $option): ?><option value="<?php echo esc($option); ?>" <?php echo $unit===$option?'selected':''; ?>><?php echo esc($option); ?></option><?php endforeach; ?></select></label>
            <label><span>Tipe pekerjaan</span><select name="type"><option value="">Semua tipe</option><?php foreach ($types as $option): ?><option value="<?php echo esc($option); ?>" <?php echo $employmentType===$option?'selected':''; ?>><?php echo esc($option); ?></option><?php endforeach; ?></select></label>
            <button class="btn btn-gold" type="submit">Cari Lowongan</button>
        </form>
    </div>
</section>

<section class="job-board-section">
    <div class="container job-board-layout">
        <aside class="job-sidebar"><div><span class="section-eyebrow">Mengapa Kami</span><h2>Lebih dari tempat bekerja</h2><p>Lingkungan yang membantu Anda terus belajar sekaligus memberi dampak bagi pendidikan anak.</p></div><ul><li>Budaya kerja Islami</li><li>Pengembangan kompetensi</li><li>Kolaborasi lintas unit</li><li>Pekerjaan yang bermakna</li></ul><a href="karir.php" class="job-reset-link">Lihat semua posisi &rarr;</a></aside>
        <div class="job-results">
            <div class="job-results-head"><div><span>Lowongan tersedia</span><h2><?php echo count($jobs); ?> posisi ditemukan</h2></div><?php if ($q || $unit || $employmentType): ?><a href="karir.php">Hapus filter</a><?php endif; ?></div>
            <div class="job-list">
                <?php if (!$jobs): ?><div class="job-empty"><h3>Belum ada posisi yang cocok</h3><p>Coba kata kunci atau filter lain. Anda juga dapat kembali memeriksa halaman ini secara berkala.</p><a class="btn btn-primary" href="karir.php">Tampilkan Semua</a></div><?php endif; ?>
                <?php foreach ($jobs as $job): ?>
                <a class="job-card<?php echo $job['is_featured'] ? ' featured' : ''; ?>" href="detail-karir.php?slug=<?php echo urlencode($job['slug']); ?>" aria-label="Lihat detail dan lamar posisi <?php echo esc($job['title']); ?>">
                    <div class="job-logo"><img src="<?php echo esc(asset_url('frontend/assets/images/logo-sit-round.png')); ?>" alt=""></div>
                    <div class="job-card-main"><div class="job-card-top"><?php if($job['is_featured']): ?><span class="job-featured">Prioritas</span><?php endif; ?><span><?php echo esc($job['department']); ?></span></div><h3><?php echo esc($job['title']); ?></h3><p><?php echo esc($job['summary']); ?></p><div class="job-meta"><span><?php echo esc($job['unit']); ?></span><span><?php echo esc($job['employment_type']); ?></span><span><?php echo esc($job['work_location']); ?></span></div></div>
                    <div class="job-card-side"><?php if($job['deadline']): ?><small>Batas lamaran</small><strong><?php echo esc(tanggal_indo($job['deadline'])); ?></strong><?php else: ?><small>Dibuka sampai</small><strong>Posisi terpenuhi</strong><?php endif; ?><span class="job-card-action">Detail &amp; Apply <b>&rarr;</b></span></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="career-process-section"><div class="container"><div class="section-head"><span class="section-eyebrow">Proses Rekrutmen</span><h2>Langkah Bergabung Bersama Kami</h2></div><div class="career-process-grid"><div><span>01</span><h3>Kirim Lamaran</h3><p>Pilih posisi dan lengkapi formulir beserta CV terbaru.</p></div><div><span>02</span><h3>Seleksi Administrasi</h3><p>Tim kami meninjau kesesuaian profil dan kebutuhan posisi.</p></div><div><span>03</span><h3>Wawancara &amp; Tes</h3><p>Kandidat terpilih mengikuti wawancara serta tes sesuai posisi.</p></div><div><span>04</span><h3>Penawaran</h3><p>Kandidat terbaik menerima informasi hasil dan penawaran kerja.</p></div></div></div></section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
