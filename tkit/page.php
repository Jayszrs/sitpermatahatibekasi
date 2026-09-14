<?php
require_once __DIR__ . '/bootstrap.php';
$pdo = unit_db();
require_once __DIR__ . '/layout.php';
$page = $unit_page ?? 'home';
$settings = unit_settings($pdo);

$formError = '';
$spmbOld = [];
$spmbSuccess = false;
$lockedLevel = '';
$karirSlug = '';
$karirErrors = [];
$karirSubmitted = false;
$karirJob = null;
$karirJobs = [];
$karirOld = [];
if ($page === 'spmb') {
    require_once __DIR__ . '/../backend/helpers/functions.php';
    require_once __DIR__ . '/../backend/helpers/spmb_shared.php';
    $lockedLevel = school_unit_catalog()[UNIT_SLUG]['subtitle'] ?? ucfirst(UNIT_SLUG);
    $spmbOld = spmb_empty_fields($lockedLevel);
    $spmbSuccess = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!unit_verify_csrf($_POST['csrf'] ?? '')) {
            $formError = 'Sesi formulir tidak valid. Silakan muat ulang halaman.';
            $spmbOld = spmb_fields_from_post($_POST, $lockedLevel);
        } else {
            require_once __DIR__ . '/../backend/helpers/unit_shared.php';
            $mainPdo = unit_connect_main_site_pdo();
            $spmbResult = spmb_validate_and_save($mainPdo, $_POST, $lockedLevel);
            $spmbOld = $spmbResult['old'];
            if ($spmbResult['success']) $spmbSuccess = true;
            else $formError = implode(' ', $spmbResult['errors']);
        }
    }
}

