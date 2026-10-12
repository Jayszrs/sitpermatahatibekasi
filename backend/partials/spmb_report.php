<?php
try { $filters = spmb_report_filters($scope, $_GET); }
catch (RuntimeException $filterError) {
    $filters = spmb_report_filters($scope, []);
    ?><div class="portal-alert danger"><?php echo unit_e($filterError->getMessage()); ?></div><?php
}
$allRows = unit_admin_spmb_rows($mainPdo, $scope);
$rows = spmb_report_rows($mainPdo, $pdo, $scope, $filters);
$counts = spmb_report_counts($rows, $scope);
$years = array_values(array_unique(array_filter(array_column($allRows, 'academic_year'))));
rsort($years);
$statusLabels = ['baru'=>'Baru','verifikasi'=>'Verifikasi','lulus'=>'Lulus','cadangan'=>'Cadangan','ditolak'=>'Ditolak','daftar_ulang'=>'Daftar Ulang'];
$paymentLabels = ['belum_bayar'=>'Belum Bayar','sebagian'=>'Sebagian','lunas'=>'Lunas'];
$claimLabels = ['pending'=>'Menunggu','verified'=>'Terverifikasi','rejected'=>'Ditolak','voided'=>'Dibatalkan'];
$claimQuery = $mainPdo->query("SELECT c.*,r.level,r.student_name,r.registration_number FROM spmb_payment_claims c JOIN spmb_registrations r ON r.id=c.registration_id ORDER BY c.created_at DESC,c.id DESC");
$unitClaims = array_values(array_filter($claimQuery->fetchAll(), static fn(array $c): bool => school_unit_slug($c['level']) === $scope));
$pendingClaims = array_values(array_filter($unitClaims, static fn(array $c): bool => $c['status'] === 'pending'));
$history = array_values(array_filter($unitClaims, static fn(array $c): bool => $c['status'] !== 'pending'));
usort($history, static fn(array $a, array $b): int => strcmp((string)$b['reviewed_at'], (string)$a['reviewed_at']));
$history = array_slice($history, 0, 30);
$today = date('Y-m-d');
$todayRegistrations = count(array_filter($allRows, static fn(array $r): bool => substr((string)$r['created_at'], 0, 10) === $today));
$unitPaid = array_sum(array_map(static fn(array $r): float => (float)$r['payment_amount'], $allRows));
$verifiedToday = array_sum(array_map(static fn(array $c): float => $c['status'] === 'verified' && substr((string)$c['reviewed_at'],0,10) === $today ? (float)$c['claimed_amount'] : 0.0, $unitClaims));
$baseQuery = ['tab'=>'enrollments','unit'=>$scope];
$baseUrl = '?' . http_build_query($baseQuery);
$exportUrl = '?' . http_build_query($baseQuery + $filters + ['export_spmb'=>1]);
$sharePaymentUrl = null;
if (isset($_GET['payment_link_spmb'])) {
    try {
        $shareRegistration = spmb_unit_registration($mainPdo, (int)$_GET['payment_link_spmb'], $scope);
        $sharePaymentUrl = spmb_payment_url(spmb_payment_link_token($mainPdo, (int)$shareRegistration['id']));
    } catch (RuntimeException $shareError) { ?><div class="portal-alert danger"><?php echo unit_e($shareError->getMessage()); ?></div><?php }
}
?>
<div class="spmb-page-head">
    <div><span class="spmb-eyebrow">PENERIMAAN SISWA BARU</span><h2>SPMB <?php echo unit_e($allUnits[$scope]); ?></h2><p>Kelola pendaftar, bukti pembayaran, dan rekap dari satu halaman.</p></div>
    <div class="spmb-toolbar"><a class="spmb-button secondary" href="<?php echo unit_e($exportUrl); ?>">Unduh Excel</a><a class="spmb-button" href="<?php echo unit_e($baseUrl . '&new_spmb=1#spmb-entry'); ?>">+ Input Pendaftar</a></div>
