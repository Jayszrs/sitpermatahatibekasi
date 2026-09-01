<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Berita';

$stmt = $pdo->query("SELECT * FROM news ORDER BY published_at DESC");
$news_list = $stmt->fetchAll();
foreach ($news_list as &$newsItem) $newsItem['image'] = public_media_url($newsItem['image'] ?? null);
unset($newsItem);
$news_units = ['Semua', 'Daycare', 'TKIT', 'SDIT', 'SMPIT'];

require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Berita &amp; Informasi</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / Berita</p>
    </div>
</section>

<section class="section news-index-section">
    <div class="container">
        <div class="section-head">
            <span class="section-eyebrow">Berita Per Unit</span>
            <h2>Kabar dari Setiap Jenjang</h2>
            <p>Pilih unit untuk melihat kegiatan dan informasi terbaru dari Daycare, TKIT, SDIT, atau SMPIT.</p>
        </div>
        <div class="achievement-tabs news-unit-tabs" role="tablist" aria-label="Filter berita berdasarkan unit">
            <?php foreach ($news_units as $unit): ?>
                <button type="button" class="achievement-tab<?php echo $unit === 'Semua' ? ' active' : ''; ?>" data-news-filter="<?php echo esc(strtolower($unit)); ?>"><?php echo esc($unit); ?></button>
            <?php endforeach; ?>
        </div>
        <?php if (count($news_list) > 0): ?>
        <?php $featured = $news_list[0]; ?>
        <a class="news-featured-card news-filter-card" data-news-featured data-news-unit="<?php echo esc(strtolower($featured['unit'] ?? 'SDIT')); ?>" href="detail-berita.php?slug=<?php echo urlencode($featured['slug']); ?>">
            <div class="news-featured-image">
                <img src="<?php echo esc($featured['image']); ?>" alt="<?php echo esc($featured['title']); ?>">
                <span>Berita Utama &middot; <?php echo esc($featured['unit'] ?? 'SDIT'); ?></span>
            </div>
            <div>
                <span class="news-unit-chip"><?php echo esc($featured['unit'] ?? 'SDIT'); ?></span>
                <div class="news-date"><?php echo tanggal_indo($featured['published_at']); ?></div>
                <h2><?php echo esc($featured['title']); ?></h2>
                <p><?php echo esc($featured['excerpt']); ?></p>
                <strong>Baca cerita lengkap &rarr;</strong>
            </div>
        </a>
        <div class="news-index-head" data-news-list-head>
            <div>
                <span class="section-eyebrow" data-news-list-eyebrow>Informasi Lainnya</span>
                <h2 data-news-list-title>Berita Sekolah</h2>
            </div>
            <p>Dokumentasi kegiatan, pencapaian, dan informasi terbaru SIT Permata Hati Bekasi.</p>
        </div>
        <div class="news-modern-grid">
            <?php foreach ($news_list as $news_index => $news): ?>
            <article class="news-modern-card news-filter-card<?php echo $news_index === 0 ? ' is-hidden' : ''; ?>"
                data-news-unit="<?php echo esc(strtolower($news['unit'] ?? 'SDIT')); ?>"
                <?php echo $news_index === 0 ? 'data-news-featured-duplicate' : ''; ?>>
                <a class="news-modern-image" href="detail-berita.php?slug=<?php echo urlencode($news['slug']); ?>">
                    <img src="<?php echo esc($news['image']); ?>" alt="<?php echo esc($news['title']); ?>" loading="lazy">
                    <span class="news-card-unit"><?php echo esc($news['unit'] ?? 'SDIT'); ?></span>
                </a>
                <div>
                    <div class="news-date"><?php echo tanggal_indo($news['published_at']); ?></div>
                    <h3><a href="detail-berita.php?slug=<?php echo urlencode($news['slug']); ?>"><?php echo esc($news['title']); ?></a></h3>
                    <p><?php echo esc(mb_strimwidth($news['excerpt'], 0, 145, '...')); ?></p>
                    <a href="detail-berita.php?slug=<?php echo urlencode($news['slug']); ?>" class="news-link">Baca Selengkapnya &rarr;</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="news-filter-empty" hidden>Belum ada berita untuk unit ini.</div>
        <?php else: ?>
        <p style="text-align:center; color: var(--muted);">Belum ada berita.</p>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelector('.news-unit-tabs');
    if (!tabs) return;
    const featuredCard = document.querySelector('[data-news-featured]');
    const compactCards = [...document.querySelectorAll('.news-modern-card[data-news-unit]')];
    const listHead = document.querySelector('[data-news-list-head]');
    const listEyebrow = document.querySelector('[data-news-list-eyebrow]');
    const listTitle = document.querySelector('[data-news-list-title]');
    const emptyState = document.querySelector('.news-filter-empty');

    const setCardVisible = (card, visible, immediate = false) => {
        if (!card) return;
        window.clearTimeout(card._newsFilterTimer);
        if (visible) {
            card.classList.remove('is-hidden');
            requestAnimationFrame(() => card.classList.remove('is-faded'));
            return;
        }
        card.classList.add('is-faded');
        if (immediate) {
            card.classList.add('is-hidden');
            return;
        }
        card._newsFilterTimer = window.setTimeout(() => card.classList.add('is-hidden'), 220);
    };

    const applyFilter = (filter, label) => {
        const showAll = filter === 'semua';
        setCardVisible(featuredCard, showAll, true);

        let compactVisibleCount = 0;
        compactCards.forEach((card) => {
            const isFeaturedDuplicate = card.hasAttribute('data-news-featured-duplicate');
            const visible = showAll ? !isFeaturedDuplicate : card.dataset.newsUnit === filter;
            if (visible) compactVisibleCount += 1;
            setCardVisible(card, visible, isFeaturedDuplicate);
        });

        if (listHead) listHead.hidden = compactVisibleCount === 0;
        if (listEyebrow) listEyebrow.textContent = showAll ? 'Informasi Lainnya' : 'Berita Per Unit';
        if (listTitle) listTitle.textContent = showAll ? 'Berita Sekolah' : `Berita ${label}`;
        const visibleCount = compactVisibleCount + (showAll && featuredCard ? 1 : 0);
        if (emptyState) emptyState.hidden = visibleCount !== 0;
    };

    tabs.querySelectorAll('[data-news-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.newsFilter;
            tabs.querySelectorAll('[data-news-filter]').forEach((tab) => tab.classList.remove('active'));
            button.classList.add('active');
            applyFilter(filter, button.textContent.trim());
            const url = new URL(window.location.href);
            if (filter === 'semua') url.searchParams.delete('unit');
            else url.searchParams.set('unit', filter);
            window.history.replaceState({}, '', url);
        });
    });

    const requestedUnit = new URLSearchParams(window.location.search).get('unit')?.toLowerCase();
    const requestedTab = requestedUnit
        ? tabs.querySelector(`[data-news-filter="${CSS.escape(requestedUnit)}"]`)
        : null;
    if (requestedTab) requestedTab.click();
    else applyFilter('semua', 'Semua');
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
