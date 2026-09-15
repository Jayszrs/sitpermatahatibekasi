<?php
/**
 * Jembatan dari portal unit (daycare/tkit/sdit/smpit, yang biasanya hanya
 * memakai database unit_db()/school_units_portal) ke database utama yayasan,
 * dipakai untuk fitur yang datanya memang harus tunggal/gabungan lintas unit
 * (SPMB -> spmb_registrations, Karir -> job_vacancies/job_applications).
 *
 * require_once dipanggil di dalam fungsi ini (bukan di top-level file unit)
 * supaya variabel $pdo yang didefinisikan backend/config/database.php tetap
 * lokal terhadap fungsi ini dan tidak menimpa $pdo milik unit_db() di scope
 * pemanggil.
 */
function unit_connect_main_site_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/spmb_shared.php';
    return $pdo;
}

/** Ambil post Instagram resmi untuk unit aktif, memakai sumber yang sama dengan beranda. */
function unit_instagram_gallery_items(string $profileUrl, int $limit = 12): array {
    $mainPdo = unit_connect_main_site_pdo();
    $stmt = $mainPdo->prepare("SELECT * FROM instagram_gallery WHERE scope=? AND is_active=1 AND media_type='embed' ORDER BY sort_order,id LIMIT 24");
    $stmt->execute([UNIT_SLUG]);
    return instagram_verified_gallery(
        $stmt->fetchAll(),
        [UNIT_SLUG => instagram_profile_username($profileUrl) ?: ''],
        $limit
    );
}

function unit_render_instagram_cards(array $items, array $settings): void {
    global $unit_config;
    if (!$items) {
        echo '<div class="unit-social-empty"><strong>Postingan sedang disinkronkan</strong><p>Silakan kunjungi akun Instagram resmi kami untuk melihat kabar terbaru.</p></div>';
        return;
    }
    echo '<div class="ig-gallery-grid unit-gallery-instagram-grid">';
    foreach ($items as $socialItem) {
        $media = $socialItem['public_media'];
        $isVideo = !empty($media['video']);
        $caption = ($media['caption'] ?? null) ?: (($socialItem['caption'] ?? null) ?: 'Momen terbaru '.$unit_config['short_name'].' di Instagram.');
        echo '<article class="ig-gallery-card ig-native-card" data-ig-card data-ig-state="'.($isVideo ? 'video' : 'image').'">';
        echo '<header class="ig-card-head"><span class="ig-card-brand'.(!empty($media['profile_image']) ? ' ig-card-avatar' : '').'" aria-hidden="true">';
        if (!empty($media['profile_image'])) echo '<img src="'.unit_e($media['profile_image']).'" alt="" loading="lazy" decoding="async">';
        else echo '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" class="ig-dot"/></svg>';
        echo '</span><span class="ig-card-identity"><strong>'.unit_e($unit_config['short_name']).'</strong><small data-ig-username>@'.unit_e($media['username']).'</small></span><a class="ig-card-open" href="'.unit_e($socialItem['instagram_url']).'" target="_blank" rel="noopener" aria-label="Buka postingan di Instagram">&nearr;</a></header>';
        echo '<div class="ig-gallery-media"><img class="ig-media-poster" src="'.unit_e($media['image']).'" alt="'.unit_e($caption).'" loading="lazy" decoding="async">';
        if ($isVideo) echo '<video class="ig-media-video" data-ig-video-src="'.unit_e($media['video']).'" poster="'.unit_e($media['image']).'" autoplay muted loop playsinline controls preload="none"></video>';
        echo '<span class="ig-media-shade" aria-hidden="true"></span><span class="ig-media-kind" data-ig-kind>'.($isVideo ? 'REEL' : 'POST').'</span></div>';
        echo '<footer class="ig-gallery-foot"><p data-ig-caption>'.unit_e($caption).'</p><a href="'.unit_e($socialItem['instagram_url']).'" target="_blank" rel="noopener"><span>Lihat postingan</span><span aria-hidden="true">&rarr;</span></a></footer></article>';
    }
    echo '</div><div class="ig-gallery-cta"><a class="button button-primary" href="'.unit_e($settings['instagram']).'" target="_blank" rel="noopener">Ikuti Instagram '.unit_e($unit_config['short_name']).'</a></div>';
}

