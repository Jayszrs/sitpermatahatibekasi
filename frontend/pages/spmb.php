<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'SPMB - Penerimaan Murid Baru';
$spmbUnits = $pdo->query("SELECT * FROM site_content_items WHERE type='unit' AND is_active=1 ORDER BY sort_order,id")->fetchAll();
$spmbUnitNames = array_map(static fn(array $unit): string => (string) ($unit['subtitle'] ?: $unit['title']), $spmbUnits);
$spmbUnitImageMap = [
    'daycare' => SITE_URL . '/frontend/assets/images/brochures/daycare-promo.png',
    'tkit' => SITE_URL . '/frontend/assets/images/brochures/tkit-promo.png',
    'sdit' => SITE_URL . '/frontend/assets/images/brochures/sdit-promo.png',
    'smpit' => SITE_URL . '/frontend/assets/images/brochures/smpit-promo.png',
];
require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Penerimaan Murid Baru</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / SPMB</p>
    </div>
</section>

<!-- HEADLINE -->
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-content">
            <span class="hero-eyebrow">SPMB <?php echo date('Y'); ?>/<?php echo date('Y') + 1; ?></span>
            <h1>Bergabunglah Bersama <span><?php echo esc(SITE_NAME); ?></span></h1>
            <p>Pendaftaran murid baru tahun ajaran <?php echo date('Y'); ?>/<?php echo date('Y') + 1; ?> resmi dibuka untuk <?php echo esc(implode(', ', $spmbUnitNames)); ?>. Persiapkan masa depan terbaik untuk putra-putri Anda bersama kami.</p>
            <div class="hero-actions">
                <a href="form-spmb.php" class="btn btn-primary">Daftar Sekarang</a>
                <a href="https://wa.me/<?php echo esc(SITE_WHATSAPP); ?>" target="_blank" rel="noopener" class="btn btn-outline-light">Hubungi WhatsApp</a>
            </div>
        </div>
        <div class="hero-media">
            <img src="<?php echo SITE_URL; ?>/frontend/assets/images/school/gedung-sekolah.jpeg" alt="Gedung SIT Permata Hati Bekasi">
        </div>
    </div>
</section>

<!-- UNIT TERSEDIA -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Unit Tersedia</span>
            <h2>Jenjang Pendaftaran</h2>
        </div>
        <div class="spmb-unit-grid">
            <?php foreach($spmbUnits as $unit): ?><?php $unitKey = strtolower((string) $unit['subtitle']); $unitImage = $unit['image'] ?: ($spmbUnitImageMap[$unitKey] ?? SITE_URL . '/frontend/assets/images/school/gedung-sekolah.jpeg'); ?><div class="card"><img src="<?php echo esc($unitImage); ?>" data-fallback="<?php echo SITE_URL; ?>/frontend/assets/images/school/gedung-sekolah.jpeg" alt="<?php echo esc($unit['title']); ?>"><div class="card-body"><h3><?php echo esc($unit['title']); ?></h3><p><?php echo esc(mb_strimwidth($unit['description'],0,150,'...')); ?></p><a class="btn btn-outline btn-sm" href="form-spmb.php?level=<?php echo urlencode($unit['subtitle']); ?>">Pilih Jenjang</a></div></div><?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ALUR PENDAFTARAN -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Alur Pendaftaran</span>
            <h2>Langkah Mudah Mendaftar</h2>
        </div>
        <div class="steps-grid">
            <div class="step-item"><div class="step-num">1</div><h3>Isi Formulir</h3><p>Lengkapi formulir pendaftaran online melalui website.</p></div>
            <div class="step-item"><div class="step-num">2</div><h3>Verifikasi Data</h3><p>Tim kami akan menghubungi Anda untuk verifikasi data.</p></div>
            <div class="step-item"><div class="step-num">3</div><h3>Tes &amp; Wawancara</h3><p>Calon siswa mengikuti tes akademik dan wawancara orang tua.</p></div>
            <div class="step-item"><div class="step-num">4</div><h3>Daftar Ulang</h3><p>Melakukan pembayaran dan daftar ulang setelah dinyatakan lulus.</p></div>
        </div>
    </div>