</div>
<div class="spmb-metrics">
    <div><span>Pendaftar hari ini</span><strong><?php echo $todayRegistrations; ?></strong><small>Online dan manual</small></div>
    <div><span>Bukti perlu diperiksa</span><strong><?php echo count($pendingClaims); ?></strong><a href="#spmb-bukti">Buka antrean</a></div>
    <div><span>Terverifikasi hari ini</span><strong>Rp <?php echo number_format($verifiedToday,0,',','.'); ?></strong><small>Setelah pemeriksaan petugas</small></div>
    <div><span>Total terbayar unit</span><strong>Rp <?php echo number_format($unitPaid,0,',','.'); ?></strong><small>Akumulasi terverifikasi</small></div>
</div>
<nav class="spmb-section-nav" aria-label="Bagian SPMB"><a href="#spmb-rekap">Rekap pendaftar <span><?php echo count($rows); ?></span></a><a href="#spmb-bukti">Bukti pembayaran <span><?php echo count($pendingClaims); ?></span></a><a href="#spmb-riwayat">Riwayat pemeriksaan</a></nav>
<?php require __DIR__ . '/unit_spmb_entry.php'; ?>
<?php if($sharePaymentUrl): ?>
<section class="panel spmb-share" id="spmb-share"><div class="spmb-section-head"><div><h2>Tautan bukti pembayaran</h2><p><?php echo unit_e($shareRegistration['student_name']); ?> · Bagikan kepada orang tua pendaftar ini.</p></div><a href="<?php echo unit_e($baseUrl); ?>">Tutup</a></div><div class="spmb-copy-row"><input id="spmbPaymentLink" readonly value="<?php echo unit_e($sharePaymentUrl); ?>" aria-label="Tautan pembayaran"><button type="button" data-copy-target="spmbPaymentLink">Salin Tautan</button><a class="spmb-button secondary" href="<?php echo unit_e($sharePaymentUrl); ?>" target="_blank" rel="noopener noreferrer">Buka</a></div><p class="spmb-copy-feedback" role="status" aria-live="polite"></p></section>
<?php endif; ?>
<section class="panel" id="spmb-rekap">
    <div class="spmb-section-head"><div><h2>Rekap pendaftar</h2><p><?php echo count($rows); ?> pendaftar sesuai filter, dari semua kelas tujuan.</p></div><a class="spmb-button secondary" href="<?php echo unit_e($exportUrl); ?>">Unduh Excel (.xlsx)</a></div>
    <form class="spmb-filters" method="get"><input type="hidden" name="tab" value="enrollments"><input type="hidden" name="unit" value="<?php echo unit_e($scope); ?>">
        <label>Cari pendaftar<input name="q" maxlength="100" value="<?php echo unit_e($filters['q']); ?>" placeholder="Nama, kode, orang tua, atau WA"></label>
        <label>Tahun ajaran<select name="year"><option value="">Semua tahun</option><?php foreach($years as $year): ?><option value="<?php echo unit_e($year); ?>" <?php echo $filters['year']===$year?'selected':''; ?>><?php echo unit_e($year); ?></option><?php endforeach; ?></select></label>
        <label>Kelas tujuan<select name="class"><option value="">Semua kelas</option><?php foreach(spmb_entry_classes($scope) as $class): ?><option <?php echo $filters['class']===$class?'selected':''; ?>><?php echo unit_e($class); ?></option><?php endforeach; ?><option value="Belum ditentukan" <?php echo $filters['class']==='Belum ditentukan'?'selected':''; ?>>Belum ditentukan</option></select></label>
        <label>Status bayar<select name="payment"><option value="">Semua status</option><?php foreach($paymentLabels as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo $filters['payment']===$key?'selected':''; ?>><?php echo unit_e($label); ?></option><?php endforeach; ?></select></label>
        <div class="spmb-filter-actions"><button type="submit">Tampilkan</button><a class="spmb-button secondary" href="<?php echo unit_e($baseUrl); ?>">Reset</a></div>
    </form>
    <div class="spmb-class-counts"><?php foreach($counts as $class=>$count): ?><span><?php echo unit_e($class); ?><strong><?php echo $count; ?></strong></span><?php endforeach; ?></div>
    <?php if($rows): ?>
    <p class="spmb-scroll-hint">Geser tabel ke samping untuk melihat seluruh kolom dan aksi.</p>
    <div class="spmb-table-wrap" tabindex="0" role="region" aria-label="Rekap pendaftar SPMB"><table class="spmb-recap"><thead><tr><th>No</th><th>Calon siswa</th><th>Kelas / Tahun</th><th>Orang tua</th><th>Status SPMB</th><th>Tagihan / Terbayar</th><th>Status bayar</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($rows as $index=>$row): $legacy = !empty($row['source']); $waUrl = spmb_whatsapp_url($row['whatsapp'], 'Halo, kami tim SPMB ' . $allUnits[$scope] . ' Permata Hati. Kami ingin menindaklanjuti pendaftaran ' . $row['student_name'] . ' (' . $row['registration_number'] . ').'); ?>
        <tr><td><?php echo $index+1; ?></td><td><strong><?php echo unit_e($row['student_name']); ?></strong><small><?php echo unit_e($row['registration_number'] ?: '-'); ?></small><span class="spmb-source"><?php echo $legacy ? 'Data lama' : (($row['registration_source'] ?? 'online') === 'manual' ? 'Manual' : 'Online'); ?></span></td><td><?php echo unit_e($row['entry_class'] ?: 'Belum ditentukan'); ?><small><?php echo unit_e($row['academic_year']); ?><?php echo $row['admission_track']==='waiting_list'?' · Waiting List':''; ?></small></td><td><?php echo unit_e($row['parent_name']); ?><small><?php echo unit_e($row['whatsapp']); ?></small></td><td><?php echo unit_e($statusLabels[$row['registration_status']] ?? $row['registration_status']); ?></td><td><small><?php echo (float)($row['total_due']??0)>0 ? 'Tagihan Rp '.number_format((float)$row['total_due'],0,',','.') : 'Tagihan belum ditetapkan'; ?></small><strong>Rp <?php echo number_format((float)($row['payment_amount']??0),0,',','.'); ?></strong></td><td><span class="spmb-badge <?php echo unit_e($row['payment_status']??'belum_bayar'); ?>"><?php echo unit_e($paymentLabels[$row['payment_status']??'belum_bayar']); ?></span></td><td><details class="spmb-row-actions"><summary>Aksi</summary><div>
            <?php if(!$legacy): ?><a class="spmb-button secondary" href="<?php echo unit_e($baseUrl . '&record_payment_spmb=' . (int)$row['id'] . '#spmb-entry'); ?>">Catat Pembayaran</a><a class="spmb-button secondary" href="<?php echo unit_e($baseUrl . '&payment_link_spmb=' . (int)$row['id'] . '#spmb-share'); ?>">Tautan Bukti</a><?php endif; ?>
            <?php if($waUrl): ?><a class="spmb-button secondary" href="<?php echo unit_e($waUrl); ?>" target="_blank" rel="noopener noreferrer">WhatsApp Orang Tua</a><?php endif; ?>
            <?php if(!$legacy): ?><form method="post"><input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>"><input type="hidden" name="action" value="update_spmb_status"><input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>"><label>Status pendaftaran<select name="status"><?php foreach($statusLabels as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo $row['registration_status']===$key?'selected':''; ?>><?php echo unit_e($label); ?></option><?php endforeach; ?></select></label><button>Simpan Status</button></form><?php endif; ?>
        </div></details></td></tr>
    <?php endforeach; ?></tbody></table></div>
    <?php else: ?><div class="spmb-empty"><strong>Belum ada pendaftar sesuai filter</strong><p>Ubah filter atau masukkan pendaftar yang datang langsung ke sekolah.</p><a class="spmb-button" href="<?php echo unit_e($baseUrl . '&new_spmb=1#spmb-entry'); ?>">+ Input Pendaftar</a></div><?php endif; ?>
</section>
<section class="panel" id="spmb-bukti">
    <div class="spmb-section-head"><div><h2>Bukti pembayaran</h2><p><?php echo count($pendingClaims); ?> bukti menunggu pemeriksaan.</p></div></div>
    <?php if($pendingClaims): ?><div class="spmb-info">Cocokkan nama, tanggal, nominal, dan referensi dengan mutasi rekening atau kas sebelum menyetujui.</div><?php endif; ?>
    <?php foreach($pendingClaims as $claim): ?>
    <article class="spmb-proof-card"><div class="spmb-section-head"><div><h3><?php echo unit_e($claim['student_name']); ?></h3><p><?php echo unit_e($claim['registration_number'] . ' · ' . $claim['payment_date'] . ' · ' . $claim['payment_method']); ?></p><p>Penyetor: <?php echo unit_e($claim['payer_name'] ?: '-'); ?></p></div><div class="spmb-proof-amount"><strong>Rp <?php echo number_format((float)$claim['claimed_amount'],0,',','.'); ?></strong><?php if($claim['proof_path']): ?><a class="spmb-button secondary" href="<?php echo unit_e($baseUrl . '&proof_spmb=' . (int)$claim['id']); ?>" target="_blank" rel="noopener">Lihat Bukti</a><?php endif; ?></div></div>
        <form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>"><input type="hidden" name="action" value="review_spmb_payment"><input type="hidden" name="claim_id" value="<?php echo (int)$claim['id']; ?>"><label>Nominal cocok (Rp)<input type="number" name="confirmed_amount" min="1" step="1" value="<?php echo (int)$claim['claimed_amount']; ?>"></label><label>Referensi mutasi / transaksi<input name="bank_reference" maxlength="100" value="<?php echo unit_e($claim['reference_number']); ?>" placeholder="Wajib untuk transfer"></label><label class="full">Catatan pemeriksaan<input name="review_notes" placeholder="Alasan wajib jika bukti ditolak"></label><div class="full actions"><button name="decision" value="verified">Setujui &amp; Akumulasikan</button><button class="reject" name="decision" value="rejected">Tolak Bukti</button></div></form>
    </article>
    <?php endforeach; ?>
    <?php if(!$pendingClaims): ?><div class="spmb-empty compact"><strong>Tidak ada bukti yang menunggu</strong><p>Bukti baru akan muncul di sini setelah dikirim orang tua atau dicatat petugas.</p></div><?php endif; ?>
</section>
<section class="panel" id="spmb-riwayat"><div class="spmb-section-head"><div><h2>Riwayat pemeriksaan</h2><p>30 transaksi terakhir yang sudah diperiksa.</p></div></div>
    <?php if($history): ?><div class="spmb-table-wrap" tabindex="0" role="region" aria-label="Riwayat pembayaran"><table><thead><tr><th>Pendaftar</th><th>Nominal</th><th>Status</th><th>Pemeriksaan</th><th>Aksi</th></tr></thead><tbody><?php foreach($history as $claim): ?><tr><td><strong><?php echo unit_e($claim['student_name']); ?></strong><small><?php echo unit_e($claim['registration_number']); ?></small></td><td>Rp <?php echo number_format((float)$claim['claimed_amount'],0,',','.'); ?><small><?php echo unit_e($claim['verified_reference'] ?: $claim['reference_number'] ?: '-'); ?></small></td><td><span class="spmb-badge <?php echo unit_e($claim['status']); ?>"><?php echo unit_e($claimLabels[$claim['status']]); ?></span></td><td><?php echo unit_e($claim['reviewed_at']); ?><small><?php echo unit_e($claim['review_notes'] ?: '-'); ?></small></td><td><?php if($claim['proof_path']): ?><a href="<?php echo unit_e($baseUrl . '&proof_spmb=' . (int)$claim['id']); ?>" target="_blank" rel="noopener">Lihat Bukti</a><?php endif; ?><?php if($claim['status']==='verified'): ?><details><summary>Batalkan verifikasi</summary><form method="post"><input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>"><input type="hidden" name="action" value="void_spmb_payment"><input type="hidden" name="claim_id" value="<?php echo (int)$claim['id']; ?>"><label>Alasan pembatalan<input name="reason" required></label><button class="danger">Batalkan Verifikasi</button></form></details><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
    <?php else: ?><div class="spmb-empty compact"><strong>Belum ada riwayat pemeriksaan</strong><p>Hasil persetujuan dan penolakan pembayaran akan tercatat di sini.</p></div><?php endif; ?>
</section>
