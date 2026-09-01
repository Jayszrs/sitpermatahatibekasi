<?php
/**
 * Migrasi minimum untuk tabel yang dipakai halaman publik.
 * Seluruh statement bersifat idempotent dan tidak menghapus data lama.
 */

function ensure_public_schema(PDO $pdo): void
{
    $siteBase = defined('SITE_URL') ? SITE_URL : 'http://localhost/' . basename(dirname(__DIR__, 2));
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(80) PRIMARY KEY,
        description VARCHAR(255) NOT NULL,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $tableExists = static function (string $table) use ($pdo): bool {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
        $stmt->execute([DB_NAME, $table]);
        return (int) $stmt->fetchColumn() > 0;
    };
    $columnExists = static function (string $table, string $column) use ($pdo): bool {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
        $stmt->execute([DB_NAME, $table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    };
    $recordMigration = static function (string $version, string $description) use ($pdo): void {
        $stmt = $pdo->prepare('INSERT IGNORE INTO schema_migrations (version,description) VALUES (?,?)');
        $stmt->execute([$version, $description]);
    };

    // Perbarui URL aset localhost lama ketika nama folder project berubah.
    // Hanya kolom URL media yang disentuh; data akademik dan pengguna tetap utuh.
    $legacyLocalBases = [
        'http://localhost/school-website',
        'http://127.0.0.1/school-website',
        'http://localhost/sitpermatahatibekasi',
        'http://127.0.0.1/sitpermatahatibekasi',
    ];
    $mediaColumns = [
        'news' => ['image'],
        'gallery' => ['image'],
        'gallery_photos' => ['image'],
        'hero_media' => ['media_url', 'poster_url', 'cta_url'],
        'brochures' => ['cover_image', 'file_url'],
        'job_vacancies' => ['image'],
        'site_content_items' => ['image'],
        'site_profile' => ['image'],
        'unit_gallery_photos' => ['image'],
    ];
    foreach ($mediaColumns as $table => $columns) {
        if (!$tableExists($table)) continue;
        foreach ($columns as $column) {
            if (!$columnExists($table, $column)) continue;
            foreach ($legacyLocalBases as $legacyBase) {
                if ($legacyBase === $siteBase) continue;
                $sql = sprintf('UPDATE `%s` SET `%s`=REPLACE(`%s`, ?, ?) WHERE `%s` LIKE ?', $table, $column, $column, $column);
                $replace = $pdo->prepare($sql);
                $replace->execute([$legacyBase, $siteBase, $legacyBase . '/%']);
            }
        }
    }
    $recordMigration('20260901-dynamic-base-url', 'URL aset dan cookie mengikuti nama folder aplikasi');

    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_albums (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        slug VARCHAR(190) NOT NULL UNIQUE,
        description VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_gallery_album_active (is_active, sort_order, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        album_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        image VARCHAR(255) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_gallery_photo_album (album_id, sort_order, id),
        CONSTRAINT fk_gallery_photo_album FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS job_vacancies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        slug VARCHAR(190) NOT NULL UNIQUE,
        unit VARCHAR(80) NOT NULL,
        department VARCHAR(100) NOT NULL,
        employment_type VARCHAR(60) NOT NULL,
        work_location VARCHAR(180) NOT NULL,
        education VARCHAR(180) NULL,
        experience VARCHAR(180) NULL,
        summary VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        responsibilities TEXT NOT NULL,
        requirements TEXT NOT NULL,
        benefits TEXT NULL,
        salary_note VARCHAR(150) NULL,
        deadline DATE NULL,
        is_featured TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_job_public (is_active, deadline, created_at),
        INDEX idx_job_filter (unit, employment_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS job_applications (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        vacancy_id INT NOT NULL,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        city VARCHAR(100) NULL,
        education VARCHAR(180) NULL,
        experience_years DECIMAL(4,1) NOT NULL DEFAULT 0,
        cover_letter TEXT NOT NULL,
        cv_file VARCHAR(255) NOT NULL,
        cv_original_name VARCHAR(255) NOT NULL,
        portfolio_url VARCHAR(255) NULL,
        status ENUM('baru','ditinjau','wawancara','diterima','ditolak') NOT NULL DEFAULT 'baru',
        admin_notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_application_vacancy (vacancy_id, status, created_at),
        CONSTRAINT fk_application_vacancy FOREIGN KEY (vacancy_id) REFERENCES job_vacancies(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS hero_media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        eyebrow VARCHAR(180) NULL,
        description VARCHAR(500) NULL,
        media_type ENUM('image','video') NOT NULL DEFAULT 'image',
        media_url VARCHAR(255) NOT NULL,
        poster_url VARCHAR(255) NULL,
        cta_label VARCHAR(80) NULL,
        cta_url VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_hero_public (is_active, sort_order, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS brochures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        unit_slug VARCHAR(30) NOT NULL UNIQUE,
        unit_name VARCHAR(100) NOT NULL,
        headline VARCHAR(180) NOT NULL,
        description TEXT NOT NULL,
        cover_image VARCHAR(255) NULL,
        file_url VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_brochure_public (is_active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS unit_gallery_photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        unit_slug VARCHAR(30) NOT NULL,
        title VARCHAR(180) NOT NULL,
        description VARCHAR(255) NULL,
        image VARCHAR(255) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_unit_gallery_public (unit_slug, is_active, sort_order, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    foreach ([
        'audience' => "ALTER TABLE brochures ADD COLUMN audience VARCHAR(180) NULL AFTER description",
        'highlights' => "ALTER TABLE brochures ADD COLUMN highlights TEXT NULL AFTER audience",
        'facilities' => "ALTER TABLE brochures ADD COLUMN facilities TEXT NULL AFTER highlights",
        'schedule_info' => "ALTER TABLE brochures ADD COLUMN schedule_info VARCHAR(255) NULL AFTER facilities",
    ] as $column => $sql) {
        if (!$columnExists('brochures', $column)) $pdo->exec($sql);
    }
    if (!$columnExists('job_vacancies', 'image')) {
        $pdo->exec("ALTER TABLE job_vacancies ADD COLUMN image VARCHAR(255) NULL AFTER salary_note");
    }

    if ($tableExists('site_content_items')) {
        if (!$columnExists('site_content_items', 'link_url')) {
            $pdo->exec("ALTER TABLE site_content_items ADD COLUMN link_url VARCHAR(255) NULL AFTER extra");
        }
        if (!$columnExists('site_content_items', 'link_label')) {
            $pdo->exec("ALTER TABLE site_content_items ADD COLUMN link_label VARCHAR(80) NULL AFTER link_url");
        }
        $recordMigration('20260828-content-links', 'Tautan publikasi untuk program, prestasi, dan kegiatan');
        $foundationCount = $pdo->prepare("SELECT COUNT(*) FROM site_content_items WHERE type='foundation'");
        $foundationCount->execute();
        if ((int)$foundationCount->fetchColumn() === 0) {
            $insertFoundation = $pdo->prepare("INSERT INTO site_content_items (type,title,subtitle,description,sort_order,is_active) VALUES ('foundation',?,?,?,?,1)");
            $insertFoundation->execute(['Nama Ketua Yayasan','Ketua Yayasan','Menetapkan arah strategis yayasan dan memastikan penyelenggaraan pendidikan berjalan sesuai visi lembaga.',1]);
            $insertFoundation->execute(['Nama Sekretaris Yayasan','Sekretaris Yayasan','Mengelola tata kelola, dokumentasi, dan koordinasi kelembagaan yayasan.',2]);
            $insertFoundation->execute(['Nama Bendahara Yayasan','Bendahara Yayasan','Mengelola perencanaan dan pertanggungjawaban keuangan yayasan secara amanah.',3]);
        }
        $achievementLinks = [
            'Daycare' => 'https://www.instagram.com/daycarepermatahati.bekasi/',
            'TKIT' => 'https://www.instagram.com/tkitpermatahatibekasi/',
            'SDIT' => 'https://www.instagram.com/sditphbekasi/',
            'SMPIT' => 'https://www.instagram.com/smpit_permatahati/?hl=id',
        ];
        $linkAchievement = $pdo->prepare("UPDATE site_content_items SET link_url=?,link_label='Lihat Publikasi' WHERE type='achievement' AND extra=? AND (link_url IS NULL OR link_url='')");
        foreach ($achievementLinks as $unit => $url) $linkAchievement->execute([$url, $unit]);
    }

    if ($tableExists('spmb_registrations')) {
        if (!$columnExists('spmb_registrations', 'academic_year')) {
            $pdo->exec("ALTER TABLE spmb_registrations ADD COLUMN academic_year VARCHAR(9) NULL AFTER level");
        }
        if (!$columnExists('spmb_registrations', 'admission_track')) {
            $pdo->exec("ALTER TABLE spmb_registrations ADD COLUMN admission_track ENUM('reguler','waiting_list') NOT NULL DEFAULT 'reguler' AFTER academic_year");
        }
        $currentStart = (int) date('Y');
        $currentYear = $currentStart . '/' . ($currentStart + 1);
        $stmt = $pdo->prepare("UPDATE spmb_registrations SET academic_year=? WHERE academic_year IS NULL OR academic_year=''");
        $stmt->execute([$currentYear]);
        $recordMigration('20260828-spmb-academic-year', 'Tahun ajaran dan waiting list SPMB');
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM hero_media')->fetchColumn() === 0) {
        $seedHero = $pdo->prepare('INSERT INTO hero_media (title,eyebrow,description,media_type,media_url,cta_label,cta_url,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,1)');
        $seedHero->execute(['Sekolah Islam Terpadu Permata Hati Bekasi','SPMB '.date('Y').'/'.(date('Y') + 1),'Membentuk generasi sholeh, cerdas, mandiri, dan berwawasan global.','image',$siteBase.'/frontend/assets/images/school/gedung-sekolah.jpeg','Daftar SPMB',$siteBase.'/spmb.php',1]);
        $seedHero->execute(['Belajar, Bertumbuh, dan Berakhlak','Lingkungan Pendidikan Islami','Pembelajaran akademik, Al-Quran, dan pembinaan karakter dalam lingkungan yang hangat.','image',$siteBase.'/frontend/assets/images/school/hero-school.png','Kenali Sekolah Kami',$siteBase.'/tentang.php',2]);
        $seedHero->execute(['Empat Unit, Satu Visi Pendidikan','Daycare · TKIT · SDIT · SMPIT','Pendampingan pendidikan berkelanjutan sesuai tahap tumbuh kembang anak.','image',$siteBase.'/frontend/assets/images/school/gedung-smpit.jpeg','Lihat Unit Sekolah',$siteBase.'/unit.php',3]);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM brochures')->fetchColumn() === 0) {
        $seedBrochure = $pdo->prepare('INSERT INTO brochures (unit_slug,unit_name,headline,description,cover_image,sort_order,is_active) VALUES (?,?,?,?,?,?,1)');
        $brochures = [
            ['daycare','Daycare','Tumbuh Nyaman Sejak Langkah Pertama','Program pendampingan, stimulasi sensorik, motorik, bahasa, dan sosial dalam lingkungan Islami yang aman.',$siteBase.'/frontend/assets/images/activities/daycare-kegiatan.jpeg',1],
            ['tkit','TKIT','Bermain, Belajar, dan Beradab','Pembelajaran sentra yang menumbuhkan kemandirian, kreativitas, kebiasaan ibadah, dan kesiapan sekolah.',$siteBase.'/frontend/assets/images/activities/tkit-kegiatan.jpeg',2],
            ['sdit','SDIT','Fondasi Akademik dan Karakter yang Kuat','Literasi, numerasi, Al-Quran, proyek, dan pembinaan karakter untuk masa sekolah dasar yang bermakna.',$siteBase.'/frontend/assets/images/activities/sdit-kegiatan.jpeg',3],
            ['smpit','SMPIT','Siap Memimpin dan Berkarya','Program remaja muslim yang menguatkan akademik, kepemimpinan, kemandirian, dan kecakapan digital.',$siteBase.'/frontend/assets/images/activities/smpit-kegiatan.jpeg',4],
        ];
        foreach ($brochures as $brochure) $seedBrochure->execute($brochure);
    }
    $brochurePromotions = [
        'daycare' => [
            'Orang tua dengan anak usia 1-4 tahun',
            "Stimulasi sensorik dan motorik\nPembiasaan doa dan adab harian\nPendampingan tumbuh kembang\nLaporan aktivitas kepada orang tua",
            "Ruang aktivitas aman dan higienis\nArea bermain edukatif\nMedia sensorik sesuai usia\nPengasuh dan pendidik yang hangat",
            'Program harian yang fleksibel dan ramah ritme tumbuh kembang anak.',
            $siteBase.'/frontend/assets/images/brochures/daycare-promo.png',
        ],
        'tkit' => [
            'Anak usia 4-6 tahun yang siap bertumbuh melalui bermain',
            "Pembelajaran berbasis sentra\nTahsin dan hafalan surat pendek\nPenguatan kemandirian\nProyek seni, bahasa, dan eksplorasi",
            "Kelas ramah anak\nArea bermain aktif\nSudut baca dan kreativitas\nMedia belajar tematik",
            'Pembelajaran aktif lima hari yang menyeimbangkan bermain, adab, dan kesiapan sekolah.',
            $siteBase.'/frontend/assets/images/brochures/tkit-promo.png',
        ],
        'sdit' => [
            'Siswa sekolah dasar yang membutuhkan fondasi akademik dan karakter',
            "Literasi dan numerasi terarah\nTahfidz, tahsin, dan pembiasaan ibadah\nProject based learning\nPengembangan minat dan bakat",
            "Ruang kelas nyaman\nMasjid dan sarana ibadah\nPerpustakaan dan media digital\nLapangan serta kegiatan ekstrakurikuler",
            'Program full day terintegrasi dengan pendampingan akademik, Al-Quran, dan karakter.',
            $siteBase.'/frontend/assets/images/brochures/sdit-promo.png',
        ],
        'smpit' => [
            'Remaja usia sekolah menengah yang siap mandiri dan memimpin',
            "Tahfidz lanjutan dan mentoring remaja\nKelas sains dan teknologi\nLeadership project\nPersiapan akademik berkelanjutan",
            "Laboratorium pembelajaran\nPerpustakaan dan perangkat digital\nLapangan olahraga\nRuang organisasi dan kolaborasi",
            'Program terpadu untuk membangun kompetensi, akhlak, kepemimpinan, dan kesiapan masa depan.',
            $siteBase.'/frontend/assets/images/brochures/smpit-promo.png',
        ],
    ];
    $updateBrochure = $pdo->prepare("UPDATE brochures SET audience=?,highlights=?,facilities=?,schedule_info=?,cover_image=? WHERE unit_slug=? AND (audience IS NULL OR audience='')");
    foreach ($brochurePromotions as $unitSlug => $promotion) {
        $updateBrochure->execute([$promotion[0],$promotion[1],$promotion[2],$promotion[3],$promotion[4],$unitSlug]);
    }
    $setDefaultBrochurePdf=$pdo->prepare("UPDATE brochures SET file_url=? WHERE unit_slug=? AND (file_url IS NULL OR file_url='')");
    foreach(array_keys($brochurePromotions) as $unitSlug) $setDefaultBrochurePdf->execute([$siteBase.'/frontend/assets/brochures/brosur-'.$unitSlug.'.pdf',$unitSlug]);

    if ((int)$pdo->query('SELECT COUNT(*) FROM unit_gallery_photos')->fetchColumn() === 0) {
        $seedUnitPhoto = $pdo->prepare('INSERT INTO unit_gallery_photos (unit_slug,title,description,image,sort_order,is_active) VALUES (?,?,?,?,?,1)');
        $unitPhotos = [
            ['daycare','Ruang Bermain Sensorik','Lingkungan bermain yang aman untuk eksplorasi anak.',$siteBase.'/frontend/assets/images/brochures/daycare-promo.png',1],
            ['daycare','Aktivitas Daycare','Kegiatan motorik dan interaksi sosial yang didampingi pendidik.',$siteBase.'/frontend/assets/images/activities/daycare-kegiatan.jpeg',2],
            ['tkit','Kelas Kreatif TKIT','Belajar melalui karya, cerita, dan permainan terarah.',$siteBase.'/frontend/assets/images/brochures/tkit-promo.png',1],
            ['tkit','Eksplorasi Ceria','Aktivitas yang mengembangkan rasa ingin tahu dan kemandirian.',$siteBase.'/frontend/assets/images/activities/tkit-kegiatan.jpeg',2],
            ['sdit','Kolaborasi Sains SDIT','Pembelajaran akademik yang aktif dan kontekstual.',$siteBase.'/frontend/assets/images/brochures/sdit-promo.png',1],
            ['sdit','Kegiatan Siswa SDIT','Pengembangan potensi melalui kegiatan kelas dan ekstrakurikuler.',$siteBase.'/frontend/assets/images/activities/sdit-kegiatan.jpeg',2],
            ['sdit','Gedung Utama','Lingkungan belajar SIT Permata Hati Bekasi.',$siteBase.'/frontend/assets/images/school/gedung-sekolah.jpeg',3],
            ['smpit','Innovation Project SMPIT','Kolaborasi teknologi dan kepemimpinan siswa.',$siteBase.'/frontend/assets/images/brochures/smpit-promo.png',1],
            ['smpit','Riset dan Diskusi','Pembelajaran remaja yang aktif, ilmiah, dan berkarakter.',$siteBase.'/frontend/assets/images/activities/smpit-kegiatan.jpeg',2],
            ['smpit','Gedung SMPIT','Fasilitas pendidikan jenjang sekolah menengah.',$siteBase.'/frontend/assets/images/school/gedung-smpit.jpeg',3],
        ];
        foreach ($unitPhotos as $photo) $seedUnitPhoto->execute($photo);
    }
    $recordMigration('20260829-public-experience', 'Konten brosur lengkap, gambar lowongan, dan galeri unit tanpa batas');
    $recordMigration('20260828-hero-brochures', 'Hero multimedia dan brosur per unit');

    if ((int)$pdo->query('SELECT COUNT(*) FROM job_vacancies')->fetchColumn() === 0) {
        $seed = $pdo->prepare('INSERT INTO job_vacancies (title,slug,unit,department,employment_type,work_location,education,experience,summary,description,responsibilities,requirements,benefits,salary_note,deadline,is_featured,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,DATE_ADD(CURDATE(), INTERVAL ? DAY),?,1)');
        $jobs = [
            ['Guru Kelas SDIT','guru-kelas-sdit','SDIT','Pendidikan','Penuh Waktu','Tambun Selatan, Bekasi','S1 PGSD/Pendidikan','Minimal 1 tahun','Mendampingi pembelajaran tematik, literasi, numerasi, dan pembentukan karakter Islami siswa SDIT.','Kami mencari pendidik yang hangat, adaptif, dan mampu menghadirkan pengalaman belajar bermakna bagi siswa SDIT.','Menyusun dan menjalankan modul ajar\nMelakukan asesmen perkembangan siswa\nBerkomunikasi aktif dengan orang tua\nBerpartisipasi dalam program sekolah','Muslim/Muslimah dan berakhlak baik\nS1 PGSD atau bidang pendidikan relevan\nMampu bekerja dalam tim\nMenguasai teknologi pembelajaran dasar','Lingkungan kerja Islami\nPelatihan dan pengembangan guru\nMakan siang dan tunjangan sesuai kebijakan','Kompetitif',60,1],
            ['Guru Tahfidz dan Tahsin','guru-tahfidz-tahsin','Semua Unit','Al Quran','Penuh Waktu','Tambun Selatan, Bekasi','S1 atau pesantren/mahad relevan','Minimal 1 tahun','Membina bacaan, hafalan, adab, serta kecintaan siswa terhadap Al Quran.','Posisi ini berperan menjaga kualitas program Al Quran lintas unit melalui pendampingan yang terukur dan menyenangkan.','Membimbing tahsin dan tahfidz\nMenyusun target hafalan\nMencatat perkembangan siswa\nMendukung kegiatan ruhiyah sekolah','Bacaan Al Quran baik dan bersanad menjadi nilai tambah\nBerpengalaman mengajar anak\nSabar, komunikatif, dan disiplin','Program pengembangan kompetensi\nLingkungan kerja suportif\nTunjangan sesuai kebijakan','Kompetitif',75,1],
            ['Guru Pendamping TKIT','guru-pendamping-tkit','TKIT','Pendidikan Anak Usia Dini','Penuh Waktu','Tambun Selatan, Bekasi','S1 PAUD/Psikologi/Pendidikan','Terbuka untuk fresh graduate','Mendampingi aktivitas bermain-belajar, pembiasaan adab, dan perkembangan anak usia dini.','Kami membuka kesempatan bagi pendidik kreatif yang menyukai dunia anak dan pembelajaran berbasis eksplorasi.','Menyiapkan area dan media bermain\nMendampingi rutinitas kelas\nMencatat perkembangan anak\nMenjaga komunikasi dengan wali murid','Menyukai dunia anak\nKreatif dan komunikatif\nMampu bekerja dalam tim\nSehat jasmani dan rohani','Mentoring guru senior\nPelatihan PAUD\nLingkungan kerja Islami','Kompetitif',45,0],
            ['Staf Humas dan Konten Digital','staf-humas-konten-digital','Yayasan','Humas','Penuh Waktu','Tambun Selatan, Bekasi','D3/S1 Komunikasi/Desain/Multimedia','Minimal 1 tahun','Mengelola publikasi, dokumentasi, media sosial, dan komunikasi digital sekolah.','Posisi ini membantu menyampaikan cerita dan informasi sekolah secara akurat, menarik, serta konsisten dengan identitas lembaga.','Menyusun kalender konten\nMendokumentasikan kegiatan\nMengelola media sosial dan website\nBerkoordinasi dengan seluruh unit','Mampu menulis copy dengan baik\nMenguasai desain atau video dasar\nMemiliki portofolio\nResponsif dan terorganisir','Perangkat kerja pendukung\nKesempatan mengembangkan portofolio\nTunjangan sesuai kebijakan','Kompetitif',60,0],
        ];
        foreach ($jobs as $job) $seed->execute($job);
    }
    $pdo->exec("UPDATE job_vacancies SET responsibilities=REPLACE(responsibilities, '\\\\n', CHAR(10)), requirements=REPLACE(requirements, '\\\\n', CHAR(10)), benefits=REPLACE(benefits, '\\\\n', CHAR(10)) WHERE responsibilities LIKE '%\\\\n%' OR requirements LIKE '%\\\\n%' OR benefits LIKE '%\\\\n%'");
    $jobImages = [
        'Daycare' => $siteBase.'/frontend/assets/images/brochures/daycare-promo.png',
        'TKIT' => $siteBase.'/frontend/assets/images/brochures/tkit-promo.png',
        'SDIT' => $siteBase.'/frontend/assets/images/brochures/sdit-promo.png',
        'SMPIT' => $siteBase.'/frontend/assets/images/brochures/smpit-promo.png',
        'Semua Unit' => $siteBase.'/frontend/assets/images/school/hero-school.png',
        'Yayasan' => $siteBase.'/frontend/assets/images/school/hero-school.png',
    ];
    $updateJobImage=$pdo->prepare("UPDATE job_vacancies SET image=? WHERE unit=? AND (image IS NULL OR image='')");
    foreach($jobImages as $unitName=>$imageUrl) $updateJobImage->execute([$imageUrl,$unitName]);

    // Pertahankan isi tabel galeri versi lama jika project diperbarui tanpa import ulang SQL.
    $legacyCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME='gallery'");
    $legacyCheck->execute([DB_NAME]);
    if ((int)$legacyCheck->fetchColumn() === 0) return;

    $pdo->exec("INSERT IGNORE INTO gallery_albums (title, slug, description, sort_order, is_active)
        VALUES ('Dokumentasi Sekolah', 'dokumentasi-sekolah', 'Dokumentasi fasilitas dan aktivitas sekolah.', 5, 1)");
    $albumId = (int)$pdo->query("SELECT id FROM gallery_albums WHERE slug='dokumentasi-sekolah' LIMIT 1")->fetchColumn();
    if (!$albumId) return;

    $legacyPhotos = $pdo->query('SELECT title, image, description, created_at FROM gallery ORDER BY created_at ASC, id ASC')->fetchAll();
    $exists = $pdo->prepare('SELECT COUNT(*) FROM gallery_photos WHERE image=?');
    $insert = $pdo->prepare('INSERT INTO gallery_photos (album_id,title,image,description,sort_order,created_at) VALUES (?,?,?,?,?,?)');
    foreach ($legacyPhotos as $index => $photo) {
        $exists->execute([$photo['image']]);
        if ((int)$exists->fetchColumn() > 0) continue;
        $insert->execute([$albumId, $photo['title'], $photo['image'], $photo['description'] ?: null, $index + 1, $photo['created_at']]);
    }
}