</section>

<!-- PERSYARATAN -->
<section class="section section-alt">
    <div class="container grid-2">
        <div>
            <div class="section-eyebrow">Persyaratan</div>
            <h2 style="margin-bottom:20px;">Dokumen yang Dibutuhkan</h2>
            <ul class="req-list">
                <li>Mengisi formulir pendaftaran</li>
                <li>Fotokopi akta kelahiran</li>
                <li>Fotokopi kartu keluarga</li>
                <li>Fotokopi rapor terakhir</li>
                <li>Fotokopi ijazah atau rapor sesuai jenjang</li>
                <li>Pas foto berwarna 3x4 (2 lembar)</li>
            </ul>
        </div>
        <div>
            <div class="section-eyebrow">Biaya Pendaftaran</div>
            <h2 style="margin-bottom:20px;">Estimasi Biaya</h2>
            <div class="table-wrap">
                <table class="schedule">
                    <tr><th>Jenjang</th><th>Biaya Pendaftaran</th></tr>
                    <?php foreach ($spmbUnits as $unit): ?><tr><td><?php echo esc($unit['title']); ?></td><td>Hubungi Admin SPMB</td></tr><?php endforeach; ?>
                </table>
            </div>
            <p style="color: var(--muted); font-size:0.85rem; margin-top:14px;">*Biaya dapat berubah, informasi terbaru hubungi bagian pendaftaran.</p>
        </div>
    </div>
</section>

<!-- JADWAL -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Jadwal</span>
            <h2>Jadwal Pendaftaran</h2>
        </div>
        <div class="table-wrap">
            <table class="schedule">
                <tr><th>Tahap</th><th>Periode</th></tr>
                <tr><td>Gelombang 1</td><td>Januari - Februari</td></tr>
                <tr><td>Gelombang 2</td><td>Maret - April</td></tr>
                <tr><td>Gelombang 3</td><td>Mei - Juni</td></tr>
                <tr><td>Daftar Ulang</td><td>Juli</td></tr>
            </table>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">FAQ</span>
            <h2>Pertanyaan Umum</h2>
        </div>
        <div style="max-width: 760px; margin: 0 auto;">
            <details class="faq-item">
                <summary>Apakah ada tes masuk untuk calon siswa?</summary>
                <p>Ya, calon siswa akan mengikuti tes akademik dasar dan wawancara bersama orang tua sesuai jenjang yang dituju.</p>
            </details>
            <details class="faq-item">
                <summary>Apakah tersedia program beasiswa?</summary>
                <p>Sekolah menyediakan program beasiswa prestasi dan bantuan bagi siswa yatim/dhuafa dengan ketentuan tertentu.</p>
            </details>
            <details class="faq-item">
                <summary>Bagaimana jika kuota jenjang sudah penuh?</summary>
                <p>Pendaftar akan dimasukkan ke dalam daftar tunggu dan dihubungi apabila terdapat kuota tambahan.</p>
            </details>
            <details class="faq-item">
                <summary>Apakah bisa konsultasi sebelum mendaftar?</summary>
                <p>Tentu, Anda dapat menghubungi tim kami melalui WhatsApp untuk konsultasi seputar pendaftaran.</p>
            </details>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section">
    <div class="container">
        <div class="cta-spmb">
            <h2>Siap Bergabung Bersama Kami?</h2>
            <p>Jangan lewatkan kesempatan memberikan pendidikan terbaik untuk putra-putri Anda.</p>
            <div class="cta-actions">
                <a href="form-spmb.php" class="btn btn-gold">DAFTAR SEKARANG</a>
                <a href="https://wa.me/<?php echo esc(SITE_WHATSAPP); ?>" target="_blank" rel="noopener" class="btn btn-outline-light">HUBUNGI WHATSAPP</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
