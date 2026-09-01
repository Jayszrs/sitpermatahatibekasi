<?php
$metaTitle = isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME;
$metaDescription = $meta_description ?? SITE_TAGLINE;
$metaImage = public_media_url($meta_image ?? null);
$metaUrl = $canonical_url ?? SITE_URL . '/' . ltrim((string)($current_page ?? 'index.php'), '/');
$metaType = $meta_type ?? 'website';
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

        <button class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false">
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
            </ul>
        </nav>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Mobile nav toggle
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('mainNav');
    toggle.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('open');
        toggle.classList.toggle('active');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
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
        if (e.target.closest('.has-dropdown')) return;
        document.querySelectorAll('.has-dropdown.dropdown-open').forEach(function(item) {
            item.classList.remove('dropdown-open');
            var itemTrigger = item.querySelector('.dropdown-trigger');
            if (itemTrigger) itemTrigger.setAttribute('aria-expanded', 'false');
        });
    });

    nav.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            nav.classList.remove('open');
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
});
</script>
