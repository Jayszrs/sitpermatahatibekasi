<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth(['admin', 'humas']);

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    portal_verify_csrf();
    $phone = trim((string)($_POST['phone'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    if (!preg_match('/^\+?[0-9().\s-]{7,40}$/', $phone) || strlen(preg_replace('/\D/', '', $phone)) < 7) {
        $error = 'Masukkan nomor telepon yang valid.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Masukkan alamat email yang valid.';
    } else {
        $save = $pdo->prepare('INSERT INTO foundation_contact_settings (id,phone,email) VALUES (1,?,?) ON DUPLICATE KEY UPDATE phone=VALUES(phone),email=VALUES(email)');
        $save->execute([$phone, $email]);
        portal_log($pdo, 'save_foundation_contact', 'Memperbarui telepon dan email yayasan');
        portal_flash('success', 'Kontak yayasan berhasil diperbarui.');
        header('Location: ' . SITE_URL . '/portal/foundation-contact');
        exit;
    }
}
$contact = foundation_contact($pdo);
if ($error !== '') $contact = ['phone' => $phone, 'email' => $email];
$portalTitle = 'Kontak Yayasan';
$portalActive = 'foundation-contact';
require __DIR__ . '/../../components/portal-header.php';
?>
<div class="portal-welcome"><div><h2>Kontak Yayasan</h2><p>Nomor telepon dan email ini tampil di website utama. Kontak Daycare, TKIT, SDIT, dan SMPIT diatur terpisah melalui menu Kontak di CMS masing-masing unit.</p></div><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/kontak.php" target="_blank" rel="noopener">Lihat halaman kontak</a></div>
<?php if ($error !== ''): ?><div class="portal-alert danger"><?php echo esc($error); ?></div><?php endif; ?>
<section class="portal-panel"><div class="panel-head"><h3>Telepon dan Email</h3></div><form class="portal-form portal-form-grid" method="post"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><div class="field"><label for="foundation-phone">Nomor telepon</label><input id="foundation-phone" type="tel" name="phone" maxlength="40" value="<?php echo esc($contact['phone']); ?>" required><small>Pengunjung dapat mengetuk nomor untuk langsung menelepon.</small></div><div class="field"><label for="foundation-email">Email yayasan</label><input id="foundation-email" type="email" name="email" maxlength="190" value="<?php echo esc($contact['email']); ?>" required><small>Pengunjung dapat mengetuk alamat untuk membuka aplikasi email.</small></div><div class="form-actions field full"><button class="portal-action" type="submit">Simpan Kontak</button></div></form></section>
<?php require __DIR__ . '/../../components/portal-footer.php'; ?>
