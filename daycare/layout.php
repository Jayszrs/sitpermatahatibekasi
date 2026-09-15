<?php

function unit_social_profiles(): array {
    global $unit_config;
    $contact = $unit_config['contact'] ?? [];
    $platforms = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
    ];
    $links = [];
    foreach ($platforms as $key => $label) {
        if (!empty($contact[$key])) {
            $links[$key] = ['label' => $label, 'url' => $contact[$key]];
        }
    }
    return $links;
}

function unit_social_icon(string $platform): void {
    if ($platform === 'facebook') {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8.2V6.9c0-.7.3-1.1 1.2-1.1h1.4V3.2c-.7-.1-1.5-.2-2.2-.2-2.2 0-3.8 1.4-3.8 3.9v1.3H8.1V11h2.5v7.8H14V11h2.4l.4-2.8H14Z"></path></svg>';
    } elseif ($platform === 'tiktok') {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 3c.4 2.5 1.8 4 4.3 4.2v3.1c-1.6 0-3-.5-4.2-1.4v5.9c0 3-2.1 5.2-5.2 5.2-2.8 0-5-2-5-4.7 0-3 2.5-5.1 5.7-4.7v3.2c-1.3-.4-2.4.2-2.4 1.4 0 .9.7 1.6 1.7 1.6 1.2 0 1.9-.8 1.9-2.1V3h3.2Z"></path></svg>';
    } elseif ($platform === 'youtube') {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22.5 12s0-3.2-.4-4.7a2.8 2.8 0 0 0-2-2C18.5 5 12 5 12 5s-6.5 0-8.1.3a2.8 2.8 0 0 0-2 2C1.5 8.8 1.5 12 1.5 12s0 3.2.4 4.7a2.8 2.8 0 0 0 2 2C5.5 19 12 19 12 19s6.5 0 8.1-.3a2.8 2.8 0 0 0 2-2c.4-1.5.4-4.7.4-4.7Z"></path><path d="m10 15 5-3-5-3v6Z"></path></svg>';
    } else {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><path d="M17.5 6.5h.01"></path></svg>';
    }
}

function unit_social_anchor(string $platform, array $social, bool $showLabel = true): void {
    ?>
    <a class="unit-social-link social-<?php echo unit_e($platform); ?>" href="<?php echo unit_e($social['url']); ?>" target="_blank" rel="noopener" title="<?php echo unit_e($social['label']); ?>">
        <?php unit_social_icon($platform); ?>
        <?php if ($showLabel): ?><span><?php echo unit_e($social['label']); ?></span><?php else: ?><span class="sr-only"><?php echo unit_e($social['label']); ?></span><?php endif; ?>
    </a>
    <?php
}

function unit_page_start(string $title, string $active = 'home'): void {
    global $unit_config, $pdo;
    $settings = unit_settings($pdo);
    $theme = $unit_config['theme'];
    $profileActive = in_array($active, ['profile', 'programs', 'achievements'], true);
    $infoActive = in_array($active, ['activities', 'news', 'gallery', 'brochures', 'karir'], true);
    $socialLinks = unit_social_profiles();
    ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo unit_e($title . ' - ' . $settings['name']); ?></title><meta name="description" content="<?php echo unit_e($settings['description']); ?>"><link rel="icon" type="image/png" href="<?php echo unit_asset('assets/images/logo.png'); ?>"><link rel="stylesheet" href="<?php echo unit_asset('assets/css/style.css'); ?>"><link rel="stylesheet" href="<?php echo unit_asset('assets/css/foundation-refresh.css'); ?>"><style>:root{--primary:<?php echo unit_e($theme['primary']); ?>;--primary-dark:<?php echo unit_e($theme['primary_dark']); ?>;--accent:<?php echo unit_e($theme['accent']); ?>;--soft:<?php echo unit_e($theme['soft']); ?>;--ink:<?php echo unit_e($theme['ink']); ?>;--logo-hue:<?php echo unit_e($theme['logo_hue'] ?? '0deg'); ?>}</style></head><body>

<header class="site-header"><div class="shell nav-shell"><a class="brand" href="index.php"><span class="brand-mark-wrap"><img class="brand-mark" src="<?php echo unit_asset('assets/images/logo.png'); ?>" alt="Logo <?php echo unit_e($settings['name']); ?>"></span><span><strong><?php echo unit_e($settings['name']); ?></strong><small>SIT Permata Hati Bekasi</small></span></a><button class="menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false" data-menu-toggle><span></span><span></span><span></span></button><nav class="main-nav" data-main-nav><a class="<?php echo $active==='home'?'active':''; ?>" href="index.php">Beranda</a><div class="nav-dropdown"><button type="button" class="nav-dropdown-trigger <?php echo $profileActive?'active':''; ?>" aria-expanded="false" data-nav-dropdown>Tentang Unit <svg viewBox="0 0 10 6" aria-hidden="true"><path d="m1 1 4 4 4-4"/></svg></button><div class="nav-dropdown-menu"><a class="<?php echo $active==='profile'?'active':''; ?>" href="profile.php">Profil Unit</a><a class="<?php echo $active==='programs'?'active':''; ?>" href="programs.php">Program</a><a class="<?php echo $active==='achievements'?'active':''; ?>" href="achievements.php">Prestasi</a></div></div><div class="nav-dropdown unit-info-dropdown"><button type="button" class="nav-dropdown-trigger <?php echo $infoActive?'active':''; ?>" aria-expanded="false" data-nav-dropdown>Informasi <svg viewBox="0 0 10 6" aria-hidden="true"><path d="m1 1 4 4 4-4"/></svg></button><div class="nav-dropdown-menu"><a class="<?php echo $active==='activities'?'active':''; ?>" href="activities.php">Kegiatan</a><a class="<?php echo $active==='news'?'active':''; ?>" href="news.php">Berita</a><a class="<?php echo $active==='gallery'?'active':''; ?>" href="gallery.php">Galeri</a><a class="<?php echo $active==='brochures'?'active':''; ?>" href="brochures.php">Brosur</a><a class="<?php echo $active==='karir'?'active':''; ?>" href="karir.php">Karir</a></div></div><a class="nav-cta <?php echo $active==='spmb'?'active':''; ?>" href="spmb.php">SPMB</a><a class="<?php echo $active==='contact'?'active':''; ?>" href="contact.php">Kontak</a><?php if ($socialLinks): ?><div class="nav-dropdown unit-social-dropdown"><button type="button" class="nav-dropdown-trigger unit-social-trigger" aria-expanded="false" data-nav-dropdown>Sosial Media <svg viewBox="0 0 10 6" aria-hidden="true"><path d="m1 1 4 4 4-4"/></svg></button><div class="nav-dropdown-menu unit-social-menu"><?php foreach ($socialLinks as $platform => $social): ?><?php unit_social_anchor($platform, $social); ?><?php endforeach; ?></div></div><?php endif; ?></nav></div></header>
<main>
<?php }

