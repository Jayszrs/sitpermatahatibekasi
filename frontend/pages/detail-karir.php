<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';

$slug = trim($_GET['slug'] ?? '');
$current_page = 'karir.php';
$stmt = $pdo->prepare("SELECT * FROM job_vacancies WHERE slug=? AND is_active=1 AND (deadline IS NULL OR deadline>=CURDATE()) LIMIT 1");
$stmt->execute([$slug]);
$job = $stmt->fetch();
if (!$job) {
    http_response_code(404);
    $page_title = 'Lowongan Tidak Ditemukan';
    require_once __DIR__ . '/../components/header.php';
    ?><section class="page-header"><div class="container"><h1>Lowongan Tidak Ditemukan</h1><p class="breadcrumb"><a href="karir.php">Karir</a> / Tidak tersedia</p></div></section><section class="section"><div class="container empty-public-state"><h2>Posisi ini sudah ditutup atau tidak tersedia.</h2><a class="btn btn-primary" href="karir.php">Lihat Lowongan Lain</a></div></section><?php require_once __DIR__ . '/../components/footer.php'; return;
}

$errors = [];
$old = ['full_name'=>'','email'=>'','phone'=>'','city'=>'','education'=>'','experience_years'=>'0','portfolio_url'=>'','cover_letter'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($old) as $key) $old[$key] = trim($_POST[$key] ?? $old[$key]);
    try {
        public_verify_csrf($_POST['_token'] ?? null);
        if (trim($_POST['website'] ?? '') !== '') throw new RuntimeException('Lamaran tidak dapat diproses.');
        if ($old['full_name']==='' || $old['phone']==='' || $old['cover_letter']==='') throw new RuntimeException('Nama, nomor WhatsApp, dan pengantar lamaran wajib diisi.');
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Alamat email tidak valid.');
        $portfolioScheme = $old['portfolio_url'] !== '' ? strtolower((string)parse_url($old['portfolio_url'], PHP_URL_SCHEME)) : '';
        if ($old['portfolio_url']!=='' && (!filter_var($old['portfolio_url'], FILTER_VALIDATE_URL) || !in_array($portfolioScheme, ['http','https'], true))) throw new RuntimeException('Tautan portofolio harus menggunakan http atau https.');
        $cv = upload_career_document($_FILES['cv'] ?? []);
        try {
            $insert = $pdo->prepare('INSERT INTO job_applications (vacancy_id,full_name,email,phone,city,education,experience_years,cover_letter,cv_file,cv_original_name,portfolio_url) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $insert->execute([$job['id'],$old['full_name'],$old['email'],$old['phone'],$old['city']?:null,$old['education']?:null,max(0,(float)$old['experience_years']),$old['cover_letter'],$cv['url'],$cv['name'],$old['portfolio_url']?:null]);
        } catch (Throwable $e) {
            $path = dirname(__DIR__) . '/assets/uploads/careers/' . basename((string)parse_url($cv['url'], PHP_URL_PATH));
            if (is_file($path)) unlink($path);
            throw $e;
        }
        header('Location: ' . SITE_URL . '/detail-karir.php?slug=' . urlencode($job['slug']) . '&submitted=1');
        exit;
    } catch (Throwable $e) { $errors[] = $e->getMessage(); }
}

$relatedStmt = $pdo->prepare("SELECT title,slug,unit,employment_type FROM job_vacancies WHERE id<>? AND is_active=1 AND (deadline IS NULL OR deadline>=CURDATE()) ORDER BY is_featured DESC,created_at DESC LIMIT 3");
$relatedStmt->execute([$job['id']]);
$relatedJobs = $relatedStmt->fetchAll();
$page_title = $job['title'] . ' - Karir';
$meta_description = $job['summary'];
$meta_image = public_media_url($job['image'] ?? null);
$meta_type = 'article';
$canonical_url = SITE_URL.'/detail-karir.php?slug='.rawurlencode($job['slug']);
$shareUrl = urlencode($canonical_url); $shareText = urlencode('Lowongan '.$job['title'].' di '.SITE_NAME);
$csrf = public_form_csrf_token();
require_once __DIR__ . '/../components/header.php';
?>

<section class="job-detail-hero"><div class="container"><a class="back-link" href="karir.php">&larr; Kembali ke daftar lowongan</a><div class="job-detail-heading"><div class="job-logo large"><img src="<?php echo esc(asset_url('frontend/assets/images/logo-sit-round.png')); ?>" alt="Logo SIT Permata Hati Bekasi"></div><div><span><?php echo esc($job['department']); ?></span><h1><?php echo esc($job['title']); ?></h1><div class="job-meta"><span><?php echo esc($job['unit']); ?></span><span><?php echo esc($job['employment_type']); ?></span><span><?php echo esc($job['work_location']); ?></span></div></div><a class="btn btn-gold" href="#lamar">Lamar Posisi Ini</a></div></div></section>

