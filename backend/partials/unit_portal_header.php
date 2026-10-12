<?php
$unitNav = ['dashboard'=>'Dashboard','content'=>'Konten','gallery'=>'Galeri','social'=>'Galeri Instagram','enrollments'=>'Pendaftar SPMB','careers'=>'Karir & Lamaran','settings'=>'Pengaturan Unit','users'=>'Manajemen Pengguna'];
$unitPageTitle = $unitNav[$tab] ?? 'Portal Unit';
$unitDisplayName = $allUnits[$scope] . ' Permata Hati';
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
<body class="unit-portal">
<div class="portal-layout">
    <aside class="portal-sidebar" id="portalSidebar" aria-label="Menu administrasi unit">
        <a class="sidebar-brand" href="?tab=dashboard&amp;unit=<?php echo unit_e($scope); ?>"><img src="<?php echo unit_e(unit_asset('assets/images/logo.png')); ?>" alt="Logo unit"><span>PortalPHB <small><?php echo unit_e($unitDisplayName); ?></small></span></a>
        <div class="sidebar-role"><small>Akses aktif</small><strong><?php echo unit_e($unitRoleName); ?></strong></div>
        <nav class="portal-nav">
            <?php foreach ($unitNav as $key=>$label): if (!in_array($key, $permissions, true)) continue; ?>
            <a class="<?php echo $tab === $key ? 'active' : ''; ?>" <?php if($tab === $key): ?>aria-current="page"<?php endif; ?> href="?tab=<?php echo $key; ?>&amp;unit=<?php echo unit_e($scope); ?>"><?php echo unit_e($label); ?></a>
            <?php endforeach; ?>
            <a class="unit-open-site" href="<?php echo unit_e(unit_portal_url($scope, 'index.php')); ?>" target="_blank" rel="noopener">Buka Website <span aria-hidden="true">↗</span></a>
        </nav>
        <div class="sidebar-logout"><a href="<?php echo unit_e(unit_portal_url(UNIT_SLUG, 'admin/index.php?logout=1')); ?>">Keluar dari portal</a></div>
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