function unit_render_gallery_page(PDO $pdo, array $settings): void {
    global $unit_config;
    $selected = (int)($_GET['album'] ?? 0);
    $albums = unit_albums($pdo);
    if ($selected) {
        $photos = unit_album_photos($pdo, $selected);
        $selectedAlbum = null;
        foreach ($albums as $album) if ((int)$album['id'] === $selected) $selectedAlbum = $album;
        echo '<section class="section"><div class="shell"><a class="text-link" href="gallery.php">Kembali ke galeri</a><div class="section-head"><span class="eyebrow">ALBUM WEBSITE</span><h2>'.unit_e($selectedAlbum['title'] ?? 'Galeri').'</h2></div>';
        if (!$photos) echo '<p class="empty-state">Belum ada foto pada album ini.</p>';
        else {
            echo '<div class="photo-grid">';
            foreach ($photos as $photo) {
                $image = unit_media($photo['image']);
                echo '<button class="photo-button" type="button" data-lightbox="'.unit_e($image).'" data-title="'.unit_e($photo['title']).'"><img src="'.unit_e($image).'" alt="'.unit_e($photo['title']).'" loading="lazy" decoding="async"><span>'.unit_e($photo['title']).'</span></button>';
            }
            echo '</div>';
        }
        echo '</div></section>';
        return;
    }

    $items = unit_instagram_gallery_items($settings['instagram'], 12);
    echo '<section class="section unit-gallery-social"><div class="shell"><div class="section-head"><span class="eyebrow">POSTINGAN RESMI</span><h2>Instagram '.unit_e($unit_config['short_name']).'</h2><p>Postingan terbaru dari akun resmi kami. Reel berjalan otomatis tanpa suara saat terlihat di layar.</p></div>';
    unit_render_instagram_cards($items, $settings);
    echo '</div></section>';

    if ($albums) {
        echo '<section class="section section-soft unit-gallery-archive"><div class="shell"><div class="section-head"><span class="eyebrow">DOKUMENTASI WEBSITE</span><h2>Album Kegiatan Sekolah</h2><p>Dokumentasi tambahan yang dikelola langsung oleh admin '.unit_e($unit_config['short_name']).'.</p></div><div class="album-grid">';
        foreach ($albums as $album) {
            $cover = unit_media($album['cover_image']);
            echo '<a class="album-card" href="gallery.php?album='.(int)$album['id'].'"><img src="'.unit_e($cover).'" alt="'.unit_e($album['title']).'" loading="lazy" decoding="async"><div><span>'.(int)$album['photo_count'].' foto</span><h2>'.unit_e($album['title']).'</h2><p>'.unit_e($album['description']).'</p></div></a>';
        }
        echo '</div></div></section>';
    }
}

