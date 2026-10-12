<?php
$spmbEntryBase = '?tab=enrollments&unit=' . rawurlencode($scope);
$manualEntry = isset($_GET['new_spmb']);
$paymentEntry = null;
if (isset($_GET['record_payment_spmb'])) {
    try { $paymentEntry = spmb_unit_registration($mainPdo, (int)$_GET['record_payment_spmb'], $scope); }
    catch (RuntimeException $entryError) { ?><div class="portal-alert danger"><?php echo unit_e($entryError->getMessage()); ?></div><?php }
}
?>
<?php if($manualEntry): $entryOld = spmb_fields_from_post($_POST, school_unit_catalog()[$scope]['subtitle']); ?>
<section class="panel spmb-entry" id="spmb-entry">
    <div class="spmb-section-head"><div><h2>Input pendaftar manual</h2><p>Untuk orang tua yang datang langsung. Nomor pendaftaran dibuat otomatis.</p></div><a class="spmb-button secondary" href="<?php echo unit_e($spmbEntryBase); ?>">Tutup</a></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>"><input type="hidden" name="action" value="manual_spmb_registration">
        <label>Nama calon siswa *<input name="student_name" value="<?php echo unit_e($entryOld['student_name']); ?>" maxlength="150" required></label>
        <label>Nama orang tua *<input name="parent_name" value="<?php echo unit_e($entryOld['parent_name']); ?>" maxlength="150" required></label>
        <label>WhatsApp *<input type="tel" name="whatsapp" value="<?php echo unit_e($entryOld['whatsapp']); ?>" maxlength="30" required placeholder="08xxxxxxxxxx"></label>
        <label>Kelas tujuan *<select name="entry_class" required><option value="">Pilih kelas</option><?php foreach(spmb_entry_classes($scope) as $entryClass): ?><option <?php echo $entryOld['entry_class']===$entryClass?'selected':''; ?>><?php echo unit_e($entryClass); ?></option><?php endforeach; ?></select></label>
        <label>Tahun ajaran *<select name="academic_year" required><?php foreach(spmb_academic_years() as $year=>$track): ?><option value="<?php echo unit_e($year); ?>" <?php echo $entryOld['academic_year']===$year?'selected':''; ?>><?php echo unit_e($year . ' · ' . $track); ?></option><?php endforeach; ?></select></label>
        <label>NISN (opsional)<input name="nisn" value="<?php echo unit_e($entryOld['nisn']); ?>" inputmode="numeric" pattern="[0-9]{10}" maxlength="10"></label>
        <label>NIK siswa (opsional)<input name="student_nik" value="<?php echo unit_e($entryOld['student_nik']); ?>" maxlength="30"></label>
        <label>Asal sekolah (opsional)<input name="previous_school" value="<?php echo unit_e($entryOld['previous_school']); ?>" maxlength="150"></label>
        <label class="full">Alamat (opsional)<textarea name="address" rows="3"><?php echo unit_e($entryOld['address']); ?></textarea></label>
        <div class="full"><button type="submit">Simpan Pendaftar</button></div>
    </form>
</section>
<?php endif; ?>
<?php if($paymentEntry): ?>
<section class="panel spmb-entry" id="spmb-entry">
    <div class="spmb-section-head"><div><h2>Catat pembayaran</h2><p><?php echo unit_e($paymentEntry['student_name'] . ' · ' . $paymentEntry['registration_number']); ?></p></div><a class="spmb-button secondary" href="<?php echo unit_e($spmbEntryBase); ?>">Tutup</a></div>
    <div class="spmb-info">Pembayaran masuk antrean verifikasi. Cocokkan dengan kas atau mutasi rekening sebelum menyetujuinya.</div>
    <form method="post" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>"><input type="hidden" name="action" value="record_spmb_payment"><input type="hidden" name="id" value="<?php echo (int)$paymentEntry['id']; ?>">
        <label>Nominal (Rp) *<input type="number" name="amount" min="1" step="1" required value="<?php echo unit_e((string)($_POST['amount'] ?? '')); ?>" placeholder="500000"></label>
        <label>Jenis pembayaran<select name="payment_type"><?php foreach(['Pembayaran SPMB','Formulir Pendaftaran','Daftar Ulang','Uang Pangkal','Seragam','SPP','Lainnya'] as $type): ?><option <?php echo ($_POST['payment_type']??'')===$type?'selected':''; ?>><?php echo unit_e($type); ?></option><?php endforeach; ?></select></label>
        <label>Metode<select name="payment_method"><?php foreach(['Tunai','Transfer Bank','QRIS','Virtual Account'] as $method): ?><option <?php echo ($_POST['payment_method']??'')===$method?'selected':''; ?>><?php echo unit_e($method); ?></option><?php endforeach; ?></select></label>
        <label>Tanggal pembayaran *<input type="date" name="payment_date" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo unit_e((string)($_POST['payment_date'] ?? date('Y-m-d'))); ?>"></label>
        <label>Nama penyetor<input name="payer_name" maxlength="150" value="<?php echo unit_e((string)($_POST['payer_name'] ?? $paymentEntry['parent_name'])); ?>"></label>
        <label>Referensi transaksi (jika ada)<input name="reference_number" maxlength="100" value="<?php echo unit_e((string)($_POST['reference_number'] ?? '')); ?>"></label>
        <label class="full">Bukti gambar (opsional untuk petugas)<input type="file" name="proof" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, atau WebP. Maksimal 6 MB.</small></label>
        <div class="full"><button type="submit">Catat untuk Diverifikasi</button></div>
    </form>
</section>
<?php endif; ?>
