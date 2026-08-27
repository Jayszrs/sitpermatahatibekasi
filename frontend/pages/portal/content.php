<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth(['admin', 'humas']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    portal_verify_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_news') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $excerpt = trim($_POST['excerpt'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $publishedAt = $_POST['published_at'] ?? date('Y-m-d');
            $image = '';
            $previousImage = null;
            if ($id) {
                $existingStmt = $pdo->prepare('SELECT image FROM news WHERE id = ?');
                $existingStmt->execute([$id]);
                $previousImage = $existingStmt->fetchColumn() ?: null;
                $image = $previousImage ?: '';
            }
            if ($title === '' || $excerpt === '' || $content === '') throw new RuntimeException('Judul, ringkasan, dan isi berita wajib diisi.');
            if (!empty($_FILES['image']['name'])) $image = portal_upload_image($_FILES['image'], 'berita');
            if ($image === '') throw new RuntimeException('Gambar berita wajib dipilih.');
            $slug = portal_slug($title);
            $check = $pdo->prepare('SELECT id FROM news WHERE slug = ? AND id <> ? LIMIT 1');
            $check->execute([$slug, $id]);
            if ($check->fetch()) $slug .= '-' . ($id ?: time());
            if ($id) {
                $stmt = $pdo->prepare('UPDATE news SET title=?, slug=?, image=?, excerpt=?, content=?, published_at=? WHERE id=?');
                $stmt->execute([$title, $slug, $image, $excerpt, $content, $publishedAt, $id]);
                if ($previousImage && $previousImage !== $image) portal_delete_uploaded_image($previousImage);
                portal_log($pdo, 'update_news', 'Memperbarui berita: ' . $title);
            } else {
                $stmt = $pdo->prepare('INSERT INTO news (title, slug, image, excerpt, content, published_at) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$title, $slug, $image, $excerpt, $content, $publishedAt]);
                portal_log($pdo, 'create_news', 'Menerbitkan berita: ' . $title);
            }
            portal_flash('success', 'Berita berhasil disimpan dan tampil di website.');
        } elseif ($action === 'delete_news') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT title,image FROM news WHERE id=?'); $stmt->execute([$id]); $item = $stmt->fetch();
            $pdo->prepare('DELETE FROM news WHERE id=?')->execute([$id]);
            portal_delete_uploaded_image($item['image'] ?? null);
            portal_log($pdo, 'delete_news', 'Menghapus berita: ' . ($item['title'] ?? '#' . $id));
            portal_flash('success', 'Berita berhasil dihapus.');
        } elseif ($action === 'save_gallery_album') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if ($title === '') throw new RuntimeException('Nama album wajib diisi.');
            $slug = portal_slug($title);
            $check = $pdo->prepare('SELECT id FROM gallery_albums WHERE slug = ? LIMIT 1');
            $check->execute([$slug]);
            if ($check->fetch()) $slug .= '-' . time();
            $stmt = $pdo->prepare('INSERT INTO gallery_albums (title, slug, description, sort_order, is_active) VALUES (?,?,?,?,?)');
            $stmt->execute([$title, $slug, $description ?: null, $sortOrder, $isActive]);
            portal_log($pdo, 'create_gallery_album', 'Membuat album galeri: ' . $title);
            portal_flash('success', 'Album galeri berhasil dibuat.');
        } elseif ($action === 'delete_gallery_album') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT title FROM gallery_albums WHERE id=?');
            $stmt->execute([$id]);
            $album = $stmt->fetch();
            $photoStmt = $pdo->prepare('SELECT image FROM gallery_photos WHERE album_id=?');
            $photoStmt->execute([$id]);
            foreach ($photoStmt->fetchAll() as $photo) {
                portal_delete_uploaded_image($photo['image'] ?? null);
            }
            $pdo->prepare('DELETE FROM gallery_albums WHERE id=?')->execute([$id]);
            portal_log($pdo, 'delete_gallery_album', 'Menghapus album galeri: ' . ($album['title'] ?? '#' . $id));
            portal_flash('success', 'Album galeri berhasil dihapus.');
        } elseif ($action === 'save_gallery_photo' || $action === 'save_gallery') {
            $albumId = (int)($_POST['album_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            if ($albumId <= 0) throw new RuntimeException('Pilih album galeri terlebih dahulu.');
            if ($title === '') throw new RuntimeException('Judul foto wajib diisi.');
            $albumCheck = $pdo->prepare('SELECT id FROM gallery_albums WHERE id=? LIMIT 1');
            $albumCheck->execute([$albumId]);
            if (!$albumCheck->fetch()) throw new RuntimeException('Album galeri tidak ditemukan.');
            $image = portal_upload_image($_FILES['image'] ?? [], 'galeri');
            $stmt = $pdo->prepare('INSERT INTO gallery_photos (album_id, title, image, description, sort_order) VALUES (?,?,?,?,?)');
            $stmt->execute([$albumId, $title, $image, $description ?: null, $sortOrder]);
            portal_log($pdo, 'create_gallery_photo', 'Menambahkan foto galeri: ' . $title);
            portal_flash('success', 'Foto galeri berhasil diunggah.');
        } elseif ($action === 'delete_gallery_photo' || $action === 'delete_gallery') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT title,image FROM gallery_photos WHERE id=?'); $stmt->execute([$id]); $item = $stmt->fetch();
            $pdo->prepare('DELETE FROM gallery_photos WHERE id=?')->execute([$id]);
            portal_delete_uploaded_image($item['image'] ?? null);
            portal_log($pdo, 'delete_gallery_photo', 'Menghapus foto galeri: ' . ($item['title'] ?? '#' . $id));
            portal_flash('success', 'Foto galeri berhasil dihapus.');
        }
    } catch (Throwable $e) {
        portal_flash('danger', $e->getMessage());
    }
    header('Location: ' . SITE_URL . '/portal/content');
    exit;
}

