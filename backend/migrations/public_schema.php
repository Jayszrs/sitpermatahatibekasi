<?php
/**
 * Migrasi minimum untuk tabel yang dipakai halaman publik.
 * Seluruh statement bersifat idempotent dan tidak menghapus data lama.
 */

function ensure_public_schema(PDO $pdo): void
{
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
