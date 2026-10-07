<section class="panel"><h2>Ruang Kerja <?php echo unit_e($allUnits[$scope]); ?></h2>
<p>Selamat datang, <strong><?php echo unit_e($admin['username']); ?></strong>. Pilih menu sesuai hak akses akun Anda.</p>
<div class="actions">
<?php foreach(unit_permission_labels() as $key=>$label): if($key==='dashboard'||!in_array($key,$permissions,true))continue; ?>
<a href="?tab=<?php echo unit_e($key); ?>&amp;unit=<?php echo unit_e($scope); ?>"><?php echo unit_e($label); ?></a>
<?php endforeach; ?></div>
<?php if($isSuper): ?><p><a href="<?php echo unit_e(SITE_URL.'/portal/users?scope=unit'); ?>">Kelola seluruh pengguna &amp; role di Portal Yayasan</a></p><?php endif; ?>
</section>
