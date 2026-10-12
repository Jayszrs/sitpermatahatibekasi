<?php
$unitNav = ['dashboard'=>'Dashboard','content'=>'Konten','gallery'=>'Galeri','social'=>'Galeri Instagram','enrollments'=>'Pendaftar SPMB','careers'=>'Karir & Lamaran','settings'=>'Pengaturan Unit','users'=>'Manajemen Pengguna'];
$unitPageTitle = $unitNav[$tab] ?? 'Portal Unit';
$unitDisplayName = $allUnits[$scope] . ' Permata Hati';
$unitNavIcons = [
    'dashboard'=>'<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'content'=>'<path d="M5 3h10l4 4v14H5zM14 3v5h5M8 12h8M8 16h6"/>',
    'gallery'=>'<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1.5"/><path d="m3 17 5-5 4 4 3-3 6 6"/>',
    'social'=>'<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
    'enrollments'=>'<path d="M9 4H5v17h14V4h-4M9 3h6v4H9zM8 12h8M8 16h5"/>',
    'careers'=>'<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V3h8v4M3 12h18M10 12v3h4v-3"/>',
    'settings'=>'<path d="M4 6h16M4 12h16M4 18h16M8 3v6M16 9v6M10 15v6"/>',
    'users'=>'<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M21 21v-3a5 5 0 0 0-4-5"/>',
];
$unitRoleName = 'Superadmin Semua Unit';
if (!$isSuper) {
    $unitRoleQuery = $pdo->prepare('SELECT name FROM unit_roles WHERE role_key=?');
    $unitRoleQuery->execute([$admin['role_key'] ?: 'unit_admin']);
    $unitRoleName = $unitRoleQuery->fetchColumn() ?: 'Petugas Unit';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo unit_e($unitPageTitle . ' | ' . $unitDisplayName); ?></title>
    <link rel="icon" href="<?php echo unit_e(unit_asset('assets/images/logo.png')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo unit_e(asset_url('frontend/assets/css/portal.css')); ?>">
    <link rel="stylesheet" href="<?php echo unit_e(asset_url('frontend/assets/css/unit-portal.css')); ?>">
</head>
<body class="unit-portal" data-unit="<?php echo unit_e($scope); ?>">
<div class="portal-layout">
    <aside class="portal-sidebar" id="portalSidebar" aria-label="Menu administrasi unit">
        <a class="sidebar-brand" href="?tab=dashboard&amp;unit=<?php echo unit_e($scope); ?>"><img src="<?php echo unit_e(unit_asset('assets/images/logo.png')); ?>" alt="Logo unit"><span>Portal Unit <small><?php echo unit_e($unitDisplayName); ?></small></span></a>
        <div class="sidebar-role"><small>Akses aktif</small><strong><?php echo unit_e($unitRoleName); ?></strong></div>
        <nav class="portal-nav">
            <?php foreach ($unitNav as $key=>$label): if (!in_array($key, $permissions, true)) continue; ?>
            <a class="<?php echo $tab === $key ? 'active' : ''; ?>" <?php if($tab === $key): ?>aria-current="page"<?php endif; ?> href="?tab=<?php echo $key; ?>&amp;unit=<?php echo unit_e($scope); ?>"><svg class="unit-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><?php echo $unitNavIcons[$key]; ?></svg><span><?php echo unit_e($label); ?></span></a>
            <?php endforeach; ?>
        </nav>
        <?php if(in_array('enrollments',$permissions,true)): ?>
        <p class="unit-nav-group-title">Aksi SPMB</p>
        <nav class="portal-nav unit-sidebar-shortcuts" aria-label="Aksi cepat SPMB">
            <a href="?tab=enrollments&amp;unit=<?php echo unit_e($scope); ?>&amp;new_spmb=1#spmb-entry"><svg class="unit-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0M19 12v8M15 16h8"/></svg><span>Input Pendaftar</span></a>
            <a href="?tab=enrollments&amp;unit=<?php echo unit_e($scope); ?>#spmb-bukti"><svg class="unit-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.9 8.3 7 10 4.1-1.7 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg><span>Verifikasi Pembayaran</span></a>
            <a href="?tab=enrollments&amp;unit=<?php echo unit_e($scope); ?>#spmb-rekap"><svg class="unit-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21h17M7 17v-5M12 17V7M17 17V3"/></svg><span>Rekap &amp; Excel</span></a>
        </nav>
        <?php endif; ?>
        <a class="unit-open-site" href="<?php echo unit_e(unit_portal_url($scope, 'index.php')); ?>" target="_blank" rel="noopener"><svg class="unit-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3h7v7M21 3l-9 9M10 3H3v18h18v-7"/></svg><span>Buka Website</span></a>
        <div class="sidebar-logout"><a href="<?php echo unit_e(unit_portal_url(UNIT_SLUG, 'admin/index.php?logout=1')); ?>"><svg class="unit-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3H3v18h6M9 12h12m-4-4 4 4-4 4"/></svg><span>Keluar dari portal</span></a></div>
    </aside>
    <button class="unit-sidebar-backdrop" type="button" aria-label="Tutup menu" tabindex="-1" hidden></button>
    <main class="portal-main">
        <header class="portal-topbar">
            <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="Buka menu" aria-controls="portalSidebar" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
            <h1><?php echo unit_e($unitPageTitle); ?></h1>
            <div class="topbar-user"><strong><?php echo unit_e($admin['username']); ?></strong><small><?php echo unit_e($allUnits[$scope]); ?></small></div>
        </header>
        <div class="portal-content unit-content">
            <?php if ($isSuper): ?><form class="panel unit-scope-picker" method="get"><input type="hidden" name="tab" value="<?php echo unit_e($tab); ?>"><label>Pilih unit yang dikelola<select name="unit" onchange="this.form.submit()"><?php foreach($allUnits as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo $scope === $key ? 'selected' : ''; ?>><?php echo unit_e($label); ?></option><?php endforeach; ?></select></label></form><?php endif; ?>
            <?php if($notice): ?><div class="portal-alert success" role="status"><?php echo unit_e($notice); ?></div><?php endif; ?>
            <?php if($error): ?><div class="portal-alert danger" role="alert"><?php echo unit_e($error); ?></div><?php endif; ?>