if ($page === 'karir') {
    require_once __DIR__ . '/../backend/helpers/unit_shared.php';
    $mainPdo = unit_connect_main_site_pdo();
    $karirSlug = trim($_GET['slug'] ?? '');
    $karirErrors = [];
    $karirSubmitted = isset($_GET['submitted']);
    $karirJob = null;
    $karirJobs = [];
    $karirOld = ['full_name'=>'','email'=>'','phone'=>'','city'=>'','education'=>'','experience_years'=>'0','portfolio_url'=>'','cover_letter'=>''];
    if ($karirSlug !== '') {
        $stmt = $mainPdo->prepare("SELECT * FROM job_vacancies WHERE slug=? AND is_active=1 AND (deadline IS NULL OR deadline>=CURDATE()) LIMIT 1");
        $stmt->execute([$karirSlug]);
        $karirJob = $stmt->fetch() ?: null;
        if ($karirJob && $_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach (array_keys($karirOld) as $key) $karirOld[$key] = trim($_POST[$key] ?? $karirOld[$key]);
            try {
                public_verify_csrf($_POST['_token'] ?? null);
                if (trim($_POST['website'] ?? '') !== '') throw new RuntimeException('Lamaran tidak dapat diproses.');
                if ($karirOld['full_name']==='' || $karirOld['phone']==='' || $karirOld['cover_letter']==='') throw new RuntimeException('Nama, nomor WhatsApp, dan pengantar lamaran wajib diisi.');
                if (!filter_var($karirOld['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Alamat email tidak valid.');
                $portfolioScheme = $karirOld['portfolio_url'] !== '' ? strtolower((string) parse_url($karirOld['portfolio_url'], PHP_URL_SCHEME)) : '';
                if ($karirOld['portfolio_url'] !== '' && (!filter_var($karirOld['portfolio_url'], FILTER_VALIDATE_URL) || !in_array($portfolioScheme, ['http','https'], true))) throw new RuntimeException('Tautan portofolio harus menggunakan http atau https.');
                $cv = upload_career_document($_FILES['cv'] ?? []);
                try {
                    $insert = $mainPdo->prepare('INSERT INTO job_applications (vacancy_id,full_name,email,phone,city,education,experience_years,cover_letter,cv_file,cv_original_name,portfolio_url) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                    $insert->execute([$karirJob['id'],$karirOld['full_name'],$karirOld['email'],$karirOld['phone'],$karirOld['city']?:null,$karirOld['education']?:null,max(0,(float)$karirOld['experience_years']),$karirOld['cover_letter'],$cv['url'],$cv['name'],$karirOld['portfolio_url']?:null]);
                } catch (Throwable $e) {
                    $path = app_private_cv_path($cv['url']);
                    if ($path && is_file($path)) unlink($path);
                    throw $e;
                }
                header('Location: karir.php?slug=' . urlencode($karirJob['slug']) . '&submitted=1');
                exit;
            } catch (Throwable $e) { $karirErrors[] = $e->getMessage(); }
        }
    } else {
        $karirJobs = $mainPdo->query("SELECT * FROM job_vacancies WHERE is_active=1 AND (deadline IS NULL OR deadline>=CURDATE()) ORDER BY is_featured DESC, deadline ASC, created_at DESC")->fetchAll();
    }
}

function unit_icon(string $name): string { $icons=['spark'=>'✦','heart'=>'♡','blocks'=>'▦','book'=>'▤','star'=>'★','flag'=>'⚑']; return $icons[$name]??'✦'; }
function unit_cards(array $rows, string $class, string $empty, string $hrefBase = ''): void {
    if (!$rows) { echo '<p class="empty-state">'.unit_e($empty).'</p>'; return; }
    echo '<div class="card-grid '.$class.'">';
    foreach ($rows as $row) {
        $image = unit_media($row['image'] ?? '');
        $tag = $hrefBase !== '' ? 'a' : 'article';
        $hrefAttr = $hrefBase !== '' ? ' href="'.unit_e($hrefBase.'?id='.(int)$row['id']).'"' : '';
        echo '<'.$tag.' class="content-card"'.$hrefAttr.'>'
            .($image ? '<div class="card-image"><img src="'.unit_e($image).'" alt="'.unit_e($row['title']).'" loading="lazy"></div>' : '')
            .'<div class="card-copy">'
            .(!empty($row['meta']) ? '<span class="card-meta">'.unit_e($row['meta']).'</span>' : '')
            .'<h2>'.unit_e($row['title']).'</h2><p>'.unit_e($row['summary'] ?? '').'</p>'
            .($hrefBase !== '' ? '<span class="text-link">Baca selengkapnya &rarr;</span>' : '')
            .'</div></'.$tag.'>';
    }
    echo '</div>';
}
function unit_month_id(int $month): string { return ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$month] ?? ''; }
function unit_tanggal(?string $date): string { if(!$date) return ''; $ts=strtotime($date); if(!$ts) return ''; return date('d',$ts).' '.unit_month_id((int)date('n',$ts)).' '.date('Y',$ts); }
function unit_content_detail(PDO $pdo, string $type, int $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM unit_content WHERE id=? AND unit_slug=? AND content_type=? AND is_active=1');
    $stmt->execute([$id, UNIT_SLUG, $type]);
    return $stmt->fetch() ?: null;
}
function unit_render_detail(array $item, string $backHref, string $backLabel): void {
    $image = unit_media($item['image'] ?? '');
    echo '<div class="shell detail-content">';
    echo '<a class="text-link" href="'.unit_e($backHref).'">&larr; '.unit_e($backLabel).'</a>';
    if (!empty($item['meta'])) echo '<span class="eyebrow">'.unit_e($item['meta']).'</span>';
    echo '<h2>'.unit_e($item['title']).'</h2>';
    if (!empty($item['published_at'])) echo '<p class="detail-date">'.unit_e(unit_tanggal($item['published_at'])).'</p>';
    if ($image) echo '<img class="detail-hero-image" src="'.unit_e($image).'" alt="'.unit_e($item['title']).'">';
    echo '<div class="detail-body">'.nl2br(unit_e($item['body'] ?: $item['summary'] ?? '')).'</div>';
    echo '</div>';
}
function unit_page_banner(string $eyebrow, string $title, string $text): void { echo '<section class="page-banner"><div class="shell"><span class="eyebrow">'.unit_e($eyebrow).'</span><h1>'.unit_e($title).'</h1><p>'.unit_e($text).'</p></div></section>'; }
function unit_home_notices(): array {
    $notices = [
        'daycare' => ['Pengasuhan hangat untuk usia dini', 'Stimulasi motorik, sensori, dan bahasa', 'Pembiasaan doa dan adab setiap hari', 'Konsultasi bersama pendidik Daycare Permata Hati'],
        'tkit' => ['Belajar sambil bermain untuk anak usia 4-6 tahun', 'Tahsin dan hafalan surat pendek', 'Kegiatan kreatif serta eksplorasi luar ruang', 'Pendaftaran TKIT Permata Hati Bekasi dibuka'],
        'sdit' => ['Akademik, Al-Quran, dan karakter dalam satu proses', 'Tahfidz sesuai target pembelajaran', 'Literasi, seni, olahraga, dan prestasi siswa', 'Informasi pendaftaran SDIT tersedia melalui admin'],
        'smpit' => ['Akademik kuat dan pembinaan remaja muslim', 'Tahfidz lanjutan serta mentoring siswa', 'Proyek sains, digital, olahraga, dan kepemimpinan', 'Informasi pendaftaran SMPIT tersedia melalui admin'],
    ];
    return $notices[UNIT_SLUG] ?? $notices['sdit'];
}

if ($page === 'home') {
    unit_page_start('Beranda', 'home'); $programs=unit_content($pdo,'program',3); $activities=unit_content($pdo,'activity',3); $achievements=unit_content($pdo,'achievement',3); $albums=unit_albums($pdo); $notices=unit_home_notices();
    require_once __DIR__ . '/../backend/helpers/unit_shared.php';
    $socialStmt = unit_connect_main_site_pdo()->prepare('SELECT * FROM instagram_gallery WHERE scope=? AND is_active=1 ORDER BY sort_order,id LIMIT 5'); $socialStmt->execute([UNIT_SLUG]); $socialItems = $socialStmt->fetchAll(); ?>
<?php $heroIsVideo = ($settings['hero_media_type'] ?? '') === 'video' && !empty($settings['hero_media_path']); ?>
<section class="hero hero-foundation" data-hero-unit>
<?php if ($heroIsVideo): ?><video class="hero-video" data-hero-video muted playsinline preload="auto"><source src="<?php echo unit_e($settings['hero_media_path']); ?>"></video>
<?php else: ?><div class="hero-image" style="background-image:url('<?php echo unit_e(!empty($settings['hero_media_path']) ? $settings['hero_media_path'] : unit_media($unit_config['hero_image'])); ?>')"></div>
<?php endif; ?>
<div class="hero-shade"></div><div class="hero-motion-orbit orbit-one"></div><div class="hero-motion-orbit orbit-two"></div><div class="shell hero-content" <?php echo $heroIsVideo ? 'data-awaits-video="1"' : ''; ?>><span class="eyebrow light">SIT PERMATA HATI BEKASI</span><h1><?php echo unit_e($settings['name']); ?></h1><p><?php echo unit_e($settings['description']); ?></p><div class="hero-actions"><a class="button button-accent" href="spmb.php">Daftar Sekarang</a><a class="button button-ghost" href="profile.php">Kenali Unit Kami</a></div><div class="hero-unit-note"><span><?php echo unit_e(strtoupper($unit_config['short_name'])); ?></span><p><?php echo unit_e($settings['tagline']); ?></p></div><div class="hero-facts"><span><b>Islamic</b><small>Learning culture</small></span><span><b>Bekasi</b><small>Tambun Selatan</small></span><span><b>SPMB</b><small>Informasi tersedia</small></span></div></div></section>
<section class="unit-notice-bar" aria-label="Informasi <?php echo unit_e($settings['name']); ?>"><div class="unit-notice-window"><div class="unit-notice-track"><?php foreach(array_merge($notices,$notices) as $notice): ?><span><i></i><?php echo unit_e($notice); ?></span><?php endforeach; ?></div></div></section>
<section class="section intro-section"><div class="shell intro-grid"><div><span class="eyebrow">TUMBUH BERSAMA</span><h2>Ruang belajar yang mendampingi setiap langkah anak.</h2></div><p><?php echo unit_e($settings['tagline']); ?> Kami membangun pengalaman belajar yang aman, aktif, dan bermakna bersama keluarga.</p></div></section>
<?php $whyPhoto = !empty($activities[0]['image']) ? unit_media($activities[0]['image']) : (!empty($settings['hero_media_path']) && !$heroIsVideo ? $settings['hero_media_path'] : unit_media($unit_config['hero_image'])); ?>
<section class="section section-primary why-unit"><div class="shell why-grid-media"><div class="why-unit-copy"><span class="why-unit-ribbon">Mengapa Memilih <?php echo unit_e($unit_config['short_name']); ?>?</span><p class="why-unit-lead"><?php echo unit_e($settings['tagline']); ?></p><ul class="why-unit-points"><?php foreach (array_slice($notices, 0, 3) as $point): ?><li><?php echo unit_e($point); ?></li><?php endforeach; ?></ul><a class="button button-accent" href="profile.php">Kenali Kami Lebih Dekat</a></div><div class="why-unit-media"><img src="<?php echo unit_e($whyPhoto); ?>" alt="Kegiatan siswa <?php echo unit_e($settings['name']); ?>" loading="lazy"></div></div></section>
<section class="section section-soft"><div class="shell"><div class="section-head"><span class="eyebrow">PROGRAM UNGGULAN</span><h2>Belajar sesuai tahap tumbuh kembang</h2></div><?php unit_cards($programs,'program-grid','Program akan segera hadir.','programs.php'); ?></div></section>
<section class="section"><div class="shell"><div class="section-head line-head"><div><span class="eyebrow">KEGIATAN</span><h2>Hari-hari penuh eksplorasi</h2></div><a class="text-link" href="activities.php">Lihat semua kegiatan</a></div><?php unit_cards($activities,'activity-grid','Kegiatan akan segera hadir.','activities.php'); ?></div></section>
<section class="section section-primary"><div class="shell"><div class="section-head light-head"><div><span class="eyebrow light">POTRET SEKOLAH</span><h2>Setiap momen adalah proses bertumbuh</h2></div><a class="text-link light-link" href="gallery.php">Buka galeri</a></div><div class="album-strip"><?php foreach(array_slice($albums,0,3) as $album): ?><a href="gallery.php?album=<?php echo (int)$album['id']; ?>" class="album-tile" style="background-image:url('<?php echo unit_e(unit_media($album['cover_image'])); ?>')"><span><?php echo unit_e($album['title']); ?></span></a><?php endforeach; ?></div></div></section>
<section class="section"><div class="shell"><div class="section-head line-head"><div><span class="eyebrow">APRESIASI</span><h2>Pencapaian yang dirayakan</h2></div><a class="text-link" href="achievements.php">Semua prestasi</a></div><?php unit_cards($achievements,'achievement-grid','Prestasi akan segera ditampilkan.','achievements.php'); ?></div></section>
<?php if ($socialItems): ?>
<section class="section section-soft"><div class="shell"><div class="section-head"><span class="eyebrow">INSTAGRAM</span><h2>Galeri Instagram <?php echo unit_e($unit_config['short_name']); ?></h2></div><div class="ig-gallery-grid"><?php foreach ($socialItems as $socialItem): ?><?php if ($socialItem['media_type']==='embed' && !empty($socialItem['instagram_url'])): ?><div class="ig-gallery-card ig-gallery-embed" data-ig-lazy="<?php echo unit_e($socialItem['instagram_url']); ?>"><div class="ig-gallery-loading">Memuat postingan Instagram&hellip;</div></div><?php else: ?><div class="ig-gallery-card"><div class="ig-gallery-media"><?php if ($socialItem['media_type']==='video'): ?><video src="<?php echo unit_e($socialItem['media_path']); ?>" controls preload="metadata"></video><?php else: ?><img src="<?php echo unit_e($socialItem['media_path']); ?>" alt="<?php echo unit_e($socialItem['caption'] ?: 'Postingan Instagram'); ?>" loading="lazy"><?php endif; ?></div><?php if (!empty($socialItem['caption']) || !empty($socialItem['instagram_url'])): ?><div class="ig-gallery-foot"><?php if (!empty($socialItem['caption'])): ?><p><?php echo unit_e($socialItem['caption']); ?></p><?php endif; ?><?php if (!empty($socialItem['instagram_url'])): ?><a href="<?php echo unit_e($socialItem['instagram_url']); ?>" target="_blank" rel="noopener">Lihat di Instagram &rarr;</a><?php endif; ?></div><?php endif; ?></div><?php endif; ?><?php endforeach; ?></div></div></section>
<?php if (in_array('embed', array_column($socialItems, 'media_type'), true)): ?><script>
(function () {
    var cards = document.querySelectorAll('[data-ig-lazy]');
    if (!cards.length) return;
    var scriptLoading = false;
    function loadIgEmbedScript(done) {
        if (window.instgrm && window.instgrm.Embeds) { done(); return; }
        if (scriptLoading) { window.addEventListener('ig-embed-ready', done, { once: true }); return; }
        scriptLoading = true;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.instagram.com/embed.js';
        s.onload = function () { window.dispatchEvent(new Event('ig-embed-ready')); done(); };
        document.body.appendChild(s);
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var card = entry.target;
            io.unobserve(card);
            var url = card.getAttribute('data-ig-lazy');
            var bq = document.createElement('blockquote');
            bq.className = 'instagram-media';
            bq.setAttribute('data-instgrm-permalink', url);
            bq.setAttribute('data-instgrm-version', '14');
            var loading = card.querySelector('.ig-gallery-loading');
            if (loading) loading.remove();
            card.appendChild(bq);
            loadIgEmbedScript(function () {
                if (window.instgrm && window.instgrm.Embeds) window.instgrm.Embeds.process();
            });
        });
    }, { rootMargin: '500px 0px' });
    cards.forEach(function (card) { io.observe(card); });
})();
</script><?php endif; ?>
<?php endif; ?>
<?php unit_page_end(); return; }