function unit_page_end(): void { global $pdo, $unit_config; $settings=unit_settings($pdo); $socialLinks = unit_social_profiles(); ?>
</main><footer class="site-footer unit-site-footer" style="--footer-photo:url('<?php echo unit_e(unit_media($unit_config['hero_image'])); ?>')"><div class="shell footer-grid unit-footer-grid"><section class="unit-footer-brand"><a class="brand footer-brand" href="index.php"><span class="brand-mark-wrap"><img class="brand-mark" src="<?php echo unit_asset('assets/images/logo.png'); ?>" alt="Logo <?php echo unit_e($settings['name']); ?>"></span><span><strong><?php echo unit_e($settings['name']); ?></strong><small>SIT Permata Hati Bekasi</small></span></a><p><?php echo unit_e($settings['tagline']); ?></p><a class="back-foundation" href="../index.php">Kembali ke Website Yayasan</a></section><section class="unit-footer-nav"><h2>Jelajahi</h2><a href="profile.php">Profil Unit</a><a href="programs.php">Program</a><a href="achievements.php">Prestasi</a><a href="activities.php">Kegiatan</a><a href="news.php">Berita</a><a href="gallery.php">Galeri</a><a href="karir.php">Karir</a></section><section class="unit-footer-contact"><h2>Kontak</h2><a href="tel:<?php echo unit_e(preg_replace('/[^0-9+]/','',$settings['phone'])); ?>"><?php echo unit_e($settings['phone']); ?></a><a href="mailto:<?php echo unit_e($settings['email']); ?>"><?php echo unit_e($settings['email']); ?></a><a class="back-foundation" href="contact.php">Lihat Lokasi Sekolah</a></section><?php if ($socialLinks): ?><section class="unit-footer-social"><h2>Sosial Media</h2><div class="unit-footer-social-grid"><?php foreach ($socialLinks as $platform => $social): ?><?php unit_social_anchor($platform, $social); ?><?php endforeach; ?></div></section><?php endif; ?></div><div class="footer-bottom">&copy; <?php echo date('Y'); ?> <?php echo unit_e($settings['name']); ?>. Bagian dari SIT Permata Hati Bekasi.</div></footer><script src="<?php echo unit_e('../frontend/assets/js/instagram-gallery.js?v=' . (@filemtime(__DIR__ . '/../frontend/assets/js/instagram-gallery.js') ?: 1)); ?>"></script><script src="<?php echo unit_e('../frontend/assets/js/youtube-gallery.js?v=' . (@filemtime(__DIR__ . '/../frontend/assets/js/youtube-gallery.js') ?: 1)); ?>"></script><script src="<?php echo unit_asset('assets/js/app.js'); ?>"></script></body></html>
<?php }
