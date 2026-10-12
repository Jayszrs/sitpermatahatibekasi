<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/helpers/spmb_workflow.php';
header('Referrer-Policy: no-referrer');
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
if (!preg_match('/^[a-f0-9]{64}$/', $token)) { http_response_code(404); exit('Tautan pembayaran tidak valid.'); }
$stmt = $pdo->prepare('SELECT * FROM spmb_registrations WHERE payment_access_token=?');
$stmt->execute([$token]);
$registration = $stmt->fetch();
if (!$registration) { http_response_code(404); exit('Pendaftaran tidak ditemukan.'); }
$error = '';
$success = isset($_GET['sent']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        public_verify_csrf($_POST['_token'] ?? null);
        $rate = $pdo->prepare("SELECT COUNT(*) FROM spmb_payment_claims WHERE registration_id=? AND status='pending' AND created_at>NOW()-INTERVAL 1 HOUR");
        $rate->execute([(int)$registration['id']]);
        if ((int)$rate->fetchColumn() >= 3) throw new RuntimeException('Sudah ada beberapa bukti menunggu pemeriksaan. Hubungi admin SPMB bila perlu bantuan.');
        spmb_add_payment_claim($pdo,(int)$registration['id'],$_POST,$_FILES['proof'] ?? null,'online');
        header('Location: ' . spmb_payment_url($token) . '&sent=1');
        exit;
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
$stmt = $pdo->prepare('SELECT id,claimed_amount,payment_method,payment_date,status,review_notes,created_at FROM spmb_payment_claims WHERE registration_id=? ORDER BY created_at DESC,id DESC');
$stmt->execute([(int)$registration['id']]);
$claims = $stmt->fetchAll();
$page_title = 'Pembayaran SPMB';
require __DIR__ . '/../../components/header.php';
?>
<style>
.spmb-pay-shell{max-width:1000px;margin:40px auto;padding:0 20px 60px}.spmb-pay-intro{background:#095c43;color:#fff;border-radius:22px;padding:28px;margin-bottom:20px}.spmb-pay-intro h1{color:#fff;margin:8px 0}.spmb-pay-intro p{color:#e5f5ed}.spmb-pay-grid{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(270px,.7fr);gap:18px}.spmb-pay-card{background:#fff;border:1px solid #e0e9e3;border-radius:18px;padding:24px;box-shadow:0 14px 30px #142f2610}.spmb-pay-card h2{font-size:1.2rem;margin-bottom:14px}.spmb-pay-card label{display:block;font-weight:700;margin:14px 0 6px}.spmb-pay-card input,.spmb-pay-card select{width:100%;padding:11px;border:1px solid #cad8ce;border-radius:9px;font:inherit}.spmb-pay-card button{background:#08644a;color:#fff;border:0;border-radius:9px;padding:12px 18px;font-weight:800;margin-top:18px;cursor:pointer}.spmb-pay-note{background:#eaf7ef;padding:14px;border-radius:10px;color:#285442}.spmb-pay-error{background:#fff0ed;color:#9b2c20;padding:12px;border-radius:9px;margin:12px 0}.spmb-pay-list{list-style:none;padding:0;margin:0}.spmb-pay-list li{padding:12px 0;border-bottom:1px solid #e5ece7}.spmb-pay-list small{display:block;color:#66786d}.spmb-pay-status{font-weight:800;color:#087153}.spmb-pay-status.rejected{color:#b3312d}.spmb-pay-status.pending{color:#9b6400}@media(max-width:760px){.spmb-pay-grid{grid-template-columns:1fr}}
</style>
<main class="spmb-pay-shell">
<div class="spmb-pay-intro"><small>SPMB · <?php echo esc(strtoupper(school_unit_slug($registration['level']))); ?></small><h1>Konfirmasi Pembayaran</h1><p><strong><?php echo esc($registration['student_name']); ?></strong> · <?php echo esc($registration['registration_number']); ?></p><p>Nominal pada bukti akan masuk antrean. Total terbayar bertambah setelah petugas mencocokkan transaksi dengan mutasi rekening.</p></div>
<div class="spmb-pay-grid"><section class="spmb-pay-card"><h2>Kirim bukti transfer</h2><?php if($success): ?><p class="spmb-pay-note">Bukti terkirim dan menunggu pemeriksaan admin SPMB.</p><?php endif; ?><?php if($error): ?><p class="spmb-pay-error"><?php echo esc($error); ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?php echo esc(public_form_csrf_token()); ?>"><input type="hidden" name="token" value="<?php echo esc($token); ?>">
<label>Jenis pembayaran<select name="payment_type"><option>Pembayaran SPMB</option><option>Formulir Pendaftaran</option><option>Daftar Ulang</option><option>Uang Pangkal</option><option>Seragam</option><option>SPP</option><option>Lainnya</option></select></label>
<label>Nominal yang ditransfer (Rp)<input type="number" min="1" step="1" name="amount" required placeholder="Contoh: 500000"></label>
<label>Metode<select name="payment_method"><option>Transfer Bank</option><option>QRIS</option><option>Virtual Account</option></select></label>
<label>Tanggal transfer<input type="date" name="payment_date" value="<?php echo esc(date('Y-m-d')); ?>" required></label>
<label>Nama pengirim<input name="payer_name" maxlength="150" value="<?php echo esc($registration['parent_name']); ?>"></label>
<label>Nomor referensi transfer (jika ada)<input name="reference_number" maxlength="100"></label>
<label>Screenshot bukti pembayaran<input type="file" name="proof" accept="image/jpeg,image/png,image/webp" required></label><small>JPG, PNG, atau WebP; maksimal 6 MB.</small><br><button type="submit">Kirim untuk Diverifikasi</button></form></section>
<aside class="spmb-pay-card"><h2>Ringkasan</h2><p>Total terverifikasi<br><strong>Rp <?php echo number_format((float)$registration['payment_amount'],0,',','.'); ?></strong></p><p>Tagihan tercatat<br><strong><?php echo (float)$registration['total_due'] > 0 ? 'Rp '.number_format((float)$registration['total_due'],0,',','.') : 'Menunggu penetapan admin'; ?></strong></p><p>Status: <strong><?php echo esc(ucwords(str_replace('_',' ',$registration['payment_status']))); ?></strong></p><h2 style="margin-top:25px">Riwayat bukti</h2><ul class="spmb-pay-list"><?php foreach($claims as $claim): ?><li><strong>Rp <?php echo number_format((float)$claim['claimed_amount'],0,',','.'); ?></strong><small><?php echo esc($claim['payment_date']); ?> · <?php echo esc($claim['payment_method']); ?></small><span class="spmb-pay-status <?php echo esc($claim['status']); ?>"><?php echo esc(['pending'=>'Menunggu pemeriksaan','verified'=>'Terverifikasi','rejected'=>'Ditolak','voided'=>'Verifikasi dibatalkan'][$claim['status']] ?? $claim['status']); ?></span><?php if(in_array($claim['status'],['rejected','voided'],true) && $claim['review_notes']): ?><small><?php echo esc($claim['review_notes']); ?></small><?php endif; ?></li><?php endforeach; ?><?php if(!$claims): ?><li>Belum ada bukti pembayaran.</li><?php endif; ?></ul></aside></div></main>
<?php require __DIR__ . '/../../components/footer.php'; ?>
