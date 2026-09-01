<?php
$footerUnits = [];
if (isset($pdo) && $pdo instanceof PDO) {
    $footerUnits = fetch_school_units($pdo);
}
?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-col footer-brand">
            <div class="brand">
                <img src="<?php echo esc(asset_url('frontend/assets/images/logo-sit-round.png')); ?>" alt="Logo <?php echo esc(SITE_NAME); ?>" class="brand-logo brand-logo-footer" onerror="this.style.display='none'">
                <span class="brand-copy">
                    <strong><?php echo esc(SITE_NAME); ?></strong>
                    <small>Sekolah Islam Terpadu</small>
                </span>
            </div>
            <p><?php echo esc(SITE_TAGLINE); ?>.</p>
            <div class="school-socials" aria-label="Sosial media sekolah">
                <strong class="footer-social-title">Sosial Media</strong>
                <div class="school-social-row">
                    <span class="school-social-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><path d="M17.5 6.5h.01"></path></svg>
                    </span>
                    <div class="school-social-links">
                        <a href="<?php echo esc(SITE_DAYCARE_INSTAGRAM); ?>" target="_blank" rel="noopener">Daycare</a>
                        <a href="<?php echo esc(SITE_TKIT_INSTAGRAM); ?>" target="_blank" rel="noopener">TKIT</a>
                        <a href="<?php echo esc(SITE_SDIT_INSTAGRAM); ?>" target="_blank" rel="noopener">SDIT</a>
                        <a href="<?php echo esc(SITE_SMPIT_INSTAGRAM); ?>" target="_blank" rel="noopener">SMPIT</a>
                    </div>
                </div>
                <div class="school-social-row">
                    <span class="school-social-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22.5 12s0-3.2-.4-4.7a2.8 2.8 0 0 0-2-2C18.5 5 12 5 12 5s-6.5 0-8.1.3a2.8 2.8 0 0 0-2 2C1.5 8.8 1.5 12 1.5 12s0 3.2.4 4.7a2.8 2.8 0 0 0 2 2C5.5 19 12 19 12 19s6.5 0 8.1-.3a2.8 2.8 0 0 0 2-2c.4-1.5.4-4.7.4-4.7Z"></path><path d="m10 15 5-3-5-3v6Z"></path></svg>
                    </span>
                    <div class="school-social-links">
                        <a href="<?php echo esc(SITE_SDIT_YOUTUBE); ?>" target="_blank" rel="noopener">SDIT</a>
                        <a href="<?php echo esc(SITE_SMPIT_YOUTUBE); ?>" target="_blank" rel="noopener">SMPIT</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-col">
            <h4>Menu Cepat</h4>
            <ul>
                <li><a href="<?php echo SITE_URL; ?>/index.php">Beranda</a></li>
                <li><a href="<?php echo SITE_URL; ?>/tentang.php">Tentang Kami</a></li>
                <li><a href="<?php echo SITE_URL; ?>/berita.php">Berita</a></li>
                <li><a href="<?php echo SITE_URL; ?>/galeri.php">Galeri</a></li>
                <li><a href="<?php echo SITE_URL; ?>/kontak.php">Kontak</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Unit Sekolah</h4>
            <ul>
                <?php foreach ($footerUnits as $footerUnit): ?>
                <?php $footerUnitAnchor = $footerUnit['slug']; ?>
                <li><a href="<?php echo SITE_URL; ?>/unit.php#<?php echo esc($footerUnitAnchor); ?>"><?php echo esc($footerUnit['title']); ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?php echo SITE_URL; ?>/spmb.php">Penerimaan Siswa Baru</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3>Kontak</h3>
            <ul class="footer-contact-list">
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span><strong><?php echo esc(SITE_MAIN_CAMPUS_LABEL); ?></strong><?php echo esc(SITE_MAIN_CAMPUS_ADDRESS); ?></span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span><strong><?php echo esc(SITE_SMPIT_CAMPUS_LABEL); ?></strong><?php echo esc(SITE_SMPIT_CAMPUS_ADDRESS); ?></span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.98.36 1.92.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.89.34 1.83.57 2.81.7A2 2 0 0 1 22 16.92Z"></path></svg>
                    <span><?php echo esc(SITE_PHONE); ?></span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2Z"></path><path d="m22 6-10 7L2 6"></path></svg>
                    <span><?php echo esc(SITE_EMAIL); ?></span>
                </li>
            </ul>
            <a href="<?php echo SITE_URL; ?>/kontak.php" class="footer-location-link">Lihat Lokasi Sekolah</a>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo esc(SITE_NAME); ?>. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<a href="https://wa.me/<?php echo esc(SITE_WHATSAPP); ?>" class="wa-float" target="_blank" rel="noopener" aria-label="Hubungi WhatsApp" title="Chat Admin">
    <svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
    </svg>
</a>

<div class="image-lightbox" id="imageLightbox" aria-hidden="true">
    <button type="button" class="image-lightbox-backdrop" data-lightbox-close aria-label="Tutup preview gambar"></button>
    <div class="image-lightbox-dialog" role="dialog" aria-modal="true" aria-label="Preview gambar gedung">
        <button type="button" class="image-lightbox-close" data-lightbox-close aria-label="Tutup preview gambar">&times;</button>
        <img src="" alt="" id="imageLightboxImage">
        <p id="imageLightboxTitle"></p>
    </div>
</div>

<script>
document.addEventListener('error', function (event) {
    var image = event.target;
    if (!(image instanceof HTMLImageElement)) return;
    var fallback = image.getAttribute('data-fallback');
    if (!fallback || image.getAttribute('data-fallback-used') === 'true') return;
    image.setAttribute('data-fallback-used', 'true');
    image.src = fallback;
}, true);

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-native-share]').forEach(function (button) {
        if (!navigator.share) { button.hidden = true; return; }
        button.addEventListener('click', function () {
            navigator.share({ title: button.dataset.shareTitle || document.title, url: button.dataset.shareUrl || window.location.href }).catch(function () {});
        });
    });

    var lightbox = document.getElementById('imageLightbox');
    var lightboxImage = document.getElementById('imageLightboxImage');
    var lightboxTitle = document.getElementById('imageLightboxTitle');
    if (!lightbox || !lightboxImage || !lightboxTitle) return;

    document.querySelectorAll('.image-preview-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            lightboxImage.src = trigger.getAttribute('data-lightbox-src') || '';
            lightboxImage.alt = trigger.getAttribute('data-lightbox-title') || 'Preview gambar';
            lightboxTitle.textContent = trigger.getAttribute('data-lightbox-title') || '';
            lightbox.classList.add('open');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        });
    });

    function closeLightbox() {
        lightbox.classList.remove('open');
        lightbox.setAttribute('aria-hidden', 'true');
        lightboxImage.src = '';
        document.body.style.overflow = '';
    }

    lightbox.querySelectorAll('[data-lightbox-close]').forEach(function (button) {
        button.addEventListener('click', closeLightbox);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && lightbox.classList.contains('open')) {
            closeLightbox();
        }
    });
});
</script>

</body>
</html>
