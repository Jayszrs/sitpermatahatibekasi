<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Beranda';

// Ambil 3 berita terbaru
$stmt = $pdo->query("SELECT * FROM news ORDER BY published_at DESC LIMIT 3");
$latest_news = $stmt->fetchAll();
$hero_media = $pdo->query("SELECT * FROM hero_media WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
if (!$hero_media) $hero_media = [[
    'title' => SITE_NAME, 'eyebrow' => 'Sekolah Islam Terpadu',
    'description' => 'Membentuk generasi sholeh, cerdas, mandiri, dan berwawasan global.',
    'media_type' => 'image', 'media_url' => SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg',
    'poster_url' => null, 'cta_label' => 'Daftar SPMB', 'cta_url' => SITE_URL . '/spmb.php',
]];

// Ambil album galeri terbaru
$stmt = $pdo->query("
    SELECT a.*,
        (SELECT COUNT(*) FROM gallery_photos p WHERE p.album_id = a.id) AS photo_count
    FROM gallery_albums a
    WHERE a.is_active = 1
    ORDER BY a.sort_order ASC, a.created_at DESC, a.id DESC
    LIMIT 3
");
$gallery_albums = $stmt->fetchAll();
$galleryAlbumPhotoStmt = $pdo->prepare("
    SELECT title, image
    FROM gallery_photos
    WHERE album_id = ?
    ORDER BY sort_order ASC, created_at DESC, id DESC
    LIMIT 5
");
$gallery_album_slides = [];
foreach ($gallery_albums as $album) {
    $galleryAlbumPhotoStmt->execute([(int)$album['id']]);
    $gallery_album_slides[(int)$album['id']] = $galleryAlbumPhotoStmt->fetchAll();
}
$home_units = $pdo->query("SELECT * FROM site_content_items WHERE type='unit' AND is_active=1 ORDER BY sort_order,id LIMIT 4")->fetchAll();
$home_programs = $pdo->query("SELECT * FROM site_content_items WHERE type='program' AND is_active=1 ORDER BY sort_order,id LIMIT 8")->fetchAll();
$home_achievements = $pdo->query("SELECT * FROM site_content_items WHERE type='achievement' AND is_active=1 ORDER BY sort_order,id LIMIT 6")->fetchAll();
$home_profile = $pdo->query('SELECT * FROM site_profile WHERE id=1')->fetch();
$home_activities = $pdo->query("SELECT * FROM site_content_items WHERE type='activity' AND is_active=1 ORDER BY sort_order,id LIMIT 4")->fetchAll();
$school_advantages = school_advantages();
$unit_image_map = [
    'daycare' => SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg',
    'tkit' => SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg',
    'sdit' => SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg',
    'smpit' => SITE_URL . '/frontend/assets/images/school/gedung-smpit.jpeg',
];
$unit_icons = [
    '<svg viewBox="0 0 24 24"><path d="M12 21s-7-3.9-7-10V5l7-3 7 3v6c0 6.1-7 10-7 10Z"/><path d="M9 12h6"/><path d="M12 9v6"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M4 19V6.5A2.5 2.5 0 0 1 6.5 4H20v15H6.5A2.5 2.5 0 0 0 4 21"/><path d="M8 8h8"/><path d="M8 12h6"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M3 7l9-4 9 4-9 4-9-4Z"/><path d="M5 10v5c2 2 12 2 14 0v-5"/><path d="M21 7v6"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M4 20h16"/><path d="M6 20V8l6-4 6 4v12"/><path d="M9 20v-6h6v6"/><path d="M9 10h.01"/><path d="M15 10h.01"/></svg>',
];
$program_icons = [
    '<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5Z"/><path d="M8 7h8"/><path d="M8 11h6"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M12 3v18"/><path d="M5 8c4-4 10-4 14 0"/><path d="M7 14c3-2 7-2 10 0"/><path d="M9 19c2-1 4-1 6 0"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M12 3l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 15.3 7.2 17.9l.9-5.4-3.9-3.8 5.4-.8Z"/></svg>',
    '<svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="2"/><path d="M8 9h8"/><path d="M8 13h5"/><path d="M10 19v3"/><path d="M14 19v3"/></svg>',
    '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="M12 2v4"/><path d="M12 18v4"/><path d="M2 12h4"/><path d="M18 12h4"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M7 11V7a5 5 0 0 1 10 0v4"/><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M12 15v2"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-8 0v2"/><circle cx="12" cy="7" r="4"/><path d="M20 8v6"/><path d="M23 11h-6"/></svg>',
];
$activity_icons = [
    '<svg viewBox="0 0 24 24"><path d="M12 3c2 4 6 5 6 10a6 6 0 0 1-12 0c0-5 4-6 6-10Z"/><path d="M12 13c1.2 1.4 2 2.4 2 4a2 2 0 0 1-4 0c0-1.6.8-2.6 2-4Z"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M4 19l5-12 4 7 3-4 4 9H4Z"/><path d="M14 5h.01"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M6 9l6-4 6 4-6 4-6-4Z"/><path d="M6 13l6 4 6-4"/><path d="M6 17l6 4 6-4"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M3 8h18"/><path d="M5 8v12h14V8"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/><path d="M9 13h6"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M12 2l4 7 7 1-5 5 1 7-7-3-7 3 1-7-5-5 7-1 4-7Z"/></svg>',
    '<svg viewBox="0 0 24 24"><path d="M4 17l4-8 4 8"/><path d="M6 13h4"/><path d="M14 17V7h3a3 3 0 0 1 0 6h-3"/></svg>',
];
$achievement_units = ['Semua', 'Daycare', 'TKIT', 'SDIT', 'SMPIT'];

require_once __DIR__ . '/../components/header.php';
?>

<!-- HERO -->
<section class="hero hero-slider" data-hero-slider>
    <div class="hero-slides" aria-hidden="true">
        <?php foreach($hero_media as $index=>$slide): ?><div class="hero-slide<?php echo $index===0?' active':''; ?>" data-media-type="<?php echo esc($slide['media_type']); ?>"><?php if($slide['media_type']==='video'): ?><video muted playsinline preload="metadata" <?php if($slide['poster_url']): ?>poster="<?php echo esc($slide['poster_url']); ?>"<?php endif; ?>><source src="<?php echo esc($slide['media_url']); ?>"></video><?php else: ?><img src="<?php echo esc($slide['media_url']); ?>" alt="" <?php echo $index===0?'fetchpriority="high"':'loading="lazy"'; ?>><?php endif; ?></div><?php endforeach; ?>
    </div>
    <div class="container hero-inner"><div class="hero-content-shell">
        <div class="hero-copy-stack"><?php foreach($hero_media as $index=>$slide): ?><div class="hero-copy<?php echo $index===0?' active':''; ?>" data-hero-copy="<?php echo $index; ?>"><span class="hero-eyebrow"><?php echo esc($slide['eyebrow'] ?: 'SIT Permata Hati Bekasi'); ?></span><h1><?php echo esc($slide['title']); ?></h1><p><?php echo esc($slide['description']); ?></p><div class="hero-actions"><?php if($slide['cta_label'] && $slide['cta_url']): ?><a href="<?php echo esc($slide['cta_url']); ?>" class="btn btn-gold"><?php echo esc($slide['cta_label']); ?></a><?php endif; ?><a href="tentang.php" class="btn btn-outline-light">Kenali Sekolah</a></div></div><?php endforeach; ?></div>
        <div class="hero-side-note"><span>SIT PHB</span><p>Pendidikan terpadu dari usia dini sampai remaja.</p></div>
    </div></div>
    <div class="container hero-bottom"><div class="hero-trust"><span><strong>4</strong> Unit Pendidikan</span><span><strong>Islamic</strong> Learning Culture</span><span><strong>Bekasi</strong> Tambun Selatan</span></div><div class="hero-controls"><button type="button" data-hero-prev aria-label="Slide sebelumnya">&larr;</button><span data-hero-counter>01 / <?php echo str_pad((string)count($hero_media),2,'0',STR_PAD_LEFT); ?></span><button type="button" data-hero-next aria-label="Slide berikutnya">&rarr;</button></div></div>
    <div class="hero-pagination" aria-label="Navigasi slide"><?php foreach($hero_media as $index=>$slide): ?><button type="button" class="<?php echo $index===0?'active':''; ?>" data-hero-go="<?php echo $index; ?>" aria-label="Slide <?php echo $index+1; ?>"><span></span></button><?php endforeach; ?></div>
</section>

<!-- PENGUMUMAN -->
<div class="announcement-bar" aria-label="Pengumuman sekolah">
    <div class="announcement-track">
        <span>Selamat datang di website resmi SIT Permata Hati Bekasi</span>
        <span>Pendaftaran Murid Baru Tahun Ajaran 2026/2027 telah dibuka</span>
        <span>Sekolah Islam Terpadu: sholeh, cerdas, mandiri, dan berwawasan global</span>
        <span>Jl. Raya Buwek Jaya Gg. Buser No. 23-24, Tambun Selatan, Bekasi</span>
    </div>
</div>

<!-- INFORMASI TERBARU LANGSUNG SETELAH ONBOARDING -->
<section class="section home-pulse-section" id="informasi-terbaru">
    <div class="container home-pulse-grid">
        <div><div class="pulse-heading"><div><span class="section-eyebrow">Berita Terbaru</span><h2>Kabar dari Sekolah</h2></div><a href="berita.php">Semua berita &rarr;</a></div><div class="pulse-news-list"><?php foreach($latest_news as $news): ?><a href="detail-berita.php?slug=<?php echo urlencode($news['slug']); ?>" class="pulse-news-card"><img src="<?php echo esc($news['image']); ?>" alt="<?php echo esc($news['title']); ?>"><div><span><?php echo esc(tanggal_indo($news['published_at'])); ?></span><h3><?php echo esc($news['title']); ?></h3><p><?php echo esc(mb_strimwidth($news['excerpt'],0,88,'...')); ?></p></div></a><?php endforeach; ?></div></div>
        <div><div class="pulse-heading"><div><span class="section-eyebrow">Prestasi</span><h2>Siswa Membanggakan</h2></div><a href="prestasi.php">Semua prestasi &rarr;</a></div><div class="pulse-achievement-list"><?php foreach(array_slice($home_achievements,0,3) as $achievement): ?><a href="detail-prestasi.php?id=<?php echo (int)$achievement['id']; ?>" class="pulse-achievement-card"><img src="<?php echo esc($achievement['image'] ?: SITE_URL.'/frontend/assets/images/school/gedung-sekolah.jpeg'); ?>" alt="<?php echo esc($achievement['title']); ?>"><div><span><?php echo esc(($achievement['extra']?:'Sekolah').' · '.($achievement['year']?:date('Y'))); ?></span><h3><?php echo esc($achievement['title']); ?></h3><strong>Lihat cerita prestasi &rarr;</strong></div></a><?php endforeach; ?></div></div>
    </div>
</section>

<!-- TENTANG SEKOLAH -->
<section class="section">
    <div class="container about-grid">
        <button type="button" class="about-photo-card image-preview-trigger" data-lightbox-src="<?php echo esc($home_profile['image'] ?: SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg'); ?>" data-lightbox-title="Gedung SIT Permata Hati Bekasi">
            <img src="<?php echo esc($home_profile['image'] ?: SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg'); ?>" alt="Gedung <?php echo esc(SITE_NAME); ?>">
            <div class="about-photo-badge">
                <strong>SIT Permata Hati</strong>
                <span>Bekasi</span>
            </div>
        </button>
        <div class="about-text">
            <span class="section-eyebrow">Tentang Kami</span>
            <h2><?php echo esc($home_profile['history_title']); ?></h2>
            <p><?php echo esc(mb_strimwidth($home_profile['history_content'],0,390,'...')); ?></p>
            <a href="tentang.php" class="btn btn-primary">Selengkapnya</a>
        </div>
    </div>
</section>

<!-- UNIT SEKOLAH -->
<section class="section section-alt" id="unit-pendidikan">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Unit Pendidikan</span>
            <h2>Jenjang Pendidikan Kami</h2>
            <p>Menyediakan jenjang pendidikan berkelanjutan dari usia dini hingga menengah atas.</p>
        </div>
        <div class="grid-4 unit-home-grid">
            <?php foreach($home_units as $index => $unit): ?><?php $unit_key = strtolower($unit['subtitle'] ?: 'unit-'.$unit['id']); ?><div class="card unit-card"><div class="unit-card-photo"><img src="<?php echo esc($unit['image'] ?: ($unit_image_map[$unit_key] ?? SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg')); ?>" data-fallback="<?php echo SITE_URL; ?>/frontend/assets/images/school/gedung-sekolah.jpeg" alt="<?php echo esc($unit['title']); ?>"><span><?php echo $unit_icons[$index % count($unit_icons)]; ?><?php echo esc($unit['subtitle'] ?: $unit['title']); ?></span></div><div class="card-body"><h3><?php echo esc($unit['title']); ?></h3><p><?php echo esc(mb_strimwidth($unit['description'],0,145,'...')); ?></p><a href="unit.php#<?php echo esc($unit_key); ?>" class="btn btn-outline btn-sm">Lihat Detail</a></div></div><?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PROGRAM UNGGULAN -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Program Unggulan</span>
            <h2>Program Pendidikan Terpadu</h2>
            <p>Rangkaian program yang dirancang untuk mengembangkan potensi siswa secara menyeluruh.</p>
        </div>
        <div class="grid-4">
            <?php foreach($home_programs as $index => $program): ?><?php $programUrl=$program['link_url'] ?: 'detail-program.php?id='.$program['id']; ?><a class="program-card" href="<?php echo esc($programUrl); ?>" <?php echo preg_match('~^https?://~',$programUrl)?'target="_blank" rel="noopener"':''; ?> aria-label="Pelajari <?php echo esc($program['title']); ?>"><div class="program-icon" aria-hidden="true"><?php echo $program_icons[$index % count($program_icons)]; ?></div><h3><?php echo esc($program['title']); ?></h3><p><?php echo esc(mb_strimwidth($program['description'],0,125,'...')); ?></p><strong class="program-link"><?php echo esc($program['link_label'] ?: 'Pelajari program'); ?> &rarr;</strong></a><?php endforeach; ?>
        </div>
    </div>
</section>

<!-- KENAPA MEMILIH SEKOLAH KAMI -->
<section class="section section-alt" id="keunggulan">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Keunggulan</span>
            <h2>Kenapa Memilih Sekolah Kami</h2>
        </div>
        <div class="why-grid">
            <?php foreach ($school_advantages as $slug => $advantage): ?>
            <a class="why-item" href="keunggulan.php?slug=<?php echo urlencode($slug); ?>" aria-label="Pelajari <?php echo esc($advantage['title']); ?>">
                <div class="why-icon"><?php echo esc($advantage['number']); ?></div>
                <div><h3><?php echo esc($advantage['title']); ?></h3><p><?php echo esc($advantage['summary']); ?></p><span class="why-link">Pelajari selengkapnya <span aria-hidden="true">&rarr;</span></span></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- STATISTIK -->
<section class="section text-center" style="background: var(--primary-dark); color: white;">
    <div class="container grid-4" id="statsSection">
        <div><h2 style="color:var(--accent); font-size:3rem; margin-bottom:10px;"><span class="counter" data-target="1000">0</span>+</h2><p>Siswa</p></div>
        <div><h2 style="color:var(--accent); font-size:3rem; margin-bottom:10px;"><span class="counter" data-target="100">0</span>+</h2><p>Guru</p></div>
        <div><h2 style="color:var(--accent); font-size:3rem; margin-bottom:10px;"><span class="counter" data-target="20">0</span>+</h2><p>Program</p></div>
        <div><h2 style="color:var(--accent); font-size:3rem; margin-bottom:10px;"><span class="counter" data-target="50">0</span>+</h2><p>Prestasi</p></div>
    </div>
</section>

<!-- KEGIATAN SEKOLAH -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Kegiatan</span>
            <h2>Kegiatan Sekolah Terbaru</h2>
        </div>
        <div class="activity-grid">
            <?php foreach($home_activities as $index => $activity): ?>
            <div class="card activity-card">
                <div class="activity-photo">
                    <img src="<?php echo esc($activity['image'] ?: SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg'); ?>" data-fallback="<?php echo SITE_URL; ?>/frontend/assets/images/school/gedung-sekolah.jpeg" alt="<?php echo esc($activity['title']); ?>" loading="lazy">
                    <?php if($activity['subtitle']): ?><span><?php echo esc($activity['subtitle']); ?></span><?php endif; ?>
                </div>
                <div class="card-body">
                    <h3><?php echo esc($activity['title']); ?></h3>
                    <p><?php echo esc(mb_strimwidth($activity['description'],0,115,'...')); ?></p>
                    <?php if($activity['link_url']): ?><a class="program-link" href="<?php echo esc($activity['link_url']); ?>" target="_blank" rel="noopener"><?php echo esc($activity['link_label'] ?: 'Lihat di media sosial'); ?> &rarr;</a><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center; margin-top:36px;">
            <a href="kegiatan.php" class="btn btn-outline">Lihat Semua Kegiatan</a>
        </div>
    </div>
</section>

<!-- GALERI -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Galeri</span>
            <h2>Momen di Sekolah Kami</h2>
        </div>
        <div class="album-grid">
            <?php foreach ($gallery_albums as $album): ?>
            <?php $slides = $gallery_album_slides[(int)$album['id']] ?? []; ?>
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
                    <h3><?php echo esc($album['title']); ?></h3>
                    <?php if (!empty($album['description'])): ?>
                    <p><?php echo esc(mb_strimwidth($album['description'], 0, 110, '...')); ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center; margin-top:36px;">
            <a href="galeri.php" class="btn btn-outline">Lihat Semua Galeri</a>
        </div>
    </div>
</section>

<!-- CTA SPMB -->
<section class="section">
    <div class="container">
        <div class="cta-spmb">
            <h2>Penerimaan Murid Baru Telah Dibuka</h2>
            <p>Bergabunglah bersama keluarga besar <?php echo esc(SITE_NAME); ?> dan persiapkan masa depan terbaik untuk putra-putri Anda.</p>
            <div class="cta-actions">
                <a href="form-spmb.php" class="btn btn-gold">DAFTAR SEKARANG</a>
                <a href="https://wa.me/<?php echo esc(SITE_WHATSAPP); ?>" target="_blank" rel="noopener" class="btn btn-outline-light">HUBUNGI WHATSAPP</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-hero-slider]');
    if (!root) return;
    const slides = [...root.querySelectorAll('.hero-slide')];
    const copies = [...root.querySelectorAll('.hero-copy')];
    const dots = [...root.querySelectorAll('[data-hero-go]')];
    const counter = root.querySelector('[data-hero-counter]');
    let current = 0;
    let timer = null;

    const schedule = () => {
        window.clearTimeout(timer);
        const slide = slides[current];
        if (!slide) return;
        const video = slide.querySelector('video');
        if (video) {
            video.currentTime = 0;
            const playPromise = video.play();
            if (playPromise) playPromise.catch(() => { timer = window.setTimeout(next, 3000); });
        } else {
            timer = window.setTimeout(next, 3000);
        }
    };
    const show = (index) => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => { slide.classList.toggle('active', i === current); const video=slide.querySelector('video'); if(video && i!==current) video.pause(); });
        copies.forEach((copy, i) => copy.classList.toggle('active', i === current));
        dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
        if (counter) counter.textContent = String(current + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
        schedule();
    };
    const next = () => show(current + 1);
    slides.forEach((slide) => { const video=slide.querySelector('video'); if(video) video.addEventListener('ended', next); });
    dots.forEach((dot) => dot.addEventListener('click', () => show(Number(dot.dataset.heroGo))));
    const previousButton=root.querySelector('[data-hero-prev]');
    const nextButton=root.querySelector('[data-hero-next]');
    if(previousButton) previousButton.addEventListener('click',()=>show(current-1));
    if(nextButton) nextButton.addEventListener('click',()=>show(current+1));
    if (slides.length > 1) schedule();

    // Parallax ringan hanya pada media hero; navbar tetap utuh saat sticky.
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let parallaxFrame = null;
    const updateParallax = () => {
        parallaxFrame = null;
        if (reduceMotion.matches) {
            root.style.removeProperty('--hero-parallax');
            return;
        }
        const bounds = root.getBoundingClientRect();
        if (bounds.bottom <= 0 || bounds.top >= window.innerHeight) return;
        const distance = Math.max(0, -bounds.top);
        const limit = window.innerWidth <= 768 ? 24 : 48;
        root.style.setProperty('--hero-parallax', Math.min(limit, distance * 0.07).toFixed(2) + 'px');
    };
    const requestParallax = () => {
        if (parallaxFrame !== null) return;
        parallaxFrame = window.requestAnimationFrame(updateParallax);
    };
    window.addEventListener('scroll', requestParallax, { passive: true });
    window.addEventListener('resize', requestParallax, { passive: true });
    reduceMotion.addEventListener?.('change', requestParallax);
    updateParallax();
});

