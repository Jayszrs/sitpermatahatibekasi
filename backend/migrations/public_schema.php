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

