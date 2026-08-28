<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Al Quran';
require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header quran-page-header">
    <div class="container">
        <h1>Program Al Quran</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Al Quran</p>
    </div>
</section>

<section class="section quran-page">
    <div class="container quran-hero-grid">
        <div class="quran-copy">
            <span class="section-eyebrow">Tahsin & Tahfidz</span>
            <h2>Membiasakan Dekat Dengan Al Quran Sejak Dini</h2>
            <p>Program Al Quran SIT Permata Hati Bekasi dirancang bertahap untuk Daycare, TKIT, SDIT, dan SMPIT melalui pembiasaan tilawah, tahsin bacaan, hafalan, adab, serta murojaah yang konsisten.</p>
            <div class="quran-actions">
                <a href="form-spmb.php" class="btn btn-primary">Daftar SPMB</a>
                <a href="kontak.php" class="btn btn-outline">Konsultasi Program</a>
            </div>
        </div>
        <div class="quran-feature-card">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5Z"/><path d="M8 7h8"/><path d="M8 11h6"/></svg>
            <h3>Target Bertahap</h3>
            <p>Belajar sesuai usia dan kemampuan anak, dari pengenalan adab membaca hingga hafalan dan murojaah rutin.</p>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Fokus Program</span>
            <h2>Ruang Belajar Al Quran</h2>
        </div>
        <div class="quran-program-grid">
            <div class="quran-program-card"><h3>Tahsin Bacaan</h3><p>Pembinaan makhraj, tajwid dasar, dan kelancaran membaca Al Quran.</p></div>
            <div class="quran-program-card"><h3>Tahfidz</h3><p>Hafalan bertahap dengan pendampingan guru dan jadwal murojaah.</p></div>
            <div class="quran-program-card"><h3>Adab Qurani</h3><p>Pembiasaan sikap santun, disiplin, dan cinta ibadah dalam keseharian.</p></div>
            <div class="quran-program-card"><h3>Murojaah</h3><p>Penguatan hafalan melalui pengulangan terarah di sekolah dan rumah.</p></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container quran-flow">
        <div class="section-head">
            <span class="section-eyebrow">Alur Pembinaan</span>
            <h2>Dari Pembiasaan Sampai Kemandirian</h2>
        </div>
        <div class="quran-flow-grid">
            <div><span>01</span><h3>Daycare & TKIT</h3><p>Pengenalan doa harian, surat pendek, dan adab bersama Al Quran.</p></div>
            <div><span>02</span><h3>SDIT</h3><p>Tahsin, hafalan juz 30, dan pembiasaan murojaah rutin.</p></div>
            <div><span>03</span><h3>SMPIT</h3><p>Penguatan bacaan, hafalan lanjutan, dan tanggung jawab ibadah pribadi.</p></div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
