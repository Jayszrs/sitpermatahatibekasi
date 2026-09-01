<?php
/**
 * Autentikasi dan otorisasi portal internal.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('tbz_portal_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => defined('APP_COOKIE_PATH') ? APP_COOKIE_PATH : '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function portal_bootstrap_database(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        username VARCHAR(80) NULL,
        email VARCHAR(190) NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','humas','kasir') NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_login_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $userColumnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'portal_users' AND COLUMN_NAME = ?");
    $userColumnCheck->execute([DB_NAME, 'username']);
    if ((int)$userColumnCheck->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE portal_users ADD COLUMN username VARCHAR(80) NULL AFTER name");
    }
    $emailNullable = $pdo->prepare("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='portal_users' AND COLUMN_NAME='email'");
    $emailNullable->execute([DB_NAME]);
    if ($emailNullable->fetchColumn() === 'NO') {
        $pdo->exec("ALTER TABLE portal_users MODIFY email VARCHAR(190) NULL");
    }
    $pdo->exec("UPDATE portal_users SET email=NULL WHERE (username='admin' AND email='admin@tbz.sch.id') OR (username='humas' AND email='humas@tbz.sch.id') OR (username='kasir' AND email='kasir@tbz.sch.id')");
    $pdo->exec("UPDATE portal_users SET username = role WHERE username IS NULL OR username = ''");
    $usernameIndex = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'portal_users' AND INDEX_NAME = 'uq_portal_username'");
    $usernameIndex->execute([DB_NAME]);
    if ((int)$usernameIndex->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE portal_users ADD UNIQUE INDEX uq_portal_username (username)");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_activity_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        description VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_activity_user (user_id),
        CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES portal_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $columns = [
        'payment_status' => "ALTER TABLE spmb_registrations ADD COLUMN payment_status ENUM('belum_bayar','sebagian','lunas') NOT NULL DEFAULT 'belum_bayar' AFTER previous_school",
        'payment_amount' => "ALTER TABLE spmb_registrations ADD COLUMN payment_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER payment_status",
        'payment_method' => "ALTER TABLE spmb_registrations ADD COLUMN payment_method VARCHAR(50) NULL AFTER payment_amount",
        'payment_date' => "ALTER TABLE spmb_registrations ADD COLUMN payment_date DATE NULL AFTER payment_method",
        'payment_notes' => "ALTER TABLE spmb_registrations ADD COLUMN payment_notes TEXT NULL AFTER payment_date",
        'payment_updated_by' => "ALTER TABLE spmb_registrations ADD COLUMN payment_updated_by INT NULL AFTER payment_notes",
        'registration_number' => "ALTER TABLE spmb_registrations ADD COLUMN registration_number VARCHAR(40) NULL AFTER id",
        'student_nik' => "ALTER TABLE spmb_registrations ADD COLUMN student_nik VARCHAR(30) NULL AFTER student_name",
        'gender' => "ALTER TABLE spmb_registrations ADD COLUMN gender ENUM('L','P') NULL AFTER student_nik",
        'birth_place' => "ALTER TABLE spmb_registrations ADD COLUMN birth_place VARCHAR(100) NULL AFTER gender",
        'birth_date' => "ALTER TABLE spmb_registrations ADD COLUMN birth_date DATE NULL AFTER birth_place",
        'address' => "ALTER TABLE spmb_registrations ADD COLUMN address TEXT NULL AFTER previous_school",
        'parent_nik' => "ALTER TABLE spmb_registrations ADD COLUMN parent_nik VARCHAR(30) NULL AFTER parent_name",
        'family_card_number' => "ALTER TABLE spmb_registrations ADD COLUMN family_card_number VARCHAR(30) NULL AFTER parent_nik",
        'registration_status' => "ALTER TABLE spmb_registrations ADD COLUMN registration_status ENUM('baru','verifikasi','lulus','cadangan','ditolak','daftar_ulang') NOT NULL DEFAULT 'baru' AFTER address",
        'document_status' => "ALTER TABLE spmb_registrations ADD COLUMN document_status ENUM('belum_lengkap','lengkap','terverifikasi') NOT NULL DEFAULT 'belum_lengkap' AFTER registration_status",
        'academic_year' => "ALTER TABLE spmb_registrations ADD COLUMN academic_year VARCHAR(9) NULL AFTER level",
        'admission_track' => "ALTER TABLE spmb_registrations ADD COLUMN admission_track ENUM('reguler','waiting_list') NOT NULL DEFAULT 'reguler' AFTER academic_year",
    ];

    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'spmb_registrations' AND COLUMN_NAME = ?");
    foreach ($columns as $name => $sql) {
        $check->execute([DB_NAME, $name]);
        if ((int)$check->fetchColumn() === 0) {
            $pdo->exec($sql);
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS site_content_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(30) NOT NULL,
        title VARCHAR(180) NOT NULL,
        subtitle VARCHAR(180) NULL,
        description TEXT NOT NULL,
        image VARCHAR(255) NULL,
        badge VARCHAR(80) NULL,
        year VARCHAR(10) NULL,
        extra TEXT NULL,
        link_url VARCHAR(255) NULL,
        link_label VARCHAR(80) NULL,
        unit_slug VARCHAR(30) NULL,
        education VARCHAR(255) NULL,
        teaching_scope VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_content_type (type, is_active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS site_profile (
        id TINYINT PRIMARY KEY,
        history_title VARCHAR(180) NOT NULL,
        history_content TEXT NOT NULL,
        vision TEXT NOT NULL,
        mission TEXT NOT NULL,
        image VARCHAR(255) NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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
        unit_slug VARCHAR(30) NULL,
        instagram_url VARCHAR(255) NULL,
        published_at DATE NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_gallery_photo_album (album_id, sort_order, id),
        CONSTRAINT fk_gallery_photo_album FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS spmb_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        registration_id INT NOT NULL,
        receipt_number VARCHAR(50) NOT NULL UNIQUE,
        payment_type VARCHAR(50) NOT NULL,
        amount DECIMAL(14,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_date DATE NOT NULL,
        reference_number VARCHAR(100) NULL,
        payer_name VARCHAR(150) NULL,
        notes TEXT NULL,
        status ENUM('verified','cancelled') NOT NULL DEFAULT 'verified',
        recorded_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_payment_registration (registration_id),
        CONSTRAINT fk_payment_registration FOREIGN KEY (registration_id) REFERENCES spmb_registrations(id) ON DELETE CASCADE,
        CONSTRAINT fk_payment_recorder FOREIGN KEY (recorded_by) REFERENCES portal_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    portal_migrate_legacy_brand_content($pdo);
    portal_seed_gallery_albums($pdo);
    portal_seed_site_content($pdo);
    // Seed lama menyimpan pemisah baris sebagai teks "\\n"; normalkan agar tag tampil terpisah.
    $pdo->exec("UPDATE site_content_items SET extra=REPLACE(extra, '\\\\n', CHAR(10)) WHERE extra LIKE '%\\\\n%'");

    $defaults = [
        ['Administrator', 'admin', 'AdminPHB#2026', 'admin'],
        ['Tim Humas', 'humas', 'HumasPHB#2026', 'humas'],
        ['Kasir SPMB', 'kasir', 'KasirPHB#2026', 'kasir'],
    ];
    if ((int)$pdo->query('SELECT COUNT(*) FROM portal_users')->fetchColumn() === 0) {
        $insert = $pdo->prepare('INSERT INTO portal_users (name, username, password, role) VALUES (?, ?, ?, ?)');
        foreach ($defaults as [$name, $username, $password, $role]) {
            $insert->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
        }
    }

    // Migrasikan hanya password akun bawaan lama; password yang pernah diganti admin tidak disentuh.
    $legacyPasswords = [
        'admin' => ['AdminTBZ#2026', 'AdminPHB#2026'],
        'humas' => ['HumasTBZ#2026', 'HumasPHB#2026'],
        'kasir' => ['KasirTBZ#2026', 'KasirPHB#2026'],
    ];
    $findDefaultUser = $pdo->prepare('SELECT id,password FROM portal_users WHERE username=? LIMIT 1');
    $updateDefaultPassword = $pdo->prepare('UPDATE portal_users SET password=? WHERE id=?');
    foreach ($legacyPasswords as $username => [$legacyPassword, $currentPassword]) {
        $findDefaultUser->execute([$username]);
        $defaultUser = $findDefaultUser->fetch();
        if ($defaultUser && password_verify($legacyPassword, $defaultUser['password'])) {
            $updateDefaultPassword->execute([password_hash($currentPassword, PASSWORD_DEFAULT), $defaultUser['id']]);
        }
    }
}

/**
 * Bersihkan hanya data demo TBZ lama yang belum pernah dikustomisasi.
 * Pengecekan judul dibuat ketat supaya konten yang sudah diedit dari CRM tidak tertimpa.
 */