$pageInfo=['profile'=>['PROFIL UNIT','Mengenal '.$settings['name'],'Komitmen pendidikan dan pengasuhan yang berpihak pada tumbuh kembang siswa.'],'programs'=>['PROGRAM','Program belajar yang bermakna','Rangkaian pembelajaran yang dirancang sesuai usia dan kebutuhan anak.'],'activities'=>['KEGIATAN','Belajar melalui pengalaman','Aktivitas yang memberi ruang bagi anak untuk aktif, berani, dan bahagia.'],'news'=>['BERITA','Kabar terbaru unit','Informasi kegiatan dan cerita terbaru dari lingkungan sekolah.'],'achievements'=>['PRESTASI','Apresiasi untuk setiap proses','Pencapaian siswa yang tumbuh dari usaha, dukungan, dan doa.'],'brochures'=>['BROSUR','Informasi pendaftaran','Unduh informasi layanan dan program '.$settings['name'].'.'],'gallery'=>['GALERI','Potret kegiatan sekolah','Kumpulan momen belajar, bermain, dan bertumbuh bersama.'],'spmb'=>['SPMB','Pendaftaran siswa baru','Lengkapi formulir pendaftaran resmi. Tim admin akan menghubungi Anda melalui WhatsApp.'],'karir'=>['KARIR','Bergabung bersama kami','Lihat lowongan yang tersedia di seluruh unit SIT Permata Hati Bekasi.'],'contact'=>['KONTAK','Mari terhubung dengan kami','Tim kami siap menjawab pertanyaan Anda.']];
[$eyebrow,$title,$text]=$pageInfo[$page]??$pageInfo['profile']; unit_page_start($title,$page); unit_page_banner($eyebrow,$title,$text);
if($page==='profile'): ?>
<section class="section"><div class="shell story-grid"><img src="<?php echo unit_e(unit_media($unit_config['building_image'])); ?>" alt="Gedung <?php echo unit_e($settings['name']); ?>"><div><span class="eyebrow">TENTANG KAMI</span><h2><?php echo unit_e($settings['name']); ?></h2><p><?php echo unit_e($settings['description']); ?></p><p>Kami percaya setiap anak perlu didampingi dengan perhatian, keteladanan, dan kesempatan untuk menemukan potensinya.</p><a class="button button-primary" href="contact.php">Hubungi Kami</a></div></div></section>
<?php elseif($page==='programs'): $detailItem=($id=(int)($_GET['id']??0))?unit_content_detail($pdo,'program',$id):null; ?><section class="section"><div class="shell"><?php if($detailItem): unit_render_detail($detailItem,'programs.php','Kembali ke semua program'); else: unit_cards(unit_content($pdo,'program'),'program-grid','Program akan segera hadir.','programs.php'); endif; ?></div></section>
<?php elseif($page==='activities'): $detailItem=($id=(int)($_GET['id']??0))?unit_content_detail($pdo,'activity',$id):null; ?><section class="section"><div class="shell"><?php if($detailItem): unit_render_detail($detailItem,'activities.php','Kembali ke semua kegiatan'); else: unit_cards(unit_content($pdo,'activity'),'activity-grid','Kegiatan akan segera hadir.','activities.php'); endif; ?></div></section>
<?php elseif($page==='news'): $detailItem=($id=(int)($_GET['id']??0))?unit_content_detail($pdo,'news',$id):null; ?><section class="section"><div class="shell"><?php if($detailItem): unit_render_detail($detailItem,'news.php','Kembali ke semua berita'); else: unit_cards(unit_content($pdo,'news'),'news-grid','Belum ada berita terbaru.','news.php'); endif; ?></div></section>
<?php elseif($page==='achievements'): $detailItem=($id=(int)($_GET['id']??0))?unit_content_detail($pdo,'achievement',$id):null; ?><section class="section"><div class="shell"><?php if($detailItem): unit_render_detail($detailItem,'achievements.php','Kembali ke semua prestasi'); else: unit_cards(unit_content($pdo,'achievement'),'achievement-grid','Prestasi akan segera ditampilkan.','achievements.php'); endif; ?></div></section>
<?php elseif($page==='brochures'): ?><section class="section"><div class="shell brochure-list"><?php foreach(unit_content($pdo,'brochure') as $item): ?><article class="brochure-row"><div><span class="eyebrow">INFORMASI UNIT</span><h2><?php echo unit_e($item['title']); ?></h2><p><?php echo unit_e($item['summary']); ?></p></div><a class="button button-primary" href="<?php echo unit_e(unit_media($item['image'])); ?>" target="_blank" rel="noopener">Unduh Brosur</a></article><?php endforeach; ?></div></section>
<?php elseif($page==='gallery'): $selected=(int)($_GET['album']??0); $albums=unit_albums($pdo); if($selected): $photos=unit_album_photos($pdo,$selected); $selectedAlbum=null;foreach($albums as $album)if((int)$album['id']===$selected)$selectedAlbum=$album; ?><section class="section"><div class="shell"><a class="text-link" href="gallery.php">Kembali ke album</a><div class="section-head"><span class="eyebrow">ALBUM</span><h2><?php echo unit_e($selectedAlbum['title']??'Galeri'); ?></h2></div><?php if (!$photos): ?><p class="empty-state">Belum ada foto pada album ini.</p><?php else: ?><div class="photo-grid"><?php foreach($photos as $index=>$photo): ?><button class="photo-button" type="button" data-lightbox="<?php echo unit_e(unit_media($photo['image'])); ?>" data-title="<?php echo unit_e($photo['title']); ?>"><img src="<?php echo unit_e(unit_media($photo['image'])); ?>" alt="<?php echo unit_e($photo['title']); ?>"><span><?php echo unit_e($photo['title']); ?></span></button><?php endforeach; ?></div><?php endif; ?></div></section><?php else: ?><section class="section"><div class="shell">
<?php if (!$albums): ?><p class="empty-state">Galeri sedang disiapkan. Silakan kembali lagi nanti.</p><?php else: ?>
<div class="album-grid"><?php foreach($albums as $album): ?><a class="album-card" href="gallery.php?album=<?php echo (int)$album['id']; ?>"><img src="<?php echo unit_e(unit_media($album['cover_image'])); ?>" alt="<?php echo unit_e($album['title']); ?>"><div><span><?php echo (int)$album['photo_count']; ?> foto</span><h2><?php echo unit_e($album['title']); ?></h2><p><?php echo unit_e($album['description']); ?></p></div></a><?php endforeach; ?></div>
<?php endif; ?>
</div></section><?php endif; ?>
<?php elseif($page==='spmb'): $spmbAcademicYears = spmb_academic_years(); ?>
<section class="section"><div class="shell form-layout">
    <div>
        <span class="eyebrow">PENDAFTARAN</span>
        <h2>Mulai pendaftaran <?php echo unit_e($settings['name']); ?>.</h2>
        <p>Lengkapi data calon siswa dan orang tua berikut. Tim admin akan menghubungi Anda melalui WhatsApp untuk proses selanjutnya.</p>
        <img src="<?php echo unit_e(unit_media($unit_config['hero_image'])); ?>" alt="Kegiatan <?php echo unit_e($settings['name']); ?>">
    </div>
    <form method="post" class="public-form">
        <input type="hidden" name="csrf" value="<?php echo unit_e(unit_csrf()); ?>">
        <input type="hidden" name="level" value="<?php echo unit_e($lockedLevel); ?>">
        <p class="form-locked-level">Mendaftar untuk jenjang: <strong><?php echo unit_e($lockedLevel); ?></strong></p>
        <?php if (!empty($spmbSuccess)): ?><p class="form-success">Pendaftaran berhasil dikirim. Tim kami akan segera menghubungi Anda melalui WhatsApp.</p><?php endif; ?>
        <?php if (!empty($formError)): ?><p class="form-error"><?php echo unit_e($formError); ?></p><?php endif; ?>
        <label>Nama Calon Siswa *<input required name="student_name" value="<?php echo unit_e($spmbOld['student_name']); ?>"></label>
        <div class="form-pair">
            <label>NIK Calon Siswa<input name="student_nik" value="<?php echo unit_e($spmbOld['student_nik']); ?>"></label>
            <label>Jenis Kelamin<select name="gender"><option value="">-- Pilih --</option><option value="L" <?php echo $spmbOld['gender']==='L'?'selected':''; ?>>Laki-laki</option><option value="P" <?php echo $spmbOld['gender']==='P'?'selected':''; ?>>Perempuan</option></select></label>
        </div>
        <div class="form-pair">
            <label>Tempat Lahir<input name="birth_place" value="<?php echo unit_e($spmbOld['birth_place']); ?>"></label>
            <label>Tanggal Lahir<input type="date" name="birth_date" value="<?php echo unit_e($spmbOld['birth_date']); ?>"></label>
        </div>
        <label>Nama Orang Tua *<input required name="parent_name" value="<?php echo unit_e($spmbOld['parent_name']); ?>"></label>
        <div class="form-pair">
            <label>NIK Orang Tua/Wali<input name="parent_nik" value="<?php echo unit_e($spmbOld['parent_nik']); ?>"></label>
            <label>Nomor Kartu Keluarga<input name="family_card_number" value="<?php echo unit_e($spmbOld['family_card_number']); ?>"></label>
        </div>
        <label>Nomor WhatsApp *<input required name="whatsapp" inputmode="tel" value="<?php echo unit_e($spmbOld['whatsapp']); ?>"></label>
        <label>Tahun Ajaran *<select required name="academic_year"><?php foreach ($spmbAcademicYears as $year => $track): ?><option value="<?php echo unit_e($year); ?>" <?php echo $spmbOld['academic_year']===$year?'selected':''; ?>><?php echo unit_e($year.' — '.$track); ?></option><?php endforeach; ?></select><small class="field-help">Tahun berikutnya otomatis tercatat sebagai waiting list.</small></label>
        <label>Asal Sekolah<input name="previous_school" value="<?php echo unit_e($spmbOld['previous_school']); ?>"></label>
        <label>Alamat Lengkap<textarea name="address" rows="4"><?php echo unit_e($spmbOld['address']); ?></textarea></label>
        <button class="button button-primary" type="submit">Kirim Pendaftaran</button>
    </form>
