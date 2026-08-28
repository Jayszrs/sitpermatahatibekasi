<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Brosur Sekolah';
$brochures = $pdo->query('SELECT * FROM brochures WHERE is_active=1 ORDER BY sort_order,id')->fetchAll();
require_once __DIR__ . '/../components/header.php';
?>
<section class="page-header page-header-photo"><div class="container"><span class="page-kicker">Informasi Unit</span><h1>Brosur Sekolah</h1><p class="breadcrumb"><a href="index.php">Beranda</a> / Brosur</p></div></section>
<section class="section brochure-section"><div class="container"><div class="section-head"><span class="section-eyebrow">Pilih Unit</span><h2>Kenali Program Setiap Jenjang</h2><p>Lihat ringkasan program dan unduh brosur resmi sesuai unit yang Anda butuhkan.</p></div><div class="brochure-grid">
<?php foreach($brochures as $item): ?><article class="brochure-card"><a class="brochure-cover" href="brosur-unit.php?unit=<?php echo urlencode($item['unit_slug']); ?>"><img src="<?php echo esc($item['cover_image']); ?>" alt="Brosur <?php echo esc($item['unit_name']); ?>" loading="lazy"><span><?php echo esc($item['unit_name']); ?></span></a><div><h3><?php echo esc($item['headline']); ?></h3><p><?php echo esc(mb_strimwidth($item['description'],0,155,'...')); ?></p><a class="btn btn-outline btn-sm" href="brosur-unit.php?unit=<?php echo urlencode($item['unit_slug']); ?>">Lihat Brosur</a></div></article><?php endforeach; ?>
</div></div></section>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
