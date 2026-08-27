<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Kontak';

$success = false;
$errors = [];
$old = ['name' => '', 'email' => '', 'whatsapp' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']     = trim($_POST['name'] ?? '');
    $old['email']    = trim($_POST['email'] ?? '');
    $old['whatsapp'] = trim($_POST['whatsapp'] ?? '');
    $old['message']  = trim($_POST['message'] ?? '');

    if ($old['name'] === '') $errors[] = 'Nama wajib diisi.';
    if ($old['email'] === '') {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if ($old['whatsapp'] === '') $errors[] = 'Nomor WhatsApp wajib diisi.';
    if ($old['message'] === '') $errors[] = 'Pesan wajib diisi.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, whatsapp, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$old['name'], $old['email'], $old['whatsapp'], $old['message']]);
        $success = true;
        $old = ['name' => '', 'email' => '', 'whatsapp' => '', 'message' => ''];
    }
}

$campuses = [
    [
        'label' => SITE_DAYCARE_TKIT_CAMPUS_LABEL,
        'title' => 'Daycare & TKIT Permata Hati',
        'address' => SITE_DAYCARE_TKIT_CAMPUS_ADDRESS,
        'latitude' => SITE_DAYCARE_TKIT_LATITUDE,
        'longitude' => SITE_DAYCARE_TKIT_LONGITUDE,
    ],
    [
        'label' => SITE_SDIT_CAMPUS_LABEL,
        'title' => 'SDIT Permata Hati',
        'address' => SITE_SDIT_CAMPUS_ADDRESS,
        'latitude' => SITE_SDIT_LATITUDE,
        'longitude' => SITE_SDIT_LONGITUDE,
    ],
    [
        'label' => SITE_SMPIT_CAMPUS_LABEL,
        'title' => 'SMPIT Permata Hati',
        'address' => SITE_SMPIT_CAMPUS_ADDRESS,
        'latitude' => SITE_SMPIT_LATITUDE,
        'longitude' => SITE_SMPIT_LONGITUDE,
    ],
];

require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Lokasi Sekolah</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Lokasi Sekolah</p>
    </div>
</section>

<section class="section contact-section">
    <div class="container contact-page-layout">
        <div class="contact-section-head">
            <span class="section-eyebrow">Informasi Lokasi</span>
            <h2>Alamat Unit Pendidikan</h2>
        </div>

        <div class="location-card-grid">
            <?php foreach ($campuses as $campus): ?>
                <?php
                    $coordinates = $campus['latitude'] . ',' . $campus['longitude'];
                    $mapEmbed = 'https://maps.google.com/maps?q=' . rawurlencode($coordinates) . '&t=&z=17&ie=UTF8&iwloc=&output=embed';
                    $mapOpen = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($coordinates);
                ?>
                <article class="campus-card">
                    <div class="campus-card-head">
                        <span><?php echo esc($campus['label']); ?></span>
                        <h3><?php echo esc($campus['title']); ?></h3>
                    </div>
                    <p><?php echo esc($campus['address']); ?></p>
                    <div class="map-wrap campus-map">
                        <iframe src="<?php echo esc($mapEmbed); ?>" loading="lazy" title="Lokasi <?php echo esc($campus['title']); ?>"></iframe>
                    </div>
                    <a href="<?php echo esc($mapOpen); ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Petunjuk Arah</a>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="form-card contact-form-card contact-form-landscape">
            <h3 style="margin-bottom:20px;">Kirim Pesan</h3>

            <?php if ($success): ?>
                <div class="alert alert-success">Pesan berhasil dikirim.</div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err) echo esc($err) . '<br>'; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="kontak.php">
                <div class="contact-form-fields">
                    <div class="form-group">
                        <label for="name">Nama *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo esc($old['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo esc($old['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="whatsapp">Nomor WhatsApp *</label>
                        <input type="text" id="whatsapp" name="whatsapp" class="form-control" value="<?php echo esc($old['whatsapp']); ?>" required>
                    </div>
                </div>
                <div class="contact-message-row">
                    <div class="form-group">
                        <label for="message">Pesan *</label>
                        <textarea id="message" name="message" class="form-control" required><?php echo esc($old['message']); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim Pesan</button>
                </div>
            </form>
        </div>

        <div class="contact-admin-card">
            <div class="contact-admin-title">
                <span class="section-eyebrow">Kontak Administrasi</span>
                <h3>Kami Siap Membantu Anda</h3>
            </div>
            <div class="contact-admin-grid">
                <div class="contact-info-item">
                    <div class="contact-info-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.98.36 1.92.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.89.34 1.83.57 2.81.7A2 2 0 0 1 22 16.92Z"></path></svg></div>
                    <div><h4>Telepon</h4><p><?php echo esc(SITE_PHONE); ?></p></div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.5 8.5 0 0 1-12.3 7.6L3 21l1.9-5.7A8.5 8.5 0 1 1 21 11.5Z"></path><path d="M9 9c.4 2.5 2 4.1 4.5 4.8l1.2-1.2 2.1.5c.3.1.5.4.5.7v1.5c0 .4-.3.7-.7.7C11.8 17 7 12.2 7 7.4c0-.4.3-.7.7-.7h1.5c.3 0 .6.2.7.5L10.4 9 9 9Z"></path></svg></div>
                    <div><h4>WhatsApp</h4><p><a href="https://wa.me/<?php echo esc(SITE_WHATSAPP); ?>" target="_blank" rel="noopener">+<?php echo esc(SITE_WHATSAPP); ?></a></p></div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2Z"></path><path d="m22 6-10 7L2 6"></path></svg></div>
                    <div><h4>Email</h4><p><?php echo esc(SITE_EMAIL); ?></p></div>
                </div>
                <div class="contact-info-item contact-social-item">
                    <div class="contact-info-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M16 8h.01"></path><rect x="3" y="3" width="18" height="18" rx="5"></rect></svg></div>
                    <div>
                        <h4>Media Sosial</h4>
                        <div class="contact-social-groups">
                            <div><strong>Instagram</strong><p class="contact-social-links"><a href="<?php echo esc(SITE_DAYCARE_INSTAGRAM); ?>" target="_blank" rel="noopener">Daycare</a><a href="<?php echo esc(SITE_TKIT_INSTAGRAM); ?>" target="_blank" rel="noopener">TKIT</a><a href="<?php echo esc(SITE_SDIT_INSTAGRAM); ?>" target="_blank" rel="noopener">SDIT</a><a href="<?php echo esc(SITE_SMPIT_INSTAGRAM); ?>" target="_blank" rel="noopener">SMPIT</a></p></div>
                            <div><strong>YouTube</strong><p class="contact-social-links"><a href="<?php echo esc(SITE_SDIT_YOUTUBE); ?>" target="_blank" rel="noopener">SDIT</a><a href="<?php echo esc(SITE_SMPIT_YOUTUBE); ?>" target="_blank" rel="noopener">SMPIT</a></p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
