<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Karir';
require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header career-page-header">
    <div class="container">
        <h1>Karir</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Karir</p>
    </div>
</section>

<section class="section career-section">
    <div class="container career-intro">
        <div>
            <span class="section-eyebrow">Bergabung Bersama Kami</span>
            <h2>Menjadi Bagian Dari Pendidikan Islam Yang Bermakna</h2>
            <p>SIT Permata Hati Bekasi membuka ruang bagi pendidik dan tenaga profesional yang ingin bertumbuh bersama dalam lingkungan kerja Islami, kolaboratif, dan berorientasi pada perkembangan anak.</p>
            <a href="mailto:<?php echo esc(SITE_EMAIL); ?>?subject=Lamaran%20Karir%20SIT%20Permata%20Hati%20Bekasi" class="btn btn-primary">Kirim Lamaran</a>
        </div>
        <div class="career-note">
            <h3>Dokumen Lamaran</h3>
            <ul>
                <li>CV terbaru</li>
                <li>Surat lamaran</li>
                <li>Ijazah dan transkrip</li>
                <li>Portofolio atau sertifikat pendukung</li>
            </ul>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Posisi</span>
            <h2>Kebutuhan Tenaga Pendidikan</h2>
            <p>Daftar posisi dapat berubah sesuai kebutuhan unit sekolah.</p>
        </div>
        <div class="career-grid">
            <div class="career-card"><h3>Guru Daycare/TKIT</h3><p>Mendampingi tumbuh kembang anak usia dini dengan pendekatan hangat dan kreatif.</p></div>
            <div class="career-card"><h3>Guru Kelas SDIT</h3><p>Mengajar pembelajaran tematik, literasi, numerasi, dan pembiasaan karakter Islami.</p></div>
            <div class="career-card"><h3>Guru Al Quran</h3><p>Membina tahsin, tahfidz, dan murojaah siswa secara bertahap.</p></div>
            <div class="career-card"><h3>Guru SMPIT</h3><p>Menguatkan akademik, karakter, dan kepemimpinan siswa jenjang menengah.</p></div>
            <div class="career-card"><h3>Staf Administrasi</h3><p>Mengelola layanan administrasi sekolah dengan rapi, ramah, dan teliti.</p></div>
            <div class="career-card"><h3>Staf Humas</h3><p>Mendukung komunikasi sekolah, publikasi kegiatan, dan layanan informasi orang tua.</p></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="career-cta">
            <div>
                <h2>Siap Mengirim Lamaran?</h2>
                <p>Kirimkan berkas lamaran ke email sekolah atau hubungi admin melalui WhatsApp untuk informasi posisi terbaru.</p>
            </div>
            <div class="cta-actions">
                <a href="mailto:<?php echo esc(SITE_EMAIL); ?>" class="btn btn-gold">Email Sekolah</a>
                <a href="https://wa.me/<?php echo esc(SITE_WHATSAPP); ?>" target="_blank" rel="noopener" class="btn btn-outline-light">WhatsApp</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