// Counter Animation
document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('.counter');
    const speed = 200;

    const animateCounters = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const updateCount = () => {
                    const target = +counter.getAttribute('data-target');
                    const count = +counter.innerText;
                    const inc = target / speed;

                    if (count < target) {
                        counter.innerText = Math.ceil(count + inc);
                        setTimeout(updateCount, 15);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCount();
                observer.unobserve(counter);
            }
        });
    };

    const counterObserver = new IntersectionObserver(animateCounters, {
        threshold: 0.5
    });

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });
});

// Achievement filter
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.achievement-tabs').forEach((tabs) => {
        const cards = tabs.parentElement.querySelectorAll('.achieve-card[data-achievement-unit]');
        tabs.querySelectorAll('[data-achievement-filter]').forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.achievementFilter;
                tabs.querySelectorAll('[data-achievement-filter]').forEach((tab) => tab.classList.remove('active'));
                button.classList.add('active');

                cards.forEach((card) => {
                    const isVisible = filter === 'semua' || card.dataset.achievementUnit === filter;
                    window.clearTimeout(card._achievementFilterTimer);
                    if (isVisible) {
                        card.classList.remove('is-hidden');
                        requestAnimationFrame(() => card.classList.remove('is-faded'));
                    } else {
                        card.classList.add('is-faded');
                        card._achievementFilterTimer = window.setTimeout(() => card.classList.add('is-hidden'), 240);
                    }
                });
            });
        });
    });
});

// Gallery album carousel
document.addEventListener('DOMContentLoaded', () => {
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