<section class="job-detail-section"><div class="container job-detail-layout">
    <main class="job-detail-content">
        <section><h2>Tentang Posisi</h2><p><?php echo nl2br(esc($job['description'])); ?></p><div class="social-share"><span>Bagikan lowongan</span><a target="_blank" rel="noopener" href="https://wa.me/?text=<?php echo $shareText.'%20'.$shareUrl; ?>">WhatsApp</a><a target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>">Facebook</a><a target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $shareUrl; ?>">LinkedIn</a><button type="button" data-native-share data-share-title="<?php echo esc($job['title']); ?>" data-share-url="<?php echo esc($canonical_url); ?>">Lainnya</button></div></section>
        <section><h2>Tanggung Jawab</h2><ul class="job-detail-list"><?php foreach(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/', $job['responsibilities']))) as $item): ?><li><?php echo esc($item); ?></li><?php endforeach; ?></ul></section>
        <section><h2>Kualifikasi</h2><ul class="job-detail-list"><?php foreach(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/', $job['requirements']))) as $item): ?><li><?php echo esc($item); ?></li><?php endforeach; ?></ul></section>
        <?php if($job['benefits']): ?><section><h2>Benefit</h2><ul class="job-detail-list benefit"><?php foreach(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/', $job['benefits']))) as $item): ?><li><?php echo esc($item); ?></li><?php endforeach; ?></ul></section><?php endif; ?>
    </main>
    <aside class="job-detail-sidebar"><div><h3>Ringkasan Pekerjaan</h3><dl><dt>Unit</dt><dd><?php echo esc($job['unit']); ?></dd><dt>Tipe</dt><dd><?php echo esc($job['employment_type']); ?></dd><dt>Lokasi</dt><dd><?php echo esc($job['work_location']); ?></dd><dt>Pendidikan</dt><dd><?php echo esc($job['education'] ?: 'Menyesuaikan posisi'); ?></dd><dt>Pengalaman</dt><dd><?php echo esc($job['experience'] ?: 'Terbuka'); ?></dd><dt>Kompensasi</dt><dd><?php echo esc($job['salary_note'] ?: 'Kompetitif'); ?></dd><dt>Batas lamaran</dt><dd><?php echo $job['deadline'] ? esc(tanggal_indo($job['deadline'])) : 'Sampai posisi terpenuhi'; ?></dd></dl><a class="btn btn-primary btn-block" href="#lamar">Kirim Lamaran</a></div></aside>
</div></section>

<section class="job-apply-section" id="lamar"><div class="container job-apply-grid"><div><span class="section-eyebrow">Form Lamaran</span><h2>Lamar sebagai <?php echo esc($job['title']); ?></h2><p>Lengkapi data dengan benar. Tim rekrutmen akan menghubungi kandidat yang sesuai melalui email atau WhatsApp.</p><div class="application-privacy"><strong>Data Anda aman</strong><span>Informasi hanya digunakan untuk proses rekrutmen SIT Permata Hati Bekasi.</span></div></div><div class="application-form-card">
    <?php if(isset($_GET['submitted'])): ?><div class="alert alert-success"><strong>Lamaran berhasil dikirim.</strong><br>Terima kasih. Tim kami akan meninjau profil Anda.</div><?php endif; ?>
    <?php if($errors): ?><div class="alert alert-error"><?php foreach($errors as $error): ?><?php echo esc($error); ?><br><?php endforeach; ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="application-form"><input type="hidden" name="_token" value="<?php echo esc($csrf); ?>"><div class="application-honeypot"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-row"><div class="form-group"><label for="full_name">Nama lengkap *</label><input class="form-control" id="full_name" name="full_name" value="<?php echo esc($old['full_name']); ?>" required></div><div class="form-group"><label for="email">Email *</label><input class="form-control" type="email" id="email" name="email" value="<?php echo esc($old['email']); ?>" required></div></div>
        <div class="form-row"><div class="form-group"><label for="phone">Nomor WhatsApp *</label><input class="form-control" id="phone" name="phone" value="<?php echo esc($old['phone']); ?>" required></div><div class="form-group"><label for="city">Domisili</label><input class="form-control" id="city" name="city" value="<?php echo esc($old['city']); ?>"></div></div>
        <div class="form-row"><div class="form-group"><label for="education">Pendidikan terakhir</label><input class="form-control" id="education" name="education" value="<?php echo esc($old['education']); ?>" placeholder="Contoh: S1 PGSD"></div><div class="form-group"><label for="experience_years">Pengalaman kerja (tahun)</label><input class="form-control" type="number" min="0" max="50" step="0.5" id="experience_years" name="experience_years" value="<?php echo esc($old['experience_years']); ?>"></div></div>
        <div class="form-group"><label for="portfolio_url">Tautan portofolio/LinkedIn</label><input class="form-control" type="url" id="portfolio_url" name="portfolio_url" value="<?php echo esc($old['portfolio_url']); ?>" placeholder="https://"></div>
        <div class="form-group"><label for="cover_letter">Pengantar lamaran *</label><textarea class="form-control" id="cover_letter" name="cover_letter" rows="6" required placeholder="Ceritakan pengalaman dan alasan Anda tertarik dengan posisi ini."><?php echo esc($old['cover_letter']); ?></textarea></div>
        <div class="form-group"><label for="cv">CV terbaru *</label><input class="form-control file-input" type="file" id="cv" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required><small>PDF, DOC, atau DOCX. Maksimal 5 MB.</small></div>
        <button class="btn btn-primary btn-block" type="submit">Kirim Lamaran Sekarang</button>
    </form>
</div></div></section>

<?php if($relatedJobs): ?><section class="section"><div class="container"><div class="section-head"><span class="section-eyebrow">Lowongan Lain</span><h2>Posisi yang Mungkin Cocok</h2></div><div class="related-job-grid"><?php foreach($relatedJobs as $related): ?><a href="detail-karir.php?slug=<?php echo urlencode($related['slug']); ?>"><span><?php echo esc($related['unit']); ?> &middot; <?php echo esc($related['employment_type']); ?></span><h3><?php echo esc($related['title']); ?></h3><strong>Lihat detail &rarr;</strong></a><?php endforeach; ?></div></div></section><?php endif; ?>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