function portal_migrate_legacy_brand_content(PDO $pdo): void
{
    $legacySets = [
        'unit' => ['SD Islam Terpadu', 'SMP Islam Terpadu', 'SMA Islam Terpadu'],
        'achievement' => ['Juara 1 Olimpiade Matematika', 'Juara 2 MTQ Pelajar', 'Juara 1 Lomba Sains'],
        'program' => ["Tahfidz Al-Qur'an", 'English Program', 'Character Building', 'Digital Learning', 'Leadership Program'],
        'activity' => ['Pesantren Ramadhan', 'Field Trip', 'Wisuda Tahfidz'],
    ];

    $selectTitles = $pdo->prepare('SELECT title FROM site_content_items WHERE type=? ORDER BY sort_order,id');
    $deleteType = $pdo->prepare('DELETE FROM site_content_items WHERE type=?');
    foreach ($legacySets as $type => $expectedTitles) {
        $selectTitles->execute([$type]);
        $actualTitles = $selectTitles->fetchAll(PDO::FETCH_COLUMN);
        if ($actualTitles === $expectedTitles) {
            $deleteType->execute([$type]);
        }
    }

    $profile = $pdo->query('SELECT history_title FROM site_profile WHERE id=1')->fetchColumn();
    if ($profile === 'Perjalanan LPIT Thariq Bin Ziyad') {
        $stmt = $pdo->prepare('UPDATE site_profile SET history_title=?,history_content=?,vision=?,mission=?,image=? WHERE id=1');
        $stmt->execute([
            'Perjalanan ' . SITE_NAME,
            'Didirikan dengan semangat mencetak generasi sholeh, cerdas, mandiri, dan berakhlak mulia, SIT Permata Hati Bekasi berkembang menjadi sekolah Islam terpadu terpercaya di Tambun Selatan. Kami konsisten memadukan kurikulum nasional, pembelajaran Al-Quran, dan pembinaan karakter.',
            'Menjadi lembaga pendidikan Islam terpadu terdepan yang melahirkan generasi cerdas, berakhlak mulia, dan berdaya saing global.',
            'Menyelenggarakan pendidikan berbasis Al-Quran dan Sunnah, mengembangkan potensi akademik secara optimal, serta membangun karakter dan kepemimpinan sejak dini.',
            SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg',
        ]);
    }

    $newsImages = [
        'pesantren-ramadhan-1447-h-resmi-dibuka' => SITE_URL . '/frontend/assets/images/gallery/masjid-sekolah/masjid-01.jpeg',
        'siswa-raih-juara-1-olimpiade-matematika-nasional' => SITE_URL . '/frontend/assets/images/achievements/sdit-bastian-bachtiar.webp',
        'wisuda-tahfidz-angkatan-xii-berlangsung-khidmat' => SITE_URL . '/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-01.jpeg',
    ];
    $updateNewsImage = $pdo->prepare("UPDATE news SET image=? WHERE slug=? AND image LIKE 'https://placehold.co/%'");
    foreach ($newsImages as $slug => $image) {
        $updateNewsImage->execute([$image, $slug]);
    }

    $galleryImages = [
        'Gedung Sekolah' => SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg',
        'Kegiatan Belajar Mengajar' => SITE_URL . '/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-01.jpeg',
        'Lapangan Olahraga' => SITE_URL . '/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-01.jpeg',
        'Perpustakaan' => SITE_URL . '/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-02.jpeg',
        'Laboratorium Komputer' => SITE_URL . '/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-03.jpeg',
        'Masjid Sekolah' => SITE_URL . '/frontend/assets/images/gallery/masjid-sekolah/masjid-01.jpeg',
    ];
    $updateGalleryPhoto = $pdo->prepare("UPDATE gallery_photos SET image=? WHERE title=? AND image LIKE 'https://placehold.co/%'");
    $legacyGalleryExists = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME='gallery'");
    $legacyGalleryExists->execute([DB_NAME]);
    $updateLegacyGallery = (int) $legacyGalleryExists->fetchColumn() > 0
        ? $pdo->prepare("UPDATE gallery SET image=? WHERE title=? AND image LIKE 'https://placehold.co/%'")
        : null;
    foreach ($galleryImages as $title => $image) {
        $updateGalleryPhoto->execute([$image, $title]);
        if ($updateLegacyGallery) $updateLegacyGallery->execute([$image, $title]);
    }
}

