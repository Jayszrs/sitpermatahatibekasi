<div class="portal-welcome unit-welcome"><div><h2>Ruang Kerja <?php echo unit_e($allUnits[$scope]); ?></h2><p>Selamat datang, <?php echo unit_e($admin['username']); ?>. Kelola pekerjaan unit dari sini.</p></div><?php if(in_array('enrollments',$permissions,true)): ?><a class="spmb-button" href="?tab=enrollments&amp;unit=<?php echo unit_e($scope); ?>&amp;new_spmb=1#spmb-entry">+ Input Pendaftar</a><?php endif; ?></div>
<?php if(in_array('enrollments',$permissions,true)):
    $dashboardRows = unit_admin_spmb_rows($mainPdo,$scope);
    $dashboardToday = date('Y-m-d');
    $dashboardNew = count(array_filter($dashboardRows,static fn(array $r):bool=>substr((string)$r['created_at'],0,10)===$dashboardToday));
    $dashboardPaid = array_sum(array_map(static fn(array $r):float=>(float)$r['payment_amount'],$dashboardRows));
    $dashboardPendingRows = $mainPdo->query("SELECT r.level FROM spmb_payment_claims c JOIN spmb_registrations r ON r.id=c.registration_id WHERE c.status='pending'")->fetchAll();
    $dashboardPending = count(array_filter($dashboardPendingRows,static fn(array $r):bool=>school_unit_slug($r['level'])===$scope));
?>
<div class="spmb-metrics"><div><span>Total pendaftar</span><strong><?php echo count($dashboardRows); ?></strong><small>Seluruh kelas tujuan</small></div><div><span>Pendaftar hari ini</span><strong><?php echo $dashboardNew; ?></strong><small>Online dan manual</small></div><div><span>Bukti perlu diperiksa</span><strong><?php echo $dashboardPending; ?></strong><a href="?tab=enrollments&amp;unit=<?php echo unit_e($scope); ?>#spmb-bukti">Buka antrean</a></div><div><span>Total terverifikasi</span><strong>Rp <?php echo number_format($dashboardPaid,0,',','.'); ?></strong><small>Pembayaran unit ini</small></div></div>
<?php endif; ?>
<section class="panel"><div class="spmb-section-head"><div><h2>Menu kerja</h2><p>Pilih pekerjaan yang ingin dikelola.</p></div></div><div class="unit-quick-links">
<?php
$dashboardLinks = [
    'enrollments'=>['Pendaftar SPMB','Input manual, rekap siswa, dan pemeriksaan pembayaran.'],
    'content'=>['Konten Website','Kelola berita, program, kegiatan, prestasi, dan brosur.'],
    'gallery'=>['Galeri Foto','Atur album dan dokumentasi kegiatan unit.'],
    'social'=>['Galeri Instagram','Tambahkan publikasi dari Instagram unit.'],
    'careers'=>['Karir & Lamaran','Kelola lowongan dan tindak lanjut pelamar.'],
    'settings'=>['Pengaturan Unit','Perbarui identitas, kontak, dan media beranda.'],
    'users'=>['Manajemen Pengguna','Kelola akun, role, dan password sesuai hak akses.'],
];
foreach($dashboardLinks as $key=>$item): if(!in_array($key,$permissions,true)) continue;
?>
<a class="unit-quick-link" href="?tab=<?php echo $key; ?>&amp;unit=<?php echo unit_e($scope); ?>"><strong><?php echo unit_e($item[0]); ?><span aria-hidden="true">↗</span></strong><small><?php echo unit_e($item[1]); ?></small></a>
<?php endforeach; ?></div></section>
<?php if($isSuper): ?><p class="unit-foundation-link"><a href="<?php echo unit_e(SITE_URL.'/portal/users?scope=unit'); ?>">Kelola seluruh pengguna di Portal Yayasan</a></p><?php endif; ?>