function unit_render_spmb_page(array $settings, array $unitConfig, array $old, bool $success, string $error, string $lockedLevel): void {
    $year = date('Y');
    $academicYears = spmb_academic_years();
    $profiles = [
        'daycare' => ['audience' => 'anak usia dini', 'assessment' => 'Observasi tumbuh kembang', 'requirements' => ['Fotokopi akta kelahiran','Fotokopi kartu keluarga','Fotokopi KTP orang tua/wali','Pas foto berwarna','Catatan imunisasi bila tersedia']],
        'tkit' => ['audience' => 'anak usia 4–6 tahun', 'assessment' => 'Observasi kesiapan belajar', 'requirements' => ['Fotokopi akta kelahiran','Fotokopi kartu keluarga','Fotokopi KTP orang tua/wali','Pas foto berwarna 3×4','Rapor atau laporan perkembangan bila tersedia']],
        'sdit' => ['audience' => 'calon siswa sekolah dasar', 'assessment' => 'Pemetaan kesiapan dan wawancara', 'requirements' => ['Fotokopi akta kelahiran','Fotokopi kartu keluarga','Fotokopi KTP orang tua/wali','Pas foto berwarna 3×4','Rapor atau laporan perkembangan terakhir']],
        'smpit' => ['audience' => 'calon siswa sekolah menengah pertama', 'assessment' => 'Tes pemetaan dan wawancara', 'requirements' => ['Fotokopi akta kelahiran','Fotokopi kartu keluarga','Fotokopi KTP orang tua/wali','Pas foto berwarna 3×4','Fotokopi rapor dan ijazah/Surat Keterangan Lulus']],
    ];
    $profile = $profiles[UNIT_SLUG] ?? $profiles['sdit'];
    $image = unit_media($unitConfig['hero_image']);
    $whatsapp = preg_replace('/\D+/', '', (string)$settings['whatsapp']);
    ?>
    <section class="unit-spmb-hero"><div class="shell unit-spmb-hero-grid"><div><span class="eyebrow light">SPMB <?php echo unit_e($year.'/'.($year + 1)); ?></span><h2>Bergabung bersama <?php echo unit_e($settings['name']); ?></h2><p>Pendaftaran resmi untuk <?php echo unit_e($profile['audience']); ?>. Dapatkan lingkungan belajar Islami, hangat, dan terarah sesuai tahap perkembangan anak.</p><div class="hero-actions"><a class="button button-accent" href="#formulir-spmb">Daftar Sekarang</a><a class="button button-ghost" href="https://wa.me/<?php echo unit_e($whatsapp); ?>" target="_blank" rel="noopener">Tanya Admin</a></div></div><img src="<?php echo unit_e($image); ?>" alt="Suasana <?php echo unit_e($settings['name']); ?>" decoding="async"></div></section>
    <section class="section section-soft"><div class="shell"><div class="section-head"><span class="eyebrow">ALUR PENDAFTARAN</span><h2>Empat langkah mudah menjadi keluarga besar kami</h2></div><div class="unit-spmb-steps"><article><b>01</b><h3>Isi Formulir</h3><p>Lengkapi data calon siswa dan orang tua secara online.</p></article><article><b>02</b><h3>Verifikasi Data</h3><p>Admin menghubungi orang tua melalui WhatsApp.</p></article><article><b>03</b><h3><?php echo unit_e($profile['assessment']); ?></h3><p>Proses pengenalan disesuaikan dengan jenjang dan usia anak.</p></article><article><b>04</b><h3>Daftar Ulang</h3><p>Lengkapi dokumen dan administrasi setelah dinyatakan diterima.</p></article></div></div></section>
    <section class="section"><div class="shell unit-spmb-info-grid"><div><div class="section-head"><span class="eyebrow">PERSYARATAN</span><h2>Dokumen yang disiapkan</h2></div><ul class="unit-spmb-requirements"><?php foreach($profile['requirements'] as $requirement): ?><li><?php echo unit_e($requirement); ?></li><?php endforeach; ?></ul></div><div><div class="section-head"><span class="eyebrow">JADWAL</span><h2>Periode pendaftaran</h2></div><div class="unit-spmb-schedule"><div><strong>Gelombang 1</strong><span>Januari – Februari</span></div><div><strong>Gelombang 2</strong><span>Maret – April</span></div><div><strong>Gelombang 3</strong><span>Mei – Juni</span></div><div><strong>Daftar Ulang</strong><span>Juli</span></div></div><p class="field-help">Kuota dan biaya terbaru dikonfirmasi langsung oleh admin <?php echo unit_e($unitConfig['short_name']); ?>.</p></div></div></section>
    <section class="section section-soft" id="formulir-spmb"><div class="shell unit-spmb-form-layout"><div class="unit-spmb-form-copy"><span class="eyebrow">FORMULIR RESMI</span><h2>Mulai pendaftaran <?php echo unit_e($settings['name']); ?></h2><p>Formulir ini langsung tercatat pada sistem SPMB yayasan dengan jenjang <?php echo unit_e($lockedLevel); ?> yang sudah terkunci.</p><div class="unit-spmb-contact-card"><strong>Perlu bantuan?</strong><p>Hubungi admin melalui WhatsApp untuk konsultasi kuota, biaya, dan kunjungan sekolah.</p><a href="https://wa.me/<?php echo unit_e($whatsapp); ?>" target="_blank" rel="noopener">Hubungi Admin &rarr;</a></div></div>
    <form method="post" class="public-form unit-spmb-form"><input type="hidden" name="csrf" value="<?php echo unit_e(unit_csrf()); ?>"><input type="hidden" name="level" value="<?php echo unit_e($lockedLevel); ?>"><p class="form-locked-level">Mendaftar untuk jenjang: <strong><?php echo unit_e($lockedLevel); ?></strong></p>
    <?php if ($success): ?><p class="form-success">Pendaftaran berhasil dikirim. Tim kami akan segera menghubungi Anda melalui WhatsApp.</p><?php endif; ?><?php if ($error !== ''): ?><p class="form-error"><?php echo unit_e($error); ?></p><?php endif; ?>
    <label>Nama Calon Siswa *<input required name="student_name" value="<?php echo unit_e($old['student_name']); ?>"></label><div class="form-pair"><label>NIK Calon Siswa<input name="student_nik" value="<?php echo unit_e($old['student_nik']); ?>"></label><label>Jenis Kelamin<select name="gender"><option value="">-- Pilih --</option><option value="L" <?php echo $old['gender']==='L'?'selected':''; ?>>Laki-laki</option><option value="P" <?php echo $old['gender']==='P'?'selected':''; ?>>Perempuan</option></select></label></div><div class="form-pair"><label>Tempat Lahir<input name="birth_place" value="<?php echo unit_e($old['birth_place']); ?>"></label><label>Tanggal Lahir<input type="date" name="birth_date" value="<?php echo unit_e($old['birth_date']); ?>"></label></div><label>Nama Orang Tua *<input required name="parent_name" value="<?php echo unit_e($old['parent_name']); ?>"></label><div class="form-pair"><label>NIK Orang Tua/Wali<input name="parent_nik" value="<?php echo unit_e($old['parent_nik']); ?>"></label><label>Nomor Kartu Keluarga<input name="family_card_number" value="<?php echo unit_e($old['family_card_number']); ?>"></label></div><label>Nomor WhatsApp *<input required name="whatsapp" inputmode="tel" value="<?php echo unit_e($old['whatsapp']); ?>"></label><label>Tahun Ajaran *<select required name="academic_year"><?php foreach ($academicYears as $schoolYear => $track): ?><option value="<?php echo unit_e($schoolYear); ?>" <?php echo $old['academic_year']===$schoolYear?'selected':''; ?>><?php echo unit_e($schoolYear.' — '.$track); ?></option><?php endforeach; ?></select><small class="field-help">Tahun berikutnya otomatis tercatat sebagai waiting list.</small></label><label>Asal Sekolah<input name="previous_school" value="<?php echo unit_e($old['previous_school']); ?>"></label><label>Alamat Lengkap<textarea name="address" rows="4"><?php echo unit_e($old['address']); ?></textarea></label><button class="button button-primary" type="submit">Kirim Pendaftaran</button></form></div></section>
    <section class="section"><div class="shell"><div class="section-head"><span class="eyebrow">PERTANYAAN UMUM</span><h2>Informasi sebelum mendaftar</h2></div><div class="unit-spmb-faq"><details><summary>Apakah bisa berkonsultasi sebelum mendaftar?</summary><p>Bisa. Admin unit siap membantu menjelaskan program, kuota, biaya, dan jadwal kunjungan.</p></details><details><summary>Apakah formulir ini khusus <?php echo unit_e($unitConfig['short_name']); ?>?</summary><p>Ya. Jenjang otomatis terkunci untuk <?php echo unit_e($lockedLevel); ?> sehingga data masuk ke unit yang tepat.</p></details><details><summary>Bagaimana jika kuota sudah penuh?</summary><p>Admin akan menyampaikan pilihan gelombang berikutnya atau status daftar tunggu.</p></details></div></div></section>
    <?php
}
