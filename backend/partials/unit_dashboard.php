<section class="panel"><h2>Ruang Kerja <?php echo unit_e($allUnits[$scope]); ?></h2>
<p>Selamat datang, <strong><?php echo unit_e($admin['username']); ?></strong>. Pilih menu sesuai hak akses akun Anda.</p>
<?php if(in_array('enrollments',$permissions,true)):
    $dashboardRows=unit_admin_spmb_rows($mainPdo,$scope);
    $dashboardToday=date('Y-m-d');
    $dashboardNew=count(array_filter($dashboardRows,static fn(array $r):bool=>substr((string)$r['created_at'],0,10)===$dashboardToday));
    $dashboardPaid=array_sum(array_map(static fn(array $r):float=>(float)$r['payment_amount'],$dashboardRows));
    $dashboardPendingRows=$mainPdo->query("SELECT r.level FROM spmb_payment_claims c JOIN spmb_registrations r ON r.id=c.registration_id WHERE c.status='pending'")->fetchAll();
    $dashboardPending=count(array_filter($dashboardPendingRows,static fn(array $r):bool=>school_unit_slug($r['level'])===$scope));
?><style>.spmb-dashboard-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:17px 0}.spmb-dashboard-grid div{padding:17px;background:#eaf7ee;border-radius:12px}.spmb-dashboard-grid span{display:block;font-size:.75rem;color:#53715e}.spmb-dashboard-grid strong{display:block;font-size:1.5rem;color:#086347;margin-top:5px}@media(max-width:700px){.spmb-dashboard-grid{grid-template-columns:1fr}}</style><div class="spmb-dashboard-grid"><div><span>Pendaftar hari ini</span><strong><?php echo $dashboardNew; ?></strong></div><div><span>Bukti menunggu pemeriksaan</span><strong><?php echo $dashboardPending; ?></strong></div><div><span>Total pembayaran terverifikasi</span><strong>Rp <?php echo number_format($dashboardPaid,0,',','.'); ?></strong></div></div><p><a href="?tab=enrollments&amp;unit=<?php echo unit_e($scope); ?>">Buka rekap dan antrean pembayaran SPMB</a></p><?php endif; ?>
<div class="actions">
<?php foreach(unit_permission_labels() as $key=>$label): if($key==='dashboard'||!in_array($key,$permissions,true))continue; ?>
<a href="?tab=<?php echo unit_e($key); ?>&amp;unit=<?php echo unit_e($scope); ?>"><?php echo unit_e($label); ?></a>
<?php endforeach; ?><?php if(in_array('users',$permissions,true)): ?><a href="?tab=users&amp;unit=<?php echo unit_e($scope); ?>">Manajemen Pengguna</a><?php endif; ?></div>
<?php if($isSuper): ?><p><a href="<?php echo unit_e(SITE_URL.'/portal/users?scope=unit'); ?>">Kelola seluruh pengguna &amp; role di Portal Yayasan</a></p><?php endif; ?>
</section>
