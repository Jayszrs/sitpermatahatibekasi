<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($page_title) ? esc($page_title) . ' - ' . SITE_NAME : SITE_NAME; ?></title>
<meta name="description" content="<?php echo esc(SITE_TAGLINE); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=El+Messiri:wght@600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/frontend/assets/css/style.css">
</head>
<body>

<header class="site-header" id="siteHeader">
    <div class="header-inner">
        <a href="<?php echo SITE_URL; ?>/index.php" class="brand">
            <img src="<?php echo SITE_URL; ?>/frontend/assets/images/logo-sit-permata-hati.jpeg" alt="Logo <?php echo esc(SITE_NAME); ?>" class="brand-logo" onerror="this.style.display='none'">
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
                        <a href="javascript:void(0)" class="dropdown-trigger <?php
                            // Mark active if current page is one of the children
                            $childActive = false;
                            foreach ($item['children'] as $cf => $cl) {
                                if ($current_page === $cf) { $childActive = true; break; }
                            }
                            echo $childActive ? 'active' : '';
                        ?>">
                            <?php echo esc($item['label']); ?>
                            <svg class="dropdown-arrow" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($item['children'] as $childFile => $childLabel): ?>
                            <li>
                                <a href="<?php echo SITE_URL . '/' . $childFile; ?>" class="<?php echo ($current_page === $childFile) ? 'active' : ''; ?>">
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

    // Dropdown toggle for mobile
    var dropdownTriggers = document.querySelectorAll('.dropdown-trigger');
    dropdownTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                var parent = this.closest('.has-dropdown');
                parent.classList.toggle('dropdown-open');
            }
        });
    });

    // Header shrink on scroll
    var header = document.getElementById('siteHeader');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
});
</script>