</div></section>
<?php elseif($page==='karir'): if ($karirSlug !== '' && !$karirJob): ?>
<section class="section"><div class="shell"><p class="empty-state">Lowongan ini sudah ditutup atau tidak tersedia.</p><a class="text-link" href="karir.php">Lihat lowongan lain</a></div></section>
<?php elseif ($karirJob): $job = $karirJob; ?>
<section class="section"><div class="shell story-grid">
    <div>
        <a class="text-link" href="karir.php">&larr; Kembali ke daftar lowongan</a>
        <span class="eyebrow"><?php echo unit_e($job['department'] ?: 'LOWONGAN'); ?></span>
        <h2><?php echo unit_e($job['title']); ?></h2>
        <p><?php echo nl2br(unit_e($job['description'])); ?></p>
        <?php if (!empty($job['responsibilities'])): ?><h3>Tanggung Jawab</h3><ul class="job-detail-list"><?php foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $job['responsibilities']))) as $item): ?><li><?php echo unit_e($item); ?></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if (!empty($job['requirements'])): ?><h3>Kualifikasi</h3><ul class="job-detail-list"><?php foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $job['requirements']))) as $item): ?><li><?php echo unit_e($item); ?></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if (!empty($job['benefits'])): ?><h3>Benefit</h3><ul class="job-detail-list"><?php foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $job['benefits']))) as $item): ?><li><?php echo unit_e($item); ?></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
    <div class="map-card job-summary-card">
        <dl>
            <dt>Unit</dt><dd><?php echo unit_e($job['unit']); ?></dd>
            <dt>Tipe</dt><dd><?php echo unit_e($job['employment_type']); ?></dd>
            <dt>Lokasi</dt><dd><?php echo unit_e($job['work_location']); ?></dd>
            <dt>Pendidikan</dt><dd><?php echo unit_e($job['education'] ?: 'Menyesuaikan posisi'); ?></dd>
            <dt>Batas Lamaran</dt><dd><?php echo $job['deadline'] ? unit_e(tanggal_indo($job['deadline'])) : 'Sampai posisi terpenuhi'; ?></dd>
        </dl>
        <a class="button button-primary" href="#lamar">Lamar Posisi Ini</a>
    </div>