function portal_seed_gallery_albums(PDO $pdo): void
{
    $ensureAlbum = function (string $title, string $slug, ?string $description, int $sortOrder) use ($pdo): int {
        $stmt = $pdo->prepare('INSERT IGNORE INTO gallery_albums (title, slug, description, sort_order, is_active) VALUES (?,?,?,?,1)');
        $stmt->execute([$title, $slug, $description, $sortOrder]);
        $select = $pdo->prepare('SELECT id FROM gallery_albums WHERE slug=? LIMIT 1');
        $select->execute([$slug]);
        return (int)$select->fetchColumn();
    };

    $documentationAlbumId = $ensureAlbum(
        'Dokumentasi Sekolah',
        'dokumentasi-sekolah',
        'Kumpulan dokumentasi fasilitas, suasana, dan aktivitas sekolah.',
        5
    );
    $pdo->prepare('UPDATE gallery_albums SET sort_order=? WHERE slug=?')->execute([5, 'dokumentasi-sekolah']);

    $legacyExists = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME='gallery'");
    $legacyExists->execute([DB_NAME]);
    if ((int)$legacyExists->fetchColumn() > 0) {
        $legacyPhotos = $pdo->query('SELECT title, image, description, created_at FROM gallery ORDER BY created_at ASC, id ASC')->fetchAll();
        $insertLegacy = $pdo->prepare('INSERT INTO gallery_photos (album_id, title, image, description, sort_order, created_at) VALUES (?,?,?,?,?,?)');
        $existsPhoto = $pdo->prepare('SELECT COUNT(*) FROM gallery_photos WHERE image=?');
        foreach ($legacyPhotos as $index => $photo) {
            $existsPhoto->execute([$photo['image']]);
            if ((int)$existsPhoto->fetchColumn() > 0) continue;
            $insertLegacy->execute([
                $documentationAlbumId,
                $photo['title'],
                $photo['image'],
                $photo['description'] ?: null,
                $index + 1,
                $photo['created_at'] ?? date('Y-m-d H:i:s'),
            ]);
        }
    }

    $activityAlbumId = $ensureAlbum(
        'Kegiatan Sekolah',
        'kegiatan-sekolah',
        'Dokumentasi kegiatan belajar, ruang kelas, dan suasana pembelajaran SIT Permata Hati Bekasi.',
        1
    );
    $activityPhotos = [
        ['Pembelajaran Interaktif di Kelas', SITE_URL . '/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-01.jpeg', 'Siswa aktif bertanya dan berdiskusi dalam suasana kelas yang nyaman.'],
        ['Digital Learning dan Diskusi Kelas', SITE_URL . '/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-02.jpeg', 'Pemanfaatan media digital untuk mendukung proses belajar yang fokus dan terarah.'],
        ['Suasana Belajar Nyaman', SITE_URL . '/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-03.jpeg', 'Ruang kelas tertata rapi untuk kegiatan belajar yang tertib dan menyenangkan.'],
        ['Kelas SMPIT Aktif', SITE_URL . '/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-04.jpeg', 'Kegiatan belajar SMPIT yang membangun konsentrasi, adab, dan kemandirian.'],
    ];
    $insertActivity = $pdo->prepare('INSERT INTO gallery_photos (album_id, title, image, description, sort_order) VALUES (?,?,?,?,?)');
    $existsPhoto = $pdo->prepare('SELECT COUNT(*) FROM gallery_photos WHERE image=?');
    foreach ($activityPhotos as $index => $photo) {
        $existsPhoto->execute([$photo[1]]);
        if ((int)$existsPhoto->fetchColumn() > 0) continue;
        $insertActivity->execute([$activityAlbumId, $photo[0], $photo[1], $photo[2], $index + 1]);
    }

    $sportsAlbumId = $ensureAlbum(
        'Kegiatan Olahraga',
        'kegiatan-olahraga',
        'Dokumentasi kegiatan lapangan, permainan bola, dan pembiasaan hidup aktif siswa SIT Permata Hati Bekasi.',
        2
    );
    $pdo->prepare('UPDATE gallery_albums SET sort_order=? WHERE slug=?')->execute([2, 'kegiatan-olahraga']);
    $sportsPhotos = [
        ['Latihan Basket Lapangan', SITE_URL . '/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-01.jpeg', 'Siswa berlatih kerja sama, koordinasi, dan sportivitas melalui permainan basket.'],
        ['Motorik Ceria TKIT', SITE_URL . '/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-02.jpeg', 'Anak-anak belajar menangkap, melempar, dan bekerja sama lewat aktivitas bola yang menyenangkan.'],
        ['Basket Outdoor SMPIT', SITE_URL . '/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-03.jpeg', 'Kegiatan olahraga luar ruang untuk menjaga kebugaran, keberanian, dan kekompakan siswa.'],
        ['Stimulasi Bola Daycare', SITE_URL . '/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-04.jpeg', 'Aktivitas bola ringan untuk melatih motorik kasar anak usia dini dengan suasana aman.'],
        ['Permainan Bola SDIT', SITE_URL . '/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-05.jpeg', 'Siswa menikmati permainan basket sebagai bagian dari kegiatan jasmani yang sehat.'],
    ];
    foreach ($sportsPhotos as $index => $photo) {
        $existsPhoto->execute([$photo[1]]);
        if ((int)$existsPhoto->fetchColumn() > 0) continue;
        $insertActivity->execute([$sportsAlbumId, $photo[0], $photo[1], $photo[2], $index + 1]);
    }

    $mosqueAlbumId = $ensureAlbum(
        'Masjid Sekolah',
        'masjid-sekolah',
        'Dokumentasi pembiasaan ibadah, membaca Al-Quran, dan kegiatan ruhiyah siswa di lingkungan sekolah.',
        3
    );
    $pdo->prepare('UPDATE gallery_albums SET sort_order=? WHERE slug=?')->execute([3, 'masjid-sekolah']);
    $mosquePhotos = [
        ['Tilawah Bersama di Masjid', SITE_URL . '/frontend/assets/images/gallery/masjid-sekolah/masjid-01.jpeg', 'Siswa membaca Al-Quran bersama dalam suasana masjid yang tenang dan khidmat.'],
        ['Halaqah Al-Quran', SITE_URL . '/frontend/assets/images/gallery/masjid-sekolah/masjid-02.jpeg', 'Kegiatan halaqah dan pembiasaan tilawah untuk menguatkan kedekatan siswa dengan Al-Quran.'],
        ['Literasi Islami Anak Usia Dini', SITE_URL . '/frontend/assets/images/gallery/masjid-sekolah/masjid-03.jpeg', 'Anak-anak mengenal bacaan dan adab Islami melalui aktivitas literasi yang lembut dan menyenangkan.'],
    ];
    foreach ($mosquePhotos as $index => $photo) {
        $existsPhoto->execute([$photo[1]]);
        if ((int)$existsPhoto->fetchColumn() > 0) continue;
        $insertActivity->execute([$mosqueAlbumId, $photo[0], $photo[1], $photo[2], $index + 1]);
    }

    $buildingAlbumId = $ensureAlbum(
        'Gedung Sekolah',
        'gedung-sekolah',
        'Dokumentasi gedung dan lingkungan unit pendidikan SIT Permata Hati Bekasi.',
        4
    );
    $pdo->prepare('UPDATE gallery_albums SET sort_order=? WHERE slug=?')->execute([4, 'gedung-sekolah']);
    $buildingPhotos = [
        ['Gedung Daycare, TKIT, dan SDIT', SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg', 'Gedung utama SIT Permata Hati Bekasi di kawasan Buwek Jaya, Tambun Selatan.'],
        ['Gedung SMPIT Permata Hati', SITE_URL . '/frontend/assets/images/school/gedung-smpit.jpeg', 'Gedung SMPIT Permata Hati Bekasi dengan fasilitas belajar dan lapangan sekolah.'],
    ];
    foreach ($buildingPhotos as $index => $photo) {
        $existsPhoto->execute([$photo[1]]);
        if ((int)$existsPhoto->fetchColumn() > 0) continue;
        $insertActivity->execute([$buildingAlbumId, $photo[0], $photo[1], $photo[2], $index + 1]);
    }
}

function portal_seed_site_content(PDO $pdo): void
{
    $seeds = [
        'unit' => [
            ['Daycare Permata Hati Bekasi', 'Daycare', 'Layanan pengasuhan anak usia dini dengan suasana aman, hangat, dan pembiasaan adab Islami sejak awal.', "Stimulasi motorik\nPembiasaan doa\nAktivitas sensorik\nLaporan harian"],
            ['TKIT Permata Hati Bekasi', 'TKIT', 'Jenjang taman kanak-kanak Islam terpadu yang menumbuhkan kemandirian, kreativitas, dan cinta Al-Quran.', "Sentra bermain\nTahsin dasar\nDoa harian\nKemandirian"],
            ['SDIT Permata Hati Bekasi', 'SDIT', 'Pendidikan dasar terpadu yang menguatkan akademik, tahfidz, adab, dan karakter mandiri siswa.', "Tahfidz Juz 30\nLiterasi numerasi\nEkstrakurikuler\nFull Day School"],
            ['SMPIT Permata Hati Bekasi', 'SMPIT', 'Jenjang menengah pertama yang membangun kompetensi akademik, kepemimpinan, dan akhlak remaja muslim.', "Tahfidz lanjutan\nEnglish Club\nKlub Sains\nLeadership Project"],
        ],
        'achievement' => [
            ['Apresiasi Kemandirian Anak', 'Sekolah', 'Penghargaan perkembangan kemandirian dan kerja sama anak melalui aktivitas bermain terarah.', 'Sekolah|2026', SITE_URL . '/frontend/assets/images/activities/daycare-kegiatan.jpeg', 'Daycare'],
            ['Apresiasi Kreativitas TKIT', 'Sekolah', 'Penghargaan untuk kreativitas, keberanian berekspresi, dan kemampuan motorik anak TKIT.', 'Sekolah|2026', SITE_URL . '/frontend/assets/images/activities/tkit-kegiatan.jpeg', 'TKIT'],
            ['Juara 1 Gaya Bebas Fins', 'Kabupaten', 'Bastian Bachtiar Agam meraih Juara 1 25M Gaya Bebas Fins dan Juara 3 50M Gaya Bebas Fins pada Piala KONI Kabupaten Bekasi 2026.', 'Kabupaten|2026', SITE_URL . '/frontend/assets/images/achievements/sdit-bastian-bachtiar.webp', 'SDIT'],
            ['Juara 1 Papan Kaki Bebas Fins', 'Kabupaten', 'Khansa Adzkiya Banafsha meraih Juara 1 25M Papan Kaki Bebas Fins pada Piala KONI Kabupaten Bekasi 2026.', 'Kabupaten|2026', SITE_URL . '/frontend/assets/images/achievements/sdit-khansa-adzkiya.webp', 'SDIT'],
            ['Juara 1 Gaya Bebas & Kupu Fins', 'Kabupaten', 'Teuku Uwais Lawdee Habibi meraih Juara 1 25M Gaya Bebas Fins dan 25M Gaya Kupu Fins pada Piala KONI Kabupaten Bekasi 2026.', 'Kabupaten|2026', SITE_URL . '/frontend/assets/images/achievements/sdit-teuku-uwais.webp', 'SDIT'],
            ['Juara 2 Gaya Bebas dan Papan Bebas Fins', 'Kabupaten', 'Aracelly Freissy Noushafarina meraih Juara 2 pada nomor 25M Gaya Bebas Fins dan 25M Papan Bebas Fins.', 'Kabupaten|2026', SITE_URL . '/frontend/assets/images/achievements/sdit-aracelly-freissy.webp', 'SDIT'],
            ['Juara 3 Gaya Kupu Fins', 'Kabupaten', 'Shaqueena Nazla Hakim meraih Juara 3 25M Gaya Kupu Fins pada Piala KONI Kabupaten Bekasi 2026.', 'Kabupaten|2026', SITE_URL . '/frontend/assets/images/achievements/sdit-shaqueena-nazla.webp', 'SDIT'],
            ['Juara 3 Youthswim Series 3', 'Event', 'Nizar Alvaro meraih Juara 3 25M Gaya Bebas dan Juara 3 25M Gaya Kupu dalam event Youthswim Series 3.', 'Event|2026', SITE_URL . '/frontend/assets/images/achievements/smpit-nizar-alvaro.webp', 'SMPIT'],
        ],
        'leadership' => [
            ['Nama Kepala Sekolah', 'Kepala Sekolah', 'Memimpin arah pendidikan dan pengembangan mutu sekolah secara keseluruhan.', ''],
            ['Nama Wakil Kepala Sekolah', 'Wakil Kepala Bidang Kurikulum', 'Mengelola dan mengembangkan kurikulum akademik sekolah.', ''],
            ['Nama Wakil Kepala Sekolah', 'Wakil Kepala Bidang Kesiswaan', 'Membina kegiatan dan pengembangan karakter siswa.', ''],
        ],
        'foundation' => [
            ['Nama Ketua Yayasan', 'Ketua Yayasan', 'Menetapkan arah strategis yayasan dan memastikan penyelenggaraan pendidikan berjalan sesuai visi lembaga.', ''],
            ['Nama Sekretaris Yayasan', 'Sekretaris Yayasan', 'Mengelola tata kelola, dokumentasi, dan koordinasi kelembagaan yayasan.', ''],
            ['Nama Bendahara Yayasan', 'Bendahara Yayasan', 'Mengelola perencanaan dan pertanggungjawaban keuangan yayasan secara amanah.', ''],
        ],
        'program' => [
            ["Tahfidz & Tahsin Al-Qur'an", 'Quran', "Program bacaan dan hafalan Al-Qur'an bertahap dari TKIT, SDIT, hingga SMPIT.", ''],
            ['Stimulasi Anak Usia Dini', 'Daycare', 'Aktivitas sensorik, motorik, bahasa, dan sosial untuk anak daycare secara aman dan menyenangkan.', ''],
            ['Sentra Kreativitas TKIT', 'TKIT', 'Kegiatan bermain terarah untuk menumbuhkan kreativitas, kemandirian, dan adab Islami.', ''],
            ['Literasi & Numerasi SDIT', 'SDIT', 'Penguatan kemampuan membaca, menulis, berhitung, dan berpikir logis sejak sekolah dasar.', ''],
            ['Digital Learning', 'Digital', 'Pemanfaatan teknologi pembelajaran yang terarah untuk mendukung kesiapan era digital.', ''],
            ['English Active Class', 'English', 'Pembiasaan bahasa Inggris aktif melalui percakapan, permainan, dan proyek kelas.', ''],
            ['Character Building', 'Adab', 'Pembinaan akhlak, kedisiplinan, tanggung jawab, dan kepedulian dalam keseharian siswa.', ''],
            ['Leadership Project SMPIT', 'SMPIT', 'Proyek kolaboratif dan organisasi siswa untuk melatih kepemimpinan remaja muslim.', ''],
        ],
        'activity' => [
            ['Taman Main Sensorik', 'Daycare', 'Belajar mengenal warna, bentuk, dan kerja sama melalui permainan balok serta eksplorasi taman yang aman dan hangat.', '', SITE_URL . '/frontend/assets/images/activities/daycare-kegiatan.jpeg'],
            ['Eksplorasi Ceria', 'TKIT', 'Aktivitas bermain gelembung dan motorik halus untuk menumbuhkan rasa ingin tahu, keberanian, dan keceriaan anak.', '', SITE_URL . '/frontend/assets/images/activities/tkit-kegiatan.jpeg'],
            ['Kreasi Seni Angklung', 'SDIT', 'Pembelajaran seni budaya yang melatih kekompakan, percaya diri, dan apresiasi siswa terhadap kekayaan Indonesia.', '', SITE_URL . '/frontend/assets/images/activities/sdit-kegiatan.jpeg'],
            ['Riset Kebun Sekolah', 'SMPIT', 'Kegiatan observasi tanaman yang mengajak siswa berpikir ilmiah, peduli lingkungan, dan aktif berdiskusi.', '', SITE_URL . '/frontend/assets/images/activities/smpit-kegiatan.jpeg'],
        ],
    ];
    $count = $pdo->prepare('SELECT COUNT(*) FROM site_content_items WHERE type=?');
    $insert = $pdo->prepare('INSERT INTO site_content_items (type,title,subtitle,description,image,badge,year,extra,sort_order) VALUES (?,?,?,?,?,?,?,?,?)');
    foreach ($seeds as $type => $items) {
        $count->execute([$type]);
        if ((int)$count->fetchColumn() > 0) continue;
        foreach ($items as $index => $item) {
            [$title, $subtitle, $description, $extra] = $item;
            $image = $item[4] ?? null;
            $unit = $item[5] ?? null;
            $badge = null; $year = null;
            if ($type === 'achievement' && strpos($extra, '|') !== false) {
                [$badge, $year] = explode('|', $extra, 2); $extra = $unit ?: '';
            }
            $insert->execute([$type, $title, $subtitle ?: null, $description, $image, $badge, $year, $extra ?: null, $index + 1]);
        }
    }
    $pdo->exec("UPDATE site_content_items SET link_label='Pelajari Program' WHERE type='program' AND (link_label IS NULL OR link_label='')");
    $pdo->exec("UPDATE site_content_items SET link_label='Lihat Publikasi' WHERE type='achievement' AND (link_label IS NULL OR link_label='')");
    $activityLinks = [
        'Daycare' => SITE_DAYCARE_INSTAGRAM,
        'TKIT' => SITE_TKIT_INSTAGRAM,
        'SDIT' => SITE_SDIT_INSTAGRAM,
        'SMPIT' => SITE_SMPIT_INSTAGRAM,
    ];
    $linkActivity = $pdo->prepare("UPDATE site_content_items SET link_url=?,link_label='Lihat di Instagram' WHERE type='activity' AND subtitle=? AND (link_url IS NULL OR link_url='')");
    foreach ($activityLinks as $unit => $url) $linkActivity->execute([$url, $unit]);
    $linkAchievement = $pdo->prepare("UPDATE site_content_items SET link_url=?,link_label='Lihat Publikasi' WHERE type='achievement' AND extra=? AND (link_url IS NULL OR link_url='')");
    foreach ($activityLinks as $unit => $url) $linkAchievement->execute([$url, $unit]);
    $profile = $pdo->prepare('INSERT IGNORE INTO site_profile (id,history_title,history_content,vision,mission) VALUES (1,?,?,?,?)');
    $profile->execute([
        'Perjalanan ' . SITE_NAME,
        'Didirikan dengan semangat mencetak generasi sholeh, cerdas, mandiri, dan berakhlak mulia, SIT Permata Hati Bekasi berkembang menjadi sekolah Islam terpadu terpercaya di Tambun Selatan. Kami konsisten memadukan kurikulum nasional, pembelajaran Al-Quran, dan pembinaan karakter.',
        'Menjadi lembaga pendidikan Islam terpadu terdepan yang melahirkan generasi cerdas, berakhlak mulia, dan berdaya saing global.',
        'Menyelenggarakan pendidikan berbasis Al-Quran dan Sunnah, mengembangkan potensi akademik secara optimal, serta membangun karakter dan kepemimpinan sejak dini.'
    ]);
}

portal_bootstrap_database($pdo);

function portal_user(): ?array
{
    return $_SESSION['portal_user'] ?? null;
}

function portal_logged_in(): bool
{
    return portal_user() !== null;
}

function portal_home_for_role(string $role): string
{
    return SITE_URL . '/portal/dashboard';
}

function portal_login_url(?string $role = null): string
{
    return SITE_URL . '/portal/admin';
}

function portal_require_guest(): void
{
    if (portal_logged_in()) {
        header('Location: ' . portal_home_for_role(portal_user()['role']));
        exit;
    }
}

function portal_require_auth(array $roles = []): void
{
    if (!portal_logged_in()) {
        header('Location: ' . portal_login_url());
        exit;
    }
    if ($roles && !in_array(portal_user()['role'], $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/../frontend/pages/portal/forbidden.php';
        exit;
    }
}

function portal_attempt_login(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare('SELECT * FROM portal_users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([strtolower(trim($username))]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['portal_user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];
    $pdo->prepare('UPDATE portal_users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
    portal_log($pdo, 'login', 'Masuk ke portal sebagai ' . ucfirst($user['role']));
    return true;
}

function portal_log(PDO $pdo, string $action, string $description): void
{
    $user = portal_user();
    $stmt = $pdo->prepare('INSERT INTO portal_activity_logs (user_id, action, description) VALUES (?, ?, ?)');
    $stmt->execute([$user['id'] ?? null, $action, mb_strimwidth($description, 0, 250, '...')]);
}

function portal_csrf_token(): string
{
    if (empty($_SESSION['portal_csrf'])) {
        $_SESSION['portal_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['portal_csrf'];
}

function portal_verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['portal_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.');
    }
}

function portal_flash(string $type, string $message): void
{
    $_SESSION['portal_flash'] = ['type' => $type, 'message' => $message];
}

function portal_get_flash(): ?array
{
    $flash = $_SESSION['portal_flash'] ?? null;
    unset($_SESSION['portal_flash']);
    return $flash;
}

function portal_slug(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    return trim($slug, '-') ?: 'konten-' . time();
}

function portal_upload_image(array $file, string $prefix = 'content'): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Pilih gambar yang akan diunggah.');
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Gambar gagal diunggah.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Ukuran gambar maksimal 5 MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Format gambar harus JPG, PNG, atau WebP.');
    }
    $directory = __DIR__ . '/../frontend/assets/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder upload tidak dapat dibuat.');
    }
    $filename = $prefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(5)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('Gagal menyimpan gambar.');
    }
    return SITE_URL . '/frontend/assets/uploads/' . $filename;
}

