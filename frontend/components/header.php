<?php
$metaTitle = isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME;
$metaDescription = $meta_description ?? SITE_TAGLINE;
$metaImage = public_media_url($meta_image ?? null);
$metaUrl = $canonical_url ?? SITE_URL . '/' . ltrim((string)($current_page ?? 'index.php'), '/');
$metaType = $meta_type ?? 'website';
$schoolSocialLinks = [
    'daycare' => [
        'label' => 'Daycare',
        'links' => [
            'instagram' => ['label' => 'Instagram', 'url' => 'https://www.instagram.com/daycarepermatahati.bekasi/'],
            'facebook' => ['label' => 'Facebook', 'url' => 'https://www.facebook.com/tkitpermatahatibekasi/posts/day-care-permata-hati-tambun-bekasiday-care-adalah-lembaga-penitipan-anak-yang-d/227697477972901/'],
        ],
    ],
    'tkit' => [
        'label' => 'TKIT',
        'links' => [
            'instagram' => ['label' => 'Instagram', 'url' => 'https://www.instagram.com/tkitpermatahatibekasi/'],
            'facebook' => ['label' => 'Facebook', 'url' => 'https://www.facebook.com/tkitpermatahatibekasi/?locale=id_ID'],
        ],
    ],
    'sdit' => [
        'label' => 'SDIT',
        'links' => [
            'instagram' => ['label' => 'Instagram', 'url' => 'https://www.instagram.com/sditphbekasi/'],
            'facebook' => ['label' => 'Facebook', 'url' => 'https://www.facebook.com/sditpermatahatibekasi/?locale=id_ID'],
            'youtube' => ['label' => 'YouTube', 'url' => 'http://www.youtube.com/@sditpermatahatibekasi99'],
        ],
    ],
    'smpit' => [
        'label' => 'SMPIT',
        'links' => [
            'instagram' => ['label' => 'Instagram', 'url' => 'https://www.instagram.com/smpit_permatahati/?hl=id'],
            'facebook' => ['label' => 'Facebook', 'url' => 'https://www.facebook.com/pembangungenerasirobani/photos/'],
            'tiktok' => ['label' => 'TikTok', 'url' => 'https://www.tiktok.com/@smpit_permatahati'],
            'youtube' => ['label' => 'YouTube', 'url' => 'http://www.youtube.com/@smpit_permatahati'],
        ],
    ],
];
$renderSocialIcon = static function (string $key): void {
    if ($key === 'facebook') {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8.2h2.2V4.6c-.38-.05-1.7-.16-3.23-.16-3.2 0-5.39 1.95-5.39 5.52v3.11H4v4.02h3.58V24h4.39v-6.91h3.45l.55-4.02h-4V10.36c0-1.16.32-2.16 2.03-2.16Z"/></svg>';
    } elseif ($key === 'instagram') {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"></rect><circle cx="12" cy="12" r="3.4"></circle><path d="M17.4 6.7h.01"></path></svg>';
    } elseif ($key === 'tiktok') {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.2 4v10.4a4.6 4.6 0 1 1-4-4.56v3.35a1.55 1.55 0 1 0 1.12 1.49V4h2.88c.42 2.02 1.74 3.57 3.8 4.18v3.26a7.5 7.5 0 0 1-3.8-1.47Z"/></svg>';
    } else {
        echo '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6.5" width="18" height="11" rx="3.2"></rect><path d="m10.4 9.3 4.7 2.7-4.7 2.7V9.3Z"></path></svg>';
    }
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($metaTitle); ?></title>
<meta name="description" content="<?php echo esc($metaDescription); ?>">
<link rel="canonical" href="<?php echo esc($metaUrl); ?>">
<meta property="og:locale" content="id_ID">
<meta property="og:type" content="<?php echo esc($metaType); ?>">
<meta property="og:site_name" content="<?php echo esc(SITE_NAME); ?>">
<meta property="og:title" content="<?php echo esc($metaTitle); ?>">
<meta property="og:description" content="<?php echo esc($metaDescription); ?>">
<meta property="og:url" content="<?php echo esc($metaUrl); ?>">
<meta property="og:image" content="<?php echo esc($metaImage); ?>">
<meta property="og:image:alt" content="<?php echo esc($metaTitle); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo esc($metaTitle); ?>">
<meta name="twitter:description" content="<?php echo esc($metaDescription); ?>">
<meta name="twitter:image" content="<?php echo esc($metaImage); ?>">
<link rel="icon" type="image/png" href="<?php echo esc(asset_url('frontend/assets/images/logo-sit-round.png')); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=El+Messiri:wght@600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo esc(asset_url('frontend/assets/css/style.css')); ?>">
</head>
<?php $pageClass = 'page-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower(pathinfo((string)($current_page ?? 'index.php'), PATHINFO_FILENAME))); ?>
<body class="<?php echo esc(trim($pageClass, '-')); ?>">

<?php
$isNavActive = static function (string $target) use ($current_page): bool {
    $targetPath = parse_url($target, PHP_URL_PATH) ?: $target;
    if ($current_page !== $targetPath) return false;
    $targetQuery = parse_url($target, PHP_URL_QUERY);
    if (!$targetQuery) return true;
    parse_str($targetQuery, $expected);
    foreach ($expected as $key => $value) if ((string)($_GET[$key] ?? '') !== (string)$value) return false;
    return true;
};
?>
<header class="site-header" id="siteHeader">
    <div class="header-inner">
        <a href="<?php echo SITE_URL; ?>/index.php" class="brand">
            <img src="<?php echo esc(asset_url('frontend/assets/images/logo-sit-round.png')); ?>" alt="Logo <?php echo esc(SITE_NAME); ?>" class="brand-logo" onerror="this.style.display='none'">
            <span class="brand-copy">
                <strong><?php echo esc(SITE_NAME); ?></strong>
                <small>Sekolah Islam Terpadu</small>
            </span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-controls="mainNav" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" id="mainNav">
            <ul>
                <?php foreach ($nav_menu as $file => $item): ?>
                    <?php if (is_array($item)): ?>
                    <!-- Dropdown menu item -->
                    <li class="has-dropdown">
                        <button type="button" class="dropdown-trigger <?php
                            // Mark active if current page is one of the children
                            $childActive = false;
                            foreach ($item['children'] as $cf => $cl) {
                                if ($isNavActive($cf)) { $childActive = true; break; }
                            }
                            echo $childActive ? 'active' : '';
                        ?>" aria-expanded="false">
                            <?php echo esc($item['label']); ?>
                            <svg class="dropdown-arrow" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach ($item['children'] as $childFile => $childLabel): ?>
                            <li>
                                <a href="<?php echo SITE_URL . '/' . $childFile; ?>" class="<?php echo $isNavActive($childFile) ? 'active' : ''; ?>">
                                    <?php echo esc($childLabel); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php else: ?>
                    <!-- Regular menu item -->
                    <li>
                        <a href="<?php echo SITE_URL . '/' . $file; ?>" class="<?php echo ($current_page === $file) ? 'active' : ''; ?>">
                            <?php echo esc($item); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li class="has-dropdown social-dropdown">
                    <button type="button" class="dropdown-trigger social-dropdown-trigger" aria-expanded="false">
                        Sosial Media
                        <svg class="dropdown-arrow" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <ul class="dropdown-menu social-dropdown-menu" aria-label="Media sosial SIT Permata Hati Bekasi">
                        <?php foreach ($schoolSocialLinks as $unit): ?>
                            <li class="social-unit-row">
                                <span class="social-unit-label"><?php echo esc($unit['label']); ?></span>
                                <span class="social-unit-links">
                                    <?php foreach ($unit['links'] as $key => $social): ?>
                                        <a class="nav-social-link social-<?php echo esc($key); ?>" href="<?php echo esc($social['url']); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc($unit['label'] . ' ' . $social['label']); ?>" title="<?php echo esc($unit['label'] . ' - ' . $social['label']); ?>">
                                            <?php $renderSocialIcon($key); ?>
                                            <span><?php echo esc($social['label']); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Mobile nav toggle
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('mainNav');
    function setNavOpen(isOpen) {
        nav.classList.toggle('open', isOpen);
        toggle.classList.toggle('active', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        toggle.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
    }
    toggle.addEventListener('click', function () {
        setNavOpen(!nav.classList.contains('open'));
    });

    // Dropdown dapat dibuka dengan klik/tap maupun keyboard.
    var dropdownTriggers = document.querySelectorAll('.dropdown-trigger');
    dropdownTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            var parent = this.closest('.has-dropdown');
            var willOpen = !parent.classList.contains('dropdown-open');
            document.querySelectorAll('.has-dropdown.dropdown-open').forEach(function(item) {
                item.classList.remove('dropdown-open');
                var itemTrigger = item.querySelector('.dropdown-trigger');
                if (itemTrigger) itemTrigger.setAttribute('aria-expanded', 'false');
            });
            parent.classList.toggle('dropdown-open', willOpen);
            this.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && nav.classList.contains('open') && !e.target.closest('#mainNav') && !e.target.closest('#navToggle')) {
            setNavOpen(false);
        }
        if (e.target.closest('.has-dropdown')) return;
        document.querySelectorAll('.has-dropdown.dropdown-open').forEach(function(item) {
            item.classList.remove('dropdown-open');
            var itemTrigger = item.querySelector('.dropdown-trigger');
            if (itemTrigger) itemTrigger.setAttribute('aria-expanded', 'false');
        });
    });

    nav.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            setNavOpen(false);
        });
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && nav.classList.contains('open')) {
            setNavOpen(false);
            toggle.focus();
        }
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && nav.classList.contains('open')) setNavOpen(false);
    });
});
</script>