</div></section>
<section class="section section-soft" id="lamar"><div class="shell">
    <div class="section-head"><span class="eyebrow">FORM LAMARAN</span><h2>Lamar sebagai <?php echo unit_e($job['title']); ?></h2></div>
    <?php if (!empty($karirSubmitted)): ?><p class="form-success">Lamaran berhasil dikirim. Tim kami akan meninjau profil Anda.</p><?php endif; ?>
    <?php if (!empty($karirErrors)): ?><p class="form-error"><?php echo unit_e(implode(' ', $karirErrors)); ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="public-form">
        <input type="hidden" name="_token" value="<?php echo unit_e(public_form_csrf_token()); ?>">
        <div class="form-hp"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-pair">
            <label>Nama Lengkap *<input required name="full_name" value="<?php echo unit_e($karirOld['full_name']); ?>"></label>
            <label>Email *<input type="email" required name="email" value="<?php echo unit_e($karirOld['email']); ?>"></label>
        </div>
        <div class="form-pair">
            <label>Nomor WhatsApp *<input required name="phone" value="<?php echo unit_e($karirOld['phone']); ?>"></label>
            <label>Domisili<input name="city" value="<?php echo unit_e($karirOld['city']); ?>"></label>
        </div>
        <div class="form-pair">
            <label>Pendidikan Terakhir<input name="education" value="<?php echo unit_e($karirOld['education']); ?>"></label>
            <label>Pengalaman Kerja (tahun)<input type="number" min="0" max="50" step="0.5" name="experience_years" value="<?php echo unit_e($karirOld['experience_years']); ?>"></label>
        </div>
        <label>Tautan Portofolio/LinkedIn<input type="url" name="portfolio_url" value="<?php echo unit_e($karirOld['portfolio_url']); ?>" placeholder="https://"></label>
        <label>Pengantar Lamaran *<textarea required name="cover_letter" rows="5"><?php echo unit_e($karirOld['cover_letter']); ?></textarea></label>
        <label>CV Terbaru *<input type="file" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required><small class="field-help">PDF, DOC, atau DOCX. Maksimal 5 MB.</small></label>
        <button class="button button-primary" type="submit">Kirim Lamaran</button>
    </form>