function portal_upload_hero_media(array $file, string $mediaType): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) throw new RuntimeException('Pilih file media yang akan diunggah.');
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Media gagal diunggah. Periksa batas upload PHP/XAMPP.');
    $maxSize = $mediaType === 'video' ? 80 * 1024 * 1024 : 10 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSize) throw new RuntimeException($mediaType === 'video' ? 'Ukuran video maksimal 80 MB.' : 'Ukuran gambar maksimal 10 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = $mediaType === 'video'
        ? ['video/mp4' => 'mp4', 'video/webm' => 'webm']
        : ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) throw new RuntimeException($mediaType === 'video' ? 'Video harus MP4 atau WebM.' : 'Gambar harus JPG, PNG, WebP, atau GIF.');
    $directory = __DIR__ . '/../frontend/assets/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('Folder upload tidak dapat dibuat.');
    $filename = 'hero-' . date('Ymd-His') . '-' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) throw new RuntimeException('Gagal menyimpan media hero.');
    return SITE_URL . '/frontend/assets/uploads/' . $filename;
}

function portal_upload_brochure(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) throw new RuntimeException('Pilih file brosur PDF.');
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Brosur gagal diunggah.');
    if (($file['size'] ?? 0) > 15 * 1024 * 1024) throw new RuntimeException('Ukuran brosur maksimal 15 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($mime !== 'application/pdf') throw new RuntimeException('Brosur harus berupa file PDF.');
    $directory = __DIR__ . '/../frontend/assets/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('Folder upload tidak dapat dibuat.');
    $filename = 'brosur-' . date('Ymd-His') . '-' . bin2hex(random_bytes(5)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) throw new RuntimeException('Gagal menyimpan brosur.');
    return SITE_URL . '/frontend/assets/uploads/' . $filename;
}

function portal_delete_uploaded_image(?string $url): void
{
    $uploadUrl = SITE_URL . '/frontend/assets/uploads/';
    if (!$url || strpos($url, $uploadUrl) !== 0) return;
    $filename = basename((string)parse_url($url, PHP_URL_PATH));
    if ($filename === '' || $filename === '.' || $filename === '..') return;
    $directory = realpath(__DIR__ . '/../frontend/assets/uploads');
    if (!$directory) return;
    $target = $directory . DIRECTORY_SEPARATOR . $filename;
    if (is_file($target)) unlink($target);
}
