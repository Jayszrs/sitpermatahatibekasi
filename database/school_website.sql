-- ============================================================
-- Database: school_website
-- Import melalui phpMyAdmin (Import > Choose File > Go)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `school_website` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `school_website`;

-- ============================================================
-- Tabel: news (Berita)
-- ============================================================
CREATE TABLE IF NOT EXISTS `news` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `unit` VARCHAR(20) NOT NULL DEFAULT 'SDIT',
    `image` VARCHAR(255) DEFAULT NULL,
    `excerpt` TEXT,
    `content` LONGTEXT,
    `published_at` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_news_unit_date` (`unit`,`published_at`,`id`)
) ENGINE=InnoDB;

-- ============================================================
-- Tabel: gallery (Galeri Foto)
-- ============================================================
CREATE TABLE IF NOT EXISTS `gallery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `image` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `gallery_albums` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(190) NOT NULL UNIQUE,
    `description` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_gallery_album_active` (`is_active`,`sort_order`,`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `gallery_photos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `album_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `image` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_gallery_photo_album` (`album_id`,`sort_order`,`id`),
    CONSTRAINT `fk_gallery_photo_album` FOREIGN KEY (`album_id`) REFERENCES `gallery_albums` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Tabel: contacts (Pesan dari Form Kontak)
-- ============================================================
CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `whatsapp` VARCHAR(30) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Tabel: spmb_registrations (Pendaftaran SPMB)
-- ============================================================
CREATE TABLE IF NOT EXISTS `spmb_registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_number` VARCHAR(40) DEFAULT NULL,
    `student_name` VARCHAR(150) NOT NULL,
    `student_nik` VARCHAR(30) DEFAULT NULL,
    `gender` ENUM('L','P') DEFAULT NULL,
    `birth_place` VARCHAR(100) DEFAULT NULL,
    `birth_date` DATE DEFAULT NULL,
    `parent_name` VARCHAR(150) NOT NULL,
    `parent_nik` VARCHAR(30) DEFAULT NULL,
    `family_card_number` VARCHAR(30) DEFAULT NULL,
    `whatsapp` VARCHAR(30) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `level` VARCHAR(20) NOT NULL,
    `academic_year` VARCHAR(9) DEFAULT NULL,
    `admission_track` ENUM('reguler','waiting_list') NOT NULL DEFAULT 'reguler',
    `previous_school` VARCHAR(150) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `registration_status` ENUM('baru','verifikasi','lulus','cadangan','ditolak','daftar_ulang') NOT NULL DEFAULT 'baru',
    `document_status` ENUM('belum_lengkap','lengkap','terverifikasi') NOT NULL DEFAULT 'belum_lengkap',
    `payment_status` ENUM('belum_bayar','sebagian','lunas') NOT NULL DEFAULT 'belum_bayar',
    `payment_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `payment_date` DATE DEFAULT NULL,
    `payment_notes` TEXT DEFAULT NULL,
    `payment_updated_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel akun portal internal. Akun awal dibuat otomatis oleh backend/auth.php
-- dengan password ter-hash saat portal pertama kali dibuka.
CREATE TABLE IF NOT EXISTS `portal_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `username` VARCHAR(80) NOT NULL,
    `email` VARCHAR(190) DEFAULT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','humas','kasir') NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_portal_username` (`username`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `portal_activity_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_activity_user` (`user_id`),
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `site_content_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(30) NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `subtitle` VARCHAR(180) DEFAULT NULL,
    `description` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `badge` VARCHAR(80) DEFAULT NULL,
    `year` VARCHAR(10) DEFAULT NULL,
    `extra` TEXT DEFAULT NULL,
    `link_url` VARCHAR(255) DEFAULT NULL,
    `link_label` VARCHAR(80) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_content_type` (`type`,`is_active`,`sort_order`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `site_profile` (
    `id` TINYINT PRIMARY KEY,
    `history_title` VARCHAR(180) NOT NULL,
    `history_content` TEXT NOT NULL,
    `vision` TEXT NOT NULL,
    `mission` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `spmb_payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` INT NOT NULL,
    `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
    `payment_type` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(14,2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `payment_date` DATE NOT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `payer_name` VARCHAR(150) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `status` ENUM('verified','cancelled') NOT NULL DEFAULT 'verified',
    `recorded_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_payment_registration` (`registration_id`),
    CONSTRAINT `fk_payment_registration` FOREIGN KEY (`registration_id`) REFERENCES `spmb_registrations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payment_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `version` VARCHAR(80) PRIMARY KEY,
    `description` VARCHAR(255) NOT NULL,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `hero_media` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(180) NOT NULL,
    `eyebrow` VARCHAR(180) DEFAULT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `media_type` ENUM('image','video') NOT NULL DEFAULT 'image',
    `media_url` VARCHAR(255) NOT NULL,
    `poster_url` VARCHAR(255) DEFAULT NULL,
    `cta_label` VARCHAR(80) DEFAULT NULL,
    `cta_url` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hero_public` (`is_active`,`sort_order`,`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `brochures` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `unit_slug` VARCHAR(30) NOT NULL UNIQUE,
    `unit_name` VARCHAR(100) NOT NULL,
    `headline` VARCHAR(180) NOT NULL,
    `description` TEXT NOT NULL,
    `audience` VARCHAR(180) DEFAULT NULL,
    `highlights` TEXT DEFAULT NULL,
    `facilities` TEXT DEFAULT NULL,
    `schedule_info` VARCHAR(255) DEFAULT NULL,
    `cover_image` VARCHAR(255) DEFAULT NULL,
    `file_url` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_brochure_public` (`is_active`,`sort_order`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `unit_gallery_photos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `unit_slug` VARCHAR(30) NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `image` VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_unit_gallery_public` (`unit_slug`,`is_active`,`sort_order`,`id`)
) ENGINE=InnoDB;

-- ============================================================
-- Tabel: portal karir dan lamaran kandidat
-- ============================================================
CREATE TABLE IF NOT EXISTS `job_vacancies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(190) NOT NULL UNIQUE,
    `unit` VARCHAR(80) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `employment_type` VARCHAR(60) NOT NULL,
    `work_location` VARCHAR(180) NOT NULL,
    `education` VARCHAR(180) DEFAULT NULL,
    `experience` VARCHAR(180) DEFAULT NULL,
    `summary` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `responsibilities` TEXT NOT NULL,
    `requirements` TEXT NOT NULL,
    `benefits` TEXT DEFAULT NULL,
    `salary_note` VARCHAR(150) DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `deadline` DATE DEFAULT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_job_public` (`is_active`,`deadline`,`created_at`),
    INDEX `idx_job_filter` (`unit`,`employment_type`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `job_applications` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `vacancy_id` INT NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `education` VARCHAR(180) DEFAULT NULL,
    `experience_years` DECIMAL(4,1) NOT NULL DEFAULT 0,
    `cover_letter` TEXT NOT NULL,
    `cv_file` VARCHAR(255) NOT NULL,
    `cv_original_name` VARCHAR(255) NOT NULL,
    `portfolio_url` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('baru','ditinjau','wawancara','diterima','ditolak') NOT NULL DEFAULT 'baru',
    `admin_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_application_vacancy` (`vacancy_id`,`status`,`created_at`),
    CONSTRAINT `fk_application_vacancy` FOREIGN KEY (`vacancy_id`) REFERENCES `job_vacancies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT IGNORE INTO `job_vacancies` (`title`,`slug`,`unit`,`department`,`employment_type`,`work_location`,`education`,`experience`,`summary`,`description`,`responsibilities`,`requirements`,`benefits`,`salary_note`,`deadline`,`is_featured`,`is_active`) VALUES
('Guru Kelas SDIT','guru-kelas-sdit','SDIT','Pendidikan','Penuh Waktu','Tambun Selatan, Bekasi','S1 PGSD/Pendidikan','Minimal 1 tahun','Mendampingi pembelajaran tematik, literasi, numerasi, dan pembentukan karakter Islami siswa SDIT.','Kami mencari pendidik yang hangat, adaptif, dan mampu menghadirkan pengalaman belajar bermakna bagi siswa SDIT.','Menyusun dan menjalankan modul ajar\nMelakukan asesmen perkembangan siswa\nBerkomunikasi aktif dengan orang tua\nBerpartisipasi dalam program sekolah','Muslim/Muslimah dan berakhlak baik\nS1 PGSD atau bidang pendidikan relevan\nMampu bekerja dalam tim\nMenguasai teknologi pembelajaran dasar','Lingkungan kerja Islami\nPelatihan dan pengembangan guru\nMakan siang dan tunjangan sesuai kebijakan','Kompetitif',DATE_ADD(CURDATE(),INTERVAL 60 DAY),1,1),
('Guru Tahfidz dan Tahsin','guru-tahfidz-tahsin','Semua Unit','Al Quran','Penuh Waktu','Tambun Selatan, Bekasi','S1 atau pesantren/mahad relevan','Minimal 1 tahun','Membina bacaan, hafalan, adab, serta kecintaan siswa terhadap Al Quran.','Posisi ini berperan menjaga kualitas program Al Quran lintas unit melalui pendampingan yang terukur dan menyenangkan.','Membimbing tahsin dan tahfidz\nMenyusun target hafalan\nMencatat perkembangan siswa\nMendukung kegiatan ruhiyah sekolah','Bacaan Al Quran baik dan bersanad menjadi nilai tambah\nBerpengalaman mengajar anak\nSabar, komunikatif, dan disiplin','Program pengembangan kompetensi\nLingkungan kerja suportif\nTunjangan sesuai kebijakan','Kompetitif',DATE_ADD(CURDATE(),INTERVAL 75 DAY),1,1),
('Guru Pendamping TKIT','guru-pendamping-tkit','TKIT','Pendidikan Anak Usia Dini','Penuh Waktu','Tambun Selatan, Bekasi','S1 PAUD/Psikologi/Pendidikan','Terbuka untuk fresh graduate','Mendampingi aktivitas bermain-belajar, pembiasaan adab, dan perkembangan anak usia dini.','Kami membuka kesempatan bagi pendidik kreatif yang menyukai dunia anak dan pembelajaran berbasis eksplorasi.','Menyiapkan area dan media bermain\nMendampingi rutinitas kelas\nMencatat perkembangan anak\nMenjaga komunikasi dengan wali murid','Menyukai dunia anak\nKreatif dan komunikatif\nMampu bekerja dalam tim\nSehat jasmani dan rohani','Mentoring guru senior\nPelatihan PAUD\nLingkungan kerja Islami','Kompetitif',DATE_ADD(CURDATE(),INTERVAL 45 DAY),0,1),
('Staf Humas dan Konten Digital','staf-humas-konten-digital','Yayasan','Humas','Penuh Waktu','Tambun Selatan, Bekasi','D3/S1 Komunikasi/Desain/Multimedia','Minimal 1 tahun','Mengelola publikasi, dokumentasi, media sosial, dan komunikasi digital sekolah.','Posisi ini membantu menyampaikan cerita dan informasi sekolah secara akurat, menarik, serta konsisten dengan identitas lembaga.','Menyusun kalender konten\nMendokumentasikan kegiatan\nMengelola media sosial dan website\nBerkoordinasi dengan seluruh unit','Mampu menulis copy dengan baik\nMenguasai desain atau video dasar\nMemiliki portofolio\nResponsif dan terorganisir','Perangkat kerja pendukung\nKesempatan mengembangkan portofolio\nTunjangan sesuai kebijakan','Kompetitif',DATE_ADD(CURDATE(),INTERVAL 60 DAY),0,1);

UPDATE `job_vacancies` SET `image` = CASE
    WHEN `unit` = 'TKIT' THEN 'http://localhost/school-website/frontend/assets/images/brochures/tkit-promo.png'
    WHEN `unit` = 'SDIT' THEN 'http://localhost/school-website/frontend/assets/images/brochures/sdit-promo.png'
    WHEN `unit` = 'SMPIT' THEN 'http://localhost/school-website/frontend/assets/images/brochures/smpit-promo.png'
    ELSE 'http://localhost/school-website/frontend/assets/images/brochures/daycare-promo.png'
END
WHERE `image` IS NULL OR `image` = '';

INSERT IGNORE INTO `brochures` (`unit_slug`,`unit_name`,`headline`,`description`,`audience`,`highlights`,`facilities`,`schedule_info`,`cover_image`,`file_url`,`sort_order`,`is_active`) VALUES
('daycare','Daycare Permata Hati','Tumbuh Aman, Hangat, dan Penuh Makna','Pendampingan anak usia dini dalam lingkungan Islami yang hangat dengan aktivitas bermain terarah.','Usia 1-4 tahun','Stimulasi motorik dan sensorik\nPembiasaan doa dan adab\nLaporan perkembangan harian','Ruang bermain ramah anak\nArea eksplorasi\nPendamping berpengalaman','Senin-Jumat, pilihan layanan setengah atau satu hari','http://localhost/school-website/frontend/assets/images/brochures/daycare-promo.png','http://localhost/school-website/frontend/assets/brochures/brosur-daycare.pdf',1,1),
('tkit','TKIT Permata Hati','Belajar Ceria, Mandiri, dan Cinta Al-Quran','Pengalaman belajar aktif yang menumbuhkan kreativitas, kemandirian, dan karakter Islami.','Usia 4-6 tahun','Sentra kreativitas\nTahsin dan hafalan bertahap\nProyek tematik','Kelas nyaman\nArea bermain\nMedia belajar interaktif','Senin-Jumat mengikuti kalender pendidikan','http://localhost/school-website/frontend/assets/images/brochures/tkit-promo.png','http://localhost/school-website/frontend/assets/brochures/brosur-tkit.pdf',2,1),
('sdit','SDIT Permata Hati','Kuat dalam Akademik, Adab, dan Al-Quran','Pendidikan dasar terpadu yang menguatkan literasi, numerasi, tahfidz, dan karakter mandiri.','Usia sekolah dasar kelas 1-6','Literasi dan numerasi\nTahfidz dan tahsin\nPembelajaran berbasis proyek','Ruang kelas nyaman\nLaboratorium pembelajaran\nPerpustakaan','Senin-Jumat mengikuti kalender pendidikan','http://localhost/school-website/frontend/assets/images/brochures/sdit-promo.png','http://localhost/school-website/frontend/assets/brochures/brosur-sdit.pdf',3,1),
('smpit','SMPIT Permata Hati','Siap Memimpin, Berkarya, dan Berakhlak','Pembelajaran remaja yang membangun kepemimpinan, kemampuan digital, akademik, dan karakter Islami.','Lulusan SD/MI sederajat','Leadership project\nDigital learning\nTahfidz dan penguatan karakter','Ruang belajar kolaboratif\nPerangkat pembelajaran digital\nFasilitas kegiatan siswa','Senin-Jumat mengikuti kalender pendidikan','http://localhost/school-website/frontend/assets/images/brochures/smpit-promo.png','http://localhost/school-website/frontend/assets/brochures/brosur-smpit.pdf',4,1);

INSERT INTO `unit_gallery_photos` (`unit_slug`,`title`,`description`,`image`,`sort_order`,`is_active`)
SELECT seed.unit_slug, seed.title, seed.description, seed.image, seed.sort_order, 1
FROM (
    SELECT 'daycare' unit_slug,'Aktivitas sensorik' title,'Eksplorasi aman untuk tumbuh kembang anak.' description,'http://localhost/school-website/frontend/assets/images/brochures/daycare-promo.png' image,1 sort_order
    UNION ALL SELECT 'daycare','Ruang bermain','Lingkungan hangat dan ramah anak.','http://localhost/school-website/frontend/assets/images/activities/daycare-kegiatan.jpeg',2
    UNION ALL SELECT 'tkit','Sentra kreativitas','Belajar melalui karya dan permainan terarah.','http://localhost/school-website/frontend/assets/images/brochures/tkit-promo.png',1
    UNION ALL SELECT 'tkit','Pembiasaan Islami','Adab dan doa hadir dalam keseharian.','http://localhost/school-website/frontend/assets/images/activities/tkit-kegiatan.jpeg',2
    UNION ALL SELECT 'sdit','Pembelajaran kolaboratif','Siswa belajar aktif bersama guru dan teman.','http://localhost/school-website/frontend/assets/images/brochures/sdit-promo.png',1
    UNION ALL SELECT 'sdit','Kegiatan siswa','Potensi siswa berkembang melalui kegiatan kelas dan ekstrakurikuler.','http://localhost/school-website/frontend/assets/images/activities/sdit-kegiatan.jpeg',2
    UNION ALL SELECT 'sdit','Lingkungan sekolah','Ruang tumbuh yang aman dan mendukung.','http://localhost/school-website/frontend/assets/images/school/gedung-sekolah.jpeg',3
    UNION ALL SELECT 'smpit','Leadership project','Proyek kolaboratif melatih kepemimpinan siswa.','http://localhost/school-website/frontend/assets/images/brochures/smpit-promo.png',1
    UNION ALL SELECT 'smpit','Digital learning','Teknologi digunakan secara terarah dalam pembelajaran.','http://localhost/school-website/frontend/assets/images/activities/smpit-kegiatan.jpeg',2
    UNION ALL SELECT 'smpit','Lingkungan SMPIT','Fasilitas pendidikan jenjang sekolah menengah.','http://localhost/school-website/frontend/assets/images/school/gedung-smpit.jpeg',3
) seed
WHERE NOT EXISTS (SELECT 1 FROM `unit_gallery_photos` LIMIT 1);

-- Konten awal CMS agar halaman publik langsung terisi setelah import.
INSERT INTO `site_profile` (`id`,`history_title`,`history_content`,`vision`,`mission`) VALUES
(1, 'Profil SIT Permata Hati Bekasi', 'Didirikan dengan semangat mencetak generasi sholeh, cerdas, mandiri, dan berakhlak mulia, SIT Permata Hati Bekasi berkembang menjadi sekolah Islam terpadu terpercaya di Tambun Selatan. Kami konsisten memadukan kurikulum nasional, pembelajaran Al-Quran, dan pembinaan karakter.', 'Menjadi sekolah Islam terpadu yang melahirkan generasi sholeh, cerdas, mandiri, dan berwawasan global.', 'Menyelenggarakan pendidikan berbasis Al-Quran dan Sunnah, mengembangkan potensi akademik secara optimal, serta membangun karakter dan kepemimpinan sejak dini.');

INSERT INTO `site_content_items` (`type`,`title`,`subtitle`,`description`,`image`,`badge`,`year`,`extra`,`sort_order`) VALUES
('unit','Daycare Permata Hati Bekasi','Daycare','Layanan pengasuhan anak usia dini dengan suasana aman, hangat, dan pembiasaan adab Islami sejak awal.',NULL,NULL,NULL,'Stimulasi motorik\nPembiasaan doa\nAktivitas sensorik\nLaporan harian',1),
('unit','TKIT Permata Hati Bekasi','TKIT','Jenjang taman kanak-kanak Islam terpadu yang menumbuhkan kemandirian, kreativitas, dan cinta Al-Quran.',NULL,NULL,NULL,'Sentra bermain\nTahsin dasar\nDoa harian\nKemandirian',2),
('unit','SDIT Permata Hati Bekasi','SDIT','Pendidikan dasar terpadu yang menguatkan akademik, tahfidz, adab, dan karakter mandiri siswa.',NULL,NULL,NULL,'Tahfidz Juz 30\nLiterasi numerasi\nEkstrakurikuler\nFull Day School',3),
('unit','SMPIT Permata Hati Bekasi','SMPIT','Jenjang menengah pertama yang membangun kompetensi akademik, kepemimpinan, dan akhlak remaja muslim.',NULL,NULL,NULL,'Tahfidz lanjutan\nEnglish Club\nKlub Sains\nLeadership Project',4),
('achievement','Apresiasi Kemandirian Anak','Sekolah','Penghargaan perkembangan kemandirian dan kerja sama anak melalui aktivitas bermain terarah.','http://localhost/school-website/frontend/assets/images/activities/daycare-kegiatan.jpeg','Sekolah','2026','Daycare',1),
('achievement','Apresiasi Kreativitas TKIT','Sekolah','Penghargaan untuk kreativitas, keberanian berekspresi, dan kemampuan motorik anak TKIT.','http://localhost/school-website/frontend/assets/images/activities/tkit-kegiatan.jpeg','Sekolah','2026','TKIT',2),
('achievement','Juara 1 Gaya Bebas Fins','Kabupaten','Bastian Bachtiar Agam meraih Juara 1 25M Gaya Bebas Fins dan Juara 3 50M Gaya Bebas Fins pada Piala KONI Kabupaten Bekasi 2026.','http://localhost/school-website/frontend/assets/images/achievements/sdit-bastian-bachtiar.webp','Kabupaten','2026','SDIT',3),
('achievement','Juara 1 Papan Kaki Bebas Fins','Kabupaten','Khansa Adzkiya Banafsha meraih Juara 1 25M Papan Kaki Bebas Fins pada Piala KONI Kabupaten Bekasi 2026.','http://localhost/school-website/frontend/assets/images/achievements/sdit-khansa-adzkiya.webp','Kabupaten','2026','SDIT',4),
('achievement','Juara 1 Gaya Bebas & Kupu Fins','Kabupaten','Teuku Uwais Lawdee Habibi meraih Juara 1 25M Gaya Bebas Fins dan 25M Gaya Kupu Fins pada Piala KONI Kabupaten Bekasi 2026.','http://localhost/school-website/frontend/assets/images/achievements/sdit-teuku-uwais.webp','Kabupaten','2026','SDIT',5),
('achievement','Juara 2 Gaya Bebas dan Papan Bebas Fins','Kabupaten','Aracelly Freissy Noushafarina meraih Juara 2 pada nomor 25M Gaya Bebas Fins dan 25M Papan Bebas Fins.','http://localhost/school-website/frontend/assets/images/achievements/sdit-aracelly-freissy.webp','Kabupaten','2026','SDIT',6),
('achievement','Juara 3 Gaya Kupu Fins','Kabupaten','Shaqueena Nazla Hakim meraih Juara 3 25M Gaya Kupu Fins pada Piala KONI Kabupaten Bekasi 2026.','http://localhost/school-website/frontend/assets/images/achievements/sdit-shaqueena-nazla.webp','Kabupaten','2026','SDIT',7),
('achievement','Juara 3 Youthswim Series 3','Event','Nizar Alvaro meraih Juara 3 25M Gaya Bebas dan Juara 3 25M Gaya Kupu dalam event Youthswim Series 3.','http://localhost/school-website/frontend/assets/images/achievements/smpit-nizar-alvaro.webp','Event','2026','SMPIT',8),
('leadership','Nama Kepala Sekolah','Kepala Sekolah','Memimpin arah pendidikan dan pengembangan mutu sekolah.',NULL,NULL,NULL,NULL,1),
('leadership','Nama Wakil Kepala Sekolah','Wakil Kepala Bidang Kurikulum','Mengelola dan mengembangkan kurikulum akademik sekolah.',NULL,NULL,NULL,NULL,2),
('leadership','Nama Wakil Kepala Sekolah','Wakil Kepala Bidang Kesiswaan','Membina kegiatan dan pengembangan karakter siswa.',NULL,NULL,NULL,NULL,3),
('program','Tahfidz & Tahsin Al-Quran','Quran','Program bacaan dan hafalan Al-Quran bertahap dari TKIT, SDIT, hingga SMPIT.',NULL,NULL,NULL,NULL,1),
('program','Stimulasi Anak Usia Dini','Daycare','Aktivitas sensorik, motorik, bahasa, dan sosial untuk anak daycare secara aman dan menyenangkan.',NULL,NULL,NULL,NULL,2),
('program','Sentra Kreativitas TKIT','TKIT','Kegiatan bermain terarah untuk menumbuhkan kreativitas, kemandirian, dan adab Islami.',NULL,NULL,NULL,NULL,3),
('program','Literasi & Numerasi SDIT','SDIT','Penguatan kemampuan membaca, menulis, berhitung, dan berpikir logis sejak sekolah dasar.',NULL,NULL,NULL,NULL,4),
('program','Digital Learning','Digital','Pemanfaatan teknologi pembelajaran yang terarah untuk mendukung kesiapan era digital.',NULL,NULL,NULL,NULL,5),
('program','English Active Class','English','Pembiasaan bahasa Inggris aktif melalui percakapan, permainan, dan proyek kelas.',NULL,NULL,NULL,NULL,6),
('program','Character Building','Adab','Pembinaan akhlak, kedisiplinan, tanggung jawab, dan kepedulian dalam keseharian siswa.',NULL,NULL,NULL,NULL,7),
('program','Leadership Project SMPIT','SMPIT','Proyek kolaboratif dan organisasi siswa untuk melatih kepemimpinan remaja muslim.',NULL,NULL,NULL,NULL,8),
('activity','Taman Main Sensorik','Daycare','Belajar mengenal warna, bentuk, dan kerja sama melalui permainan balok serta eksplorasi taman yang aman dan hangat.','http://localhost/school-website/frontend/assets/images/activities/daycare-kegiatan.jpeg',NULL,NULL,NULL,1),
('activity','Eksplorasi Ceria','TKIT','Aktivitas bermain gelembung dan motorik halus untuk menumbuhkan rasa ingin tahu, keberanian, dan keceriaan anak.','http://localhost/school-website/frontend/assets/images/activities/tkit-kegiatan.jpeg',NULL,NULL,NULL,2),
('activity','Kreasi Seni Angklung','SDIT','Pembelajaran seni budaya yang melatih kekompakan, percaya diri, dan apresiasi siswa terhadap kekayaan Indonesia.','http://localhost/school-website/frontend/assets/images/activities/sdit-kegiatan.jpeg',NULL,NULL,NULL,3),
('activity','Riset Kebun Sekolah','SMPIT','Kegiatan observasi tanaman yang mengajak siswa berpikir ilmiah, peduli lingkungan, dan aktif berdiskusi.','http://localhost/school-website/frontend/assets/images/activities/smpit-kegiatan.jpeg',NULL,NULL,NULL,4);

-- ============================================================
-- SAMPLE DATA: news
-- ============================================================
INSERT INTO `news` (`title`, `slug`, `unit`, `image`, `excerpt`, `content`, `published_at`) VALUES
('Pesantren Ramadhan 1447 H Resmi Dibuka', 'pesantren-ramadhan-1447-h-resmi-dibuka',
 'SMPIT',
 'http://localhost/school-website/frontend/assets/images/gallery/masjid-sekolah/masjid-01.jpeg',
 'Kegiatan Pesantren Ramadhan tahun ini diikuti oleh seluruh siswa SD, SMP, dan SMA dengan berbagai rangkaian acara keagamaan.',
 'Kegiatan Pesantren Ramadhan tahun ini diikuti oleh seluruh siswa SD, SMP, dan SMA dengan berbagai rangkaian acara keagamaan seperti tadarus bersama, kajian akhlak, buka puasa bersama, dan santunan anak yatim. Kegiatan ini bertujuan untuk memperkuat nilai-nilai spiritual siswa selama bulan suci Ramadhan sekaligus mempererat ukhuwah antar siswa dan guru.',
 '2026-03-10'),
('Siswa Raih Juara 1 Olimpiade Matematika Nasional', 'siswa-raih-juara-1-olimpiade-matematika-nasional',
 'SDIT',
 'http://localhost/school-website/frontend/assets/images/achievements/sdit-bastian-bachtiar.webp',
 'Prestasi membanggakan kembali diraih oleh siswa SMP kami dalam ajang Olimpiade Sains Nasional bidang Matematika.',
 'Prestasi membanggakan kembali diraih oleh siswa SMP kami dalam ajang Olimpiade Sains Nasional bidang Matematika. Setelah melalui seleksi ketat tingkat kota, provinsi, hingga nasional, siswa kami berhasil membawa pulang medali emas. Pencapaian ini merupakan hasil dari bimbingan intensif guru pembina serta kerja keras siswa selama berbulan-bulan.',
 '2026-02-20'),
('Wisuda Tahfidz Angkatan XII Berlangsung Khidmat', 'wisuda-tahfidz-angkatan-xii-berlangsung-khidmat',
 'TKIT',
 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-01.jpeg',
 'Sebanyak 45 siswa mengikuti prosesi Wisuda Tahfidz Al-Quran angkatan XII yang dihadiri oleh orang tua dan wali murid.',
 'Sebanyak 45 siswa mengikuti prosesi Wisuda Tahfidz Al-Quran angkatan XII yang dihadiri oleh orang tua dan wali murid. Acara ini menjadi momen istimewa bagi para siswa yang telah menyelesaikan target hafalan juz yang ditentukan. Kepala sekolah berharap program tahfidz ini terus mencetak generasi penghafal Al-Quran yang berakhlak mulia.',
 '2026-01-15');

-- ============================================================
-- SAMPLE DATA: gallery
-- ============================================================
INSERT INTO `gallery` (`title`, `image`, `description`) VALUES
('Gedung Sekolah', 'http://localhost/school-website/frontend/assets/images/school/gedung-sekolah.jpeg', 'Tampak depan gedung sekolah'),
('Kegiatan Belajar Mengajar', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-01.jpeg', 'Suasana kelas yang nyaman'),
('Lapangan Olahraga', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-01.jpeg', 'Fasilitas olahraga siswa'),
('Perpustakaan', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-02.jpeg', 'Ruang baca dan koleksi buku'),
('Laboratorium Komputer', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-03.jpeg', 'Fasilitas digital learning'),
('Masjid Sekolah', 'http://localhost/school-website/frontend/assets/images/gallery/masjid-sekolah/masjid-01.jpeg', 'Pusat kegiatan keagamaan siswa');

INSERT INTO `gallery_albums` (`id`,`title`,`slug`,`description`,`sort_order`,`is_active`) VALUES
(1, 'Kegiatan Sekolah', 'kegiatan-sekolah', 'Dokumentasi kegiatan belajar, ruang kelas, dan suasana pembelajaran SIT Permata Hati Bekasi.', 1, 1),
(2, 'Kegiatan Olahraga', 'kegiatan-olahraga', 'Dokumentasi kegiatan lapangan, permainan bola, dan pembiasaan hidup aktif siswa SIT Permata Hati Bekasi.', 2, 1),
(3, 'Masjid Sekolah', 'masjid-sekolah', 'Dokumentasi pembiasaan ibadah, membaca Al-Quran, dan kegiatan ruhiyah siswa di lingkungan sekolah.', 3, 1),
(4, 'Gedung Sekolah', 'gedung-sekolah', 'Dokumentasi gedung dan lingkungan unit pendidikan SIT Permata Hati Bekasi.', 4, 1),
(5, 'Dokumentasi Sekolah', 'dokumentasi-sekolah', 'Kumpulan dokumentasi fasilitas, suasana, dan aktivitas sekolah.', 5, 1);

INSERT INTO `gallery_photos` (`album_id`,`title`,`image`,`description`,`sort_order`) VALUES
(1, 'Pembelajaran Interaktif di Kelas', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-01.jpeg', 'Siswa aktif bertanya dan berdiskusi dalam suasana kelas yang nyaman.', 1),
(1, 'Digital Learning dan Diskusi Kelas', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-02.jpeg', 'Pemanfaatan media digital untuk mendukung proses belajar yang fokus dan terarah.', 2),
(1, 'Suasana Belajar Nyaman', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-03.jpeg', 'Ruang kelas tertata rapi untuk kegiatan belajar yang tertib dan menyenangkan.', 3),
(1, 'Kelas SMPIT Aktif', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-04.jpeg', 'Kegiatan belajar SMPIT yang membangun konsentrasi, adab, dan kemandirian.', 4),
(2, 'Latihan Basket Lapangan', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-01.jpeg', 'Siswa berlatih kerja sama, koordinasi, dan sportivitas melalui permainan basket.', 1),
(2, 'Motorik Ceria TKIT', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-02.jpeg', 'Anak-anak belajar menangkap, melempar, dan bekerja sama lewat aktivitas bola yang menyenangkan.', 2),
(2, 'Basket Outdoor SMPIT', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-03.jpeg', 'Kegiatan olahraga luar ruang untuk menjaga kebugaran, keberanian, dan kekompakan siswa.', 3),
(2, 'Stimulasi Bola Daycare', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-04.jpeg', 'Aktivitas bola ringan untuk melatih motorik kasar anak usia dini dengan suasana aman.', 4),
(2, 'Permainan Bola SDIT', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-05.jpeg', 'Siswa menikmati permainan basket sebagai bagian dari kegiatan jasmani yang sehat.', 5),
(3, 'Tilawah Bersama di Masjid', 'http://localhost/school-website/frontend/assets/images/gallery/masjid-sekolah/masjid-01.jpeg', 'Siswa membaca Al-Quran bersama dalam suasana masjid yang tenang dan khidmat.', 1),
(3, 'Halaqah Al-Quran', 'http://localhost/school-website/frontend/assets/images/gallery/masjid-sekolah/masjid-02.jpeg', 'Kegiatan halaqah dan pembiasaan tilawah untuk menguatkan kedekatan siswa dengan Al-Quran.', 2),
(3, 'Literasi Islami Anak Usia Dini', 'http://localhost/school-website/frontend/assets/images/gallery/masjid-sekolah/masjid-03.jpeg', 'Anak-anak mengenal bacaan dan adab Islami melalui aktivitas literasi yang lembut dan menyenangkan.', 3),
(4, 'Gedung Daycare, TKIT, dan SDIT', 'http://localhost/school-website/frontend/assets/images/school/gedung-sekolah.jpeg', 'Gedung utama SIT Permata Hati Bekasi di kawasan Buwek Jaya, Tambun Selatan.', 1),
(4, 'Gedung SMPIT Permata Hati', 'http://localhost/school-website/frontend/assets/images/school/gedung-smpit.jpeg', 'Gedung SMPIT Permata Hati Bekasi dengan fasilitas belajar dan lapangan sekolah.', 2),
(5, 'Gedung Sekolah', 'http://localhost/school-website/frontend/assets/images/school/gedung-sekolah.jpeg', 'Tampak depan gedung sekolah', 1),
(5, 'Kegiatan Belajar Mengajar', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-01.jpeg', 'Suasana kelas yang nyaman', 2),
(5, 'Lapangan Olahraga', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-olahraga/olahraga-01.jpeg', 'Fasilitas olahraga siswa', 3),
(5, 'Perpustakaan', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-02.jpeg', 'Ruang baca dan koleksi buku', 4),
(5, 'Laboratorium Komputer', 'http://localhost/school-website/frontend/assets/images/gallery/kegiatan-sekolah/kegiatan-03.jpeg', 'Fasilitas digital learning', 5),
(5, 'Masjid Sekolah', 'http://localhost/school-website/frontend/assets/images/gallery/masjid-sekolah/masjid-01.jpeg', 'Pusat kegiatan keagamaan siswa', 6);

-- Selesai
