<?php
$portalUser = portal_user();
$portalTitle = $portalTitle ?? 'Dashboard';
$portalActive = $portalActive ?? 'dashboard';
$portalNav = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => '/portal/dashboard', 'roles' => ['admin', 'humas', 'kasir']],
    ['key' => 'content', 'label' => 'Berita & Galeri', 'url' => '/portal/content', 'roles' => ['admin', 'humas']],
    ['key' => 'site-unit', 'label' => 'Unit Sekolah', 'url' => '/portal/site-content?type=unit', 'roles' => ['admin', 'humas']],
    ['key' => 'site-achievement', 'label' => 'Prestasi', 'url' => '/portal/site-content?type=achievement', 'roles' => ['admin', 'humas']],
    ['key' => 'site-leadership', 'label' => 'Struktur Pimpinan', 'url' => '/portal/site-content?type=leadership', 'roles' => ['admin', 'humas']],
    ['key' => 'site-program', 'label' => 'Program Unggulan', 'url' => '/portal/site-content?type=program', 'roles' => ['admin', 'humas']],
    ['key' => 'site-activity', 'label' => 'Kegiatan Sekolah', 'url' => '/portal/site-content?type=activity', 'roles' => ['admin', 'humas']],
    ['key' => 'site-profile', 'label' => 'Profil, Visi & Misi', 'url' => '/portal/site-content?type=profile', 'roles' => ['admin', 'humas']],
    ['key' => 'careers', 'label' => 'Karir & Lamaran', 'url' => '/portal/careers', 'roles' => ['admin']],
    ['key' => 'payments', 'label' => 'Pembayaran SPMB', 'url' => '/portal/payments', 'roles' => ['admin', 'kasir']],
    ['key' => 'users', 'label' => 'Manajemen Pengguna', 'url' => '/portal/users', 'roles' => ['admin']],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/jpeg" href="<?php echo esc(asset_url('frontend/assets/images/logo-sit-permata-hati.jpeg')); ?>">
    <title><?php echo esc($portalTitle); ?> | Portal PHB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo esc(asset_url('frontend/assets/css/portal.css')); ?>">
</head>
<body>
<div class="portal-layout">
    <aside class="portal-sidebar" id="portalSidebar">
        <a class="sidebar-brand" href="<?php echo SITE_URL; ?>/portal/dashboard">
            <img src="<?php echo SITE_URL; ?>/frontend/assets/images/logo-sit-permata-hati.jpeg" alt="Logo SIT Permata Hati Bekasi">
            <span>PortalPHB</span>
        </a>
        <div class="sidebar-role">
            <small>Akses aktif</small>
            <strong><?php echo esc(ucfirst($portalUser['role'])); ?></strong>
        </div>
        <nav class="portal-nav">
            <?php foreach ($portalNav as $item): ?>
                <?php if (in_array($portalUser['role'], $item['roles'], true)): ?>
                    <a class="<?php echo $portalActive === $item['key'] ? 'active' : ''; ?>" href="<?php echo SITE_URL . $item['url']; ?>"><?php echo esc($item['label']); ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-logout"><a href="<?php echo SITE_URL; ?>/portal/logout">Keluar dari portal</a></div>
    </aside>
    <main class="portal-main">
        <header class="portal-topbar">
            <button class="sidebar-toggle" type="button" id="sidebarToggle">&#9776;</button>
            <h1><?php echo esc($portalTitle); ?></h1>
            <div class="topbar-user">
                <strong><?php echo esc($portalUser['name']); ?></strong>
                <small><?php echo esc($portalUser['role']); ?></small>
            </div>
        </header>
        <div class="portal-content">
            <?php if ($flash = portal_get_flash()): ?>
                <div class="portal-alert <?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?>"><?php echo esc($flash['message']); ?></div>
            <?php endif; ?>
