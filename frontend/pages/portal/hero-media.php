<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth(['admin', 'humas']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    portal_verify_csrf();
    try {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);
        if ($action === 'save') {
            $title = trim($_POST['title'] ?? '');
            $eyebrow = trim($_POST['eyebrow'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $mediaType = $_POST['media_type'] ?? 'image';
            $ctaLabel = trim($_POST['cta_label'] ?? '');
            $ctaUrl = trim($_POST['cta_url'] ?? '');
            $sortOrder = max(0, (int) ($_POST['sort_order'] ?? 0));
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if ($title === '' || !in_array($mediaType, ['image', 'video'], true)) throw new RuntimeException('Judul dan jenis media wajib valid.');
            if ($ctaUrl !== '' && !filter_var($ctaUrl, FILTER_VALIDATE_URL) && strpos($ctaUrl, '/') !== 0) throw new RuntimeException('Tautan tombol harus URL lengkap atau diawali /.');

            $previous = null;
            $mediaUrl = '';
            $posterUrl = '';
            if ($id) {
                $stmt = $pdo->prepare('SELECT * FROM hero_media WHERE id=?');
                $stmt->execute([$id]);
                $previous = $stmt->fetch();
                if (!$previous) throw new RuntimeException('Media hero tidak ditemukan.');
                $mediaUrl = $previous['media_url'];
                $posterUrl = $previous['poster_url'] ?: '';
            }
            if (!empty($_FILES['media']['name'])) $mediaUrl = portal_upload_hero_media($_FILES['media'], $mediaType);
            if ($mediaUrl === '') throw new RuntimeException('File gambar atau video wajib diunggah.');
            if ($mediaType === 'video' && !empty($_FILES['poster']['name'])) $posterUrl = portal_upload_image($_FILES['poster'], 'hero-poster');
            if ($mediaType === 'image') $posterUrl = '';

            if ($id) {
                $stmt = $pdo->prepare('UPDATE hero_media SET title=?,eyebrow=?,description=?,media_type=?,media_url=?,poster_url=?,cta_label=?,cta_url=?,sort_order=?,is_active=? WHERE id=?');
                $stmt->execute([$title,$eyebrow?:null,$description?:null,$mediaType,$mediaUrl,$posterUrl?:null,$ctaLabel?:null,$ctaUrl?:null,$sortOrder,$isActive,$id]);
                if ($previous['media_url'] !== $mediaUrl) portal_delete_uploaded_image($previous['media_url']);
                if (($previous['poster_url'] ?? '') !== $posterUrl) portal_delete_uploaded_image($previous['poster_url'] ?? null);
                portal_log($pdo, 'update_hero', 'Memperbarui hero: ' . $title);
            } else {
                $stmt = $pdo->prepare('INSERT INTO hero_media (title,eyebrow,description,media_type,media_url,poster_url,cta_label,cta_url,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$title,$eyebrow?:null,$description?:null,$mediaType,$mediaUrl,$posterUrl?:null,$ctaLabel?:null,$ctaUrl?:null,$sortOrder,$isActive]);
                portal_log($pdo, 'create_hero', 'Menambah hero: ' . $title);
            }
            portal_flash('success', 'Media hero berhasil disimpan. Jumlah slide tidak dibatasi.');
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('SELECT title,media_url,poster_url FROM hero_media WHERE id=?');
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            if ($item) {
                $pdo->prepare('DELETE FROM hero_media WHERE id=?')->execute([$id]);
                portal_delete_uploaded_image($item['media_url']);
                portal_delete_uploaded_image($item['poster_url']);
                portal_log($pdo, 'delete_hero', 'Menghapus hero: ' . $item['title']);
            }
            portal_flash('success', 'Media hero berhasil dihapus.');
        }
    } catch (Throwable $e) {
        portal_flash('danger', $e->getMessage());
    }
    header('Location: ' . SITE_URL . '/portal/hero-media');
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM hero_media WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $editItem = $stmt->fetch() ?: null;
}
$showForm = isset($_GET['new']) || $editItem;
$items = $pdo->query('SELECT * FROM hero_media ORDER BY sort_order,id')->fetchAll();
$portalTitle = 'Hero Image & Video';
$portalActive = 'hero-media';
require __DIR__ . '/../../components/portal-header.php';
?>
<div class="portal-welcome"><div><h2>Onboarding Homepage</h2><p>Upload gambar, GIF, atau video sebanyak yang dibutuhkan. Gambar berganti setiap 3 detik; video berganti setelah selesai diputar.</p></div><a class="portal-action" href="<?php echo SITE_URL; ?>/portal/hero-media?new=1">+ Tambah Media</a></div>

<section class="portal-panel media-guide" style="margin-bottom:22px"><div class="panel-head"><h3>Daftar Kebutuhan Media</h3></div><div class="media-needs-grid"><div><strong>Foto hero</strong><span>Landscape 1920×1080, JPG/WebP, maksimal 10 MB.</span></div><div><strong>Video hero</strong><span>MP4/WebM 16:9, tanpa audio lebih aman, maksimal 80 MB.</span></div><div><strong>Poster video</strong><span>Landscape 1920×1080 agar loading tetap rapi.</span></div><div><strong>Konten kartu</strong><span>Rasio 4:3 atau 16:10, subjek fokus, tanpa teks kecil.</span></div></div></section>

<?php if ($showForm): ?>
<section class="portal-panel" style="margin-bottom:22px"><div class="panel-head"><h3><?php echo $editItem ? 'Edit' : 'Tambah'; ?> Media Hero</h3><a href="<?php echo SITE_URL; ?>/portal/hero-media">Tutup</a></div>
<form class="portal-form portal-form-grid" method="post" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?php echo (int)($editItem['id'] ?? 0); ?>">
<div class="field"><label>Judul utama</label><input name="title" value="<?php echo esc($editItem['title'] ?? ''); ?>" required></div><div class="field"><label>Label kecil / eyebrow</label><input name="eyebrow" value="<?php echo esc($editItem['eyebrow'] ?? ''); ?>" placeholder="Contoh: SPMB 2026/2027"></div>
<div class="field"><label>Jenis media</label><select name="media_type" required><option value="image" <?php echo ($editItem['media_type'] ?? '') === 'image' ? 'selected' : ''; ?>>Gambar / GIF</option><option value="video" <?php echo ($editItem['media_type'] ?? '') === 'video' ? 'selected' : ''; ?>>Video</option></select></div><div class="field"><label>File media <?php echo $editItem ? '(kosongkan jika tetap)' : '*'; ?></label><input type="file" name="media" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" <?php echo $editItem ? '' : 'required'; ?>></div>
<div class="field"><label>Poster video (opsional)</label><input type="file" name="poster" accept="image/jpeg,image/png,image/webp"></div><div class="field"><label>Urutan tampil</label><input type="number" min="0" name="sort_order" value="<?php echo (int)($editItem['sort_order'] ?? count($items)+1); ?>"></div>
<div class="field full"><label>Deskripsi</label><textarea name="description"><?php echo esc($editItem['description'] ?? ''); ?></textarea></div><div class="field"><label>Teks tombol</label><input name="cta_label" value="<?php echo esc($editItem['cta_label'] ?? ''); ?>" placeholder="Contoh: Daftar SPMB"></div><div class="field"><label>Tujuan tombol</label><input name="cta_url" value="<?php echo esc($editItem['cta_url'] ?? ''); ?>" placeholder="/spmb.php atau https://..."></div>
<div class="field full"><label style="display:flex;gap:8px;align-items:center;text-transform:none"><input style="width:auto" type="checkbox" name="is_active" <?php echo !isset($editItem['is_active']) || $editItem['is_active'] ? 'checked' : ''; ?>> Tampilkan di homepage</label></div><div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/hero-media">Batal</a><button class="portal-action">Simpan Media</button></div>
</form></section>
<?php endif; ?>

<section class="portal-panel"><div class="panel-head"><h3>Semua Media (<?php echo count($items); ?>)</h3></div><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Preview</th><th>Konten</th><th>Jenis</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach($items as $item): ?><tr><td><?php if($item['media_type']==='video'): ?><video src="<?php echo esc($item['media_url']); ?>" muted preload="metadata"></video><?php else: ?><img src="<?php echo esc($item['media_url']); ?>" alt=""><?php endif; ?></td><td><strong><?php echo esc($item['title']); ?></strong><br><small><?php echo esc($item['eyebrow'] ?: '-'); ?></small></td><td><?php echo $item['media_type']==='video'?'Video':'Gambar'; ?></td><td><?php echo (int)$item['sort_order']; ?></td><td><span class="status <?php echo $item['is_active']?'active':'inactive'; ?>"><?php echo $item['is_active']?'Tampil':'Nonaktif'; ?></span></td><td><div class="table-actions"><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/portal/hero-media?edit=<?php echo (int)$item['id']; ?>">Edit</a><form method="post" onsubmit="return confirm('Hapus media hero ini?')"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>"><button class="portal-action danger small">Hapus</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/../../components/portal-footer.php'; ?>