$editNews = null;
if (isset($_GET['edit_news'])) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id=?');
    $stmt->execute([(int)$_GET['edit_news']]);
    $editNews = $stmt->fetch() ?: null;
}
$showNewsForm = ($_GET['new'] ?? '') === 'news' || $editNews;
$showGalleryAlbumForm = ($_GET['new'] ?? '') === 'gallery-album';
$showGalleryForm = ($_GET['new'] ?? '') === 'gallery' || ($_GET['new'] ?? '') === 'gallery-photo';
$news = $pdo->query('SELECT * FROM news ORDER BY published_at DESC, id DESC')->fetchAll();
$galleryAlbums = $pdo->query('
    SELECT a.*,
        (SELECT COUNT(*) FROM gallery_photos p WHERE p.album_id = a.id) AS photo_count
    FROM gallery_albums a
    ORDER BY a.sort_order ASC, a.created_at DESC, a.id DESC
')->fetchAll();
$gallery = $pdo->query('
    SELECT p.*, a.title AS album_title
    FROM gallery_photos p
    INNER JOIN gallery_albums a ON a.id = p.album_id
    ORDER BY a.sort_order ASC, p.sort_order ASC, p.created_at DESC, p.id DESC
')->fetchAll();

$portalTitle = 'Konten Website';
$portalActive = 'content';
require __DIR__ . '/../../components/portal-header.php';
?>
<div class="portal-welcome">
    <div><h2>Kelola Konten Publik</h2><p>Berita dan foto yang disimpan langsung tampil di company profile.</p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/content?new=gallery-album">+ Album Galeri</a><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/content?new=gallery-photo">+ Foto Galeri</a><a class="portal-action" href="<?php echo SITE_URL; ?>/portal/content?new=news">+ Berita</a></div>
</div>

<?php if ($showNewsForm): ?>
<section class="portal-panel" style="margin-bottom:22px">
    <div class="panel-head"><h3><?php echo $editNews ? 'Edit Berita' : 'Tulis Berita Baru'; ?></h3><a href="<?php echo SITE_URL; ?>/portal/content">Tutup</a></div>
    <form class="portal-form portal-form-grid" method="post" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save_news"><input type="hidden" name="id" value="<?php echo (int)($editNews['id'] ?? 0); ?>">
        <div class="field full"><label>Judul berita</label><input name="title" value="<?php echo esc($editNews['title'] ?? ''); ?>" required></div>
        <div class="field"><label>Tanggal publikasi</label><input type="date" name="published_at" value="<?php echo esc($editNews['published_at'] ?? date('Y-m-d')); ?>" required></div>
        <div class="field"><label>Gambar <?php echo $editNews ? '(kosongkan jika tetap)' : ''; ?></label><input type="file" name="image" accept="image/jpeg,image/png,image/webp" <?php echo $editNews ? '' : 'required'; ?>></div>
        <div class="field full"><label>Ringkasan</label><textarea name="excerpt" style="min-height:80px" required><?php echo esc($editNews['excerpt'] ?? ''); ?></textarea></div>
        <div class="field full"><label>Isi berita</label><textarea name="content" required><?php echo esc($editNews['content'] ?? ''); ?></textarea></div>
        <div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/content">Batal</a><button class="portal-action" type="submit">Simpan &amp; Terbitkan</button></div>
    </form>
</section>
<?php endif; ?>

<?php if ($showGalleryAlbumForm): ?>
<section class="portal-panel" style="margin-bottom:22px">
    <div class="panel-head"><h3>Buat Album Galeri</h3><a href="<?php echo SITE_URL; ?>/portal/content">Tutup</a></div>
    <form class="portal-form portal-form-grid" method="post">
        <input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save_gallery_album">
        <div class="field"><label>Nama album</label><input name="title" placeholder="Contoh: Kegiatan Sekolah" required></div>
        <div class="field"><label>Urutan tampil</label><input type="number" name="sort_order" value="0"></div>
        <div class="field full"><label>Deskripsi album</label><textarea name="description" style="min-height:80px"></textarea></div>
        <div class="field full"><label style="display:flex;align-items:center;gap:8px;font-weight:700"><input type="checkbox" name="is_active" checked> Tampilkan album di website</label></div>
        <div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/content">Batal</a><button class="portal-action" type="submit">Simpan Album</button></div>
    </form>
</section>
<?php endif; ?>

<?php if ($showGalleryForm): ?>
<section class="portal-panel" style="margin-bottom:22px">
    <div class="panel-head"><h3>Unggah Foto Galeri</h3><a href="<?php echo SITE_URL; ?>/portal/content">Tutup</a></div>
    <form class="portal-form portal-form-grid" method="post" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save_gallery_photo">
        <div class="field"><label>Album</label><select name="album_id" required><option value="">Pilih album</option><?php foreach ($galleryAlbums as $album): ?><option value="<?php echo (int)$album['id']; ?>"><?php echo esc($album['title']); ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Judul foto</label><input name="title" required></div>
        <div class="field"><label>Gambar (maksimal 5 MB)</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></div>
        <div class="field"><label>Urutan dalam album</label><input type="number" name="sort_order" value="0"></div>
        <div class="field full"><label>Keterangan</label><textarea name="description" style="min-height:80px"></textarea></div>
        <div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/content">Batal</a><button class="portal-action" type="submit">Unggah Foto</button></div>
    </form>
</section>
<?php endif; ?>

<section class="portal-panel" style="margin-bottom:22px">
    <div class="panel-head"><h3>Daftar Berita (<?php echo count($news); ?>)</h3></div>
    <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Gambar</th><th>Judul</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach ($news as $item): ?><tr><td><img src="<?php echo esc($item['image']); ?>" alt=""></td><td><strong><?php echo esc($item['title']); ?></strong><br><small style="color:var(--portal-muted)"><?php echo esc(mb_strimwidth($item['excerpt'], 0, 75, '...')); ?></small></td><td><?php echo esc(date('d/m/Y', strtotime($item['published_at']))); ?></td><td><div class="table-actions"><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/portal/content?edit_news=<?php echo (int)$item['id']; ?>">Edit</a><form method="post" onsubmit="return confirm('Hapus berita ini?')"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="delete_news"><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>"><button class="portal-action danger small">Hapus</button></form></div></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>

<section class="portal-panel" style="margin-bottom:22px">
    <div class="panel-head"><h3>Album Galeri (<?php echo count($galleryAlbums); ?>)</h3></div>
    <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Album</th><th>Slug</th><th>Foto</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach ($galleryAlbums as $album): ?><tr><td><strong><?php echo esc($album['title']); ?></strong><br><small style="color:var(--portal-muted)"><?php echo esc($album['description'] ?: '-'); ?></small></td><td><?php echo esc($album['slug']); ?></td><td><?php echo (int)$album['photo_count']; ?> foto</td><td><?php echo ((int)$album['is_active'] === 1) ? 'Aktif' : 'Nonaktif'; ?></td><td><div class="table-actions"><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/galeri-detail.php?id=<?php echo (int)$album['id']; ?>" target="_blank" rel="noopener">Lihat</a><form method="post" onsubmit="return confirm('Hapus album ini beserta semua fotonya?')"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="delete_gallery_album"><input type="hidden" name="id" value="<?php echo (int)$album['id']; ?>"><button class="portal-action danger small">Hapus</button></form></div></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>

<section class="portal-panel">
    <div class="panel-head"><h3>Foto Galeri (<?php echo count($gallery); ?>)</h3></div>
    <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Foto</th><th>Judul</th><th>Album</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach ($gallery as $item): ?><tr><td><img src="<?php echo esc($item['image']); ?>" alt=""></td><td><strong><?php echo esc($item['title']); ?></strong></td><td><?php echo esc($item['album_title']); ?></td><td><?php echo esc($item['description'] ?: '-'); ?></td><td><form method="post" onsubmit="return confirm('Hapus foto ini?')"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="delete_gallery_photo"><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>"><button class="portal-action danger small">Hapus</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/../../components/portal-footer.php'; ?>