</div></section>
<?php else: ?>
<section class="section"><div class="shell job-layout">
    <aside class="job-sidebar">
        <span class="eyebrow">MENGAPA KAMI</span>
        <h3>Lebih dari tempat bekerja</h3>
        <p>Lingkungan yang membantu Anda terus belajar sekaligus memberi dampak bagi pendidikan anak.</p>
        <ul>
            <li>Budaya kerja Islami</li>
            <li>Pengembangan kompetensi</li>
            <li>Kolaborasi lintas unit</li>
            <li>Pekerjaan yang bermakna</li>
        </ul>
    </aside>
    <div>
        <div class="job-results-head"><div><span class="eyebrow">LOWONGAN TERSEDIA</span><h2><?php echo count($karirJobs); ?> posisi ditemukan</h2></div></div>
        <?php if (!$karirJobs): ?><p class="empty-state">Belum ada lowongan yang dibuka saat ini. Silakan kembali lagi nanti.</p><?php else: ?>
        <div class="job-list">
            <?php foreach ($karirJobs as $job): ?>
            <a class="job-card<?php echo $job['is_featured']?' featured':''; ?>" href="karir.php?slug=<?php echo urlencode($job['slug']); ?>">
                <div class="job-logo"><img src="<?php echo unit_asset('assets/images/logo.png'); ?>" alt=""></div>
                <div class="job-card-main">
                    <div class="job-card-top"><?php if($job['is_featured']): ?><span class="job-featured-badge">Prioritas</span><?php endif; ?><span class="job-department"><?php echo unit_e($job['department']); ?></span></div>
                    <h3><?php echo unit_e($job['title']); ?></h3>
                    <p><?php echo unit_e($job['summary']); ?></p>
                    <div class="job-meta-pills"><span><?php echo unit_e($job['unit']); ?></span><span><?php echo unit_e($job['employment_type']); ?></span><span><?php echo unit_e($job['work_location']); ?></span></div>
                </div>
                <div class="job-card-side">
                    <?php if ($job['deadline']): ?><small>Batas lamaran</small><strong><?php echo unit_e(tanggal_indo($job['deadline'])); ?></strong><?php else: ?><small>Dibuka sampai</small><strong>Posisi terpenuhi</strong><?php endif; ?>
                    <span class="job-apply-link">Detail &amp; Apply &rarr;</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div></section>
<?php endif; ?>
<?php elseif($page==='contact'): ?><section class="section"><div class="shell contact-grid"><div><span class="eyebrow">KONTAK ADMINISTRASI</span><h2><?php echo unit_e($settings['name']); ?></h2><dl><dt>Alamat</dt><dd><?php echo nl2br(unit_e($settings['address'])); ?></dd><dt>Telepon</dt><dd><a href="tel:<?php echo unit_e(preg_replace('/[^0-9+]/','',$settings['phone'])); ?>"><?php echo unit_e($settings['phone']); ?></a></dd><dt>Email</dt><dd><a href="mailto:<?php echo unit_e($settings['email']); ?>"><?php echo unit_e($settings['email']); ?></a></dd></dl><div class="social-links"><a href="<?php echo unit_e($settings['instagram']); ?>" target="_blank" rel="noopener">Instagram</a><a href="<?php echo unit_e($settings['youtube']); ?>" target="_blank" rel="noopener">YouTube</a></div></div><div class="map-card"><iframe title="Peta <?php echo unit_e($settings['name']); ?>" src="https://www.google.com/maps?q=<?php echo rawurlencode($settings['address']); ?>&output=embed" loading="lazy"></iframe><a class="button button-primary" href="<?php echo unit_e($settings['maps']); ?>" target="_blank" rel="noopener">Buka Petunjuk Arah</a></div></div></section>
<?php endif; ?><div class="lightbox" data-lightbox-dialog hidden><button type="button" class="lightbox-close" data-lightbox-close aria-label="Tutup">×</button><button type="button" class="lightbox-nav prev" data-lightbox-prev aria-label="Foto sebelumnya">&#8249;</button><img src="" alt=""><button type="button" class="lightbox-nav next" data-lightbox-next aria-label="Foto berikutnya">&#8250;</button><p data-lightbox-title></p></div><?php unit_page_end();
