<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth(['admin', 'humas']);

const SOCIAL_GALLERY_SCOPE = 'yayasan';
const SOCIAL_GALLERY_MAX = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    portal_verify_csrf();
    try {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);
        if ($action === 'save') {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM instagram_gallery WHERE scope=?');
            $countStmt->execute([SOCIAL_GALLERY_SCOPE]);
            $currentCount = (int) $countStmt->fetchColumn();
            if (!$id && $currentCount >= SOCIAL_GALLERY_MAX) {
                throw new RuntimeException('Galeri Instagram yayasan sudah penuh (maksimal ' . SOCIAL_GALLERY_MAX . ' item). Hapus salah satu dulu sebelum menambah yang baru.');
            }
            $caption = trim($_POST['caption'] ?? '');
            $instagramUrl = trim($_POST['instagram_url'] ?? '');
            $sortOrder = max(0, (int) ($_POST['sort_order'] ?? 0));
            if ($instagramUrl === '') throw new RuntimeException('Tempel link postingan Instagram-nya dulu.');
            if (!filter_var($instagramUrl, FILTER_VALIDATE_URL) || !str_contains($instagramUrl, 'instagram.com')) throw new RuntimeException('Link harus berupa URL postingan Instagram yang valid (instagram.com/p/...).');

            $previous = null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT * FROM instagram_gallery WHERE id=? AND scope=?');
                $stmt->execute([$id, SOCIAL_GALLERY_SCOPE]);
                $previous = $stmt->fetch();
                if (!$previous) throw new RuntimeException('Item galeri tidak ditemukan.');
                if (!empty($previous['media_path'])) portal_delete_uploaded_image($previous['media_path']);
            }

            if ($id) {
                $stmt = $pdo->prepare('UPDATE instagram_gallery SET media_path=NULL,media_type=?,caption=?,instagram_url=?,sort_order=? WHERE id=? AND scope=?');
                $stmt->execute(['embed', $caption ?: null, $instagramUrl, $sortOrder, $id, SOCIAL_GALLERY_SCOPE]);
                portal_log($pdo, 'update_social_gallery', 'Memperbarui link galeri Instagram: ' . ($caption ?: '#' . $id));
            } else {
                $stmt = $pdo->prepare('INSERT INTO instagram_gallery (scope,media_path,media_type,caption,instagram_url,sort_order) VALUES (?,NULL,?,?,?,?)');
                $stmt->execute([SOCIAL_GALLERY_SCOPE, 'embed', $caption ?: null, $instagramUrl, $sortOrder]);
                portal_log($pdo, 'create_social_gallery', 'Menambah link galeri Instagram: ' . ($caption ?: 'tanpa judul'));
            }
            portal_flash('success', 'Link postingan Instagram disimpan.');
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('SELECT media_path,caption FROM instagram_gallery WHERE id=? AND scope=?');
            $stmt->execute([$id, SOCIAL_GALLERY_SCOPE]);
            $item = $stmt->fetch();
            if ($item) {
                $pdo->prepare('DELETE FROM instagram_gallery WHERE id=? AND scope=?')->execute([$id, SOCIAL_GALLERY_SCOPE]);
                if (!empty($item['media_path'])) portal_delete_uploaded_image($item['media_path']);
                portal_log($pdo, 'delete_social_gallery', 'Menghapus item galeri Instagram: ' . ($item['caption'] ?: '#' . $id));
            }
            portal_flash('success', 'Item galeri Instagram dihapus.');
        }
    } catch (Throwable $e) {
        portal_flash('danger', $e->getMessage());
    }
    header('Location: ' . SITE_URL . '/portal/social-gallery');
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM instagram_gallery WHERE id=? AND scope=?');
    $stmt->execute([(int) $_GET['edit'], SOCIAL_GALLERY_SCOPE]);
    $editItem = $stmt->fetch() ?: null;
}
$items = $pdo->prepare('SELECT * FROM instagram_gallery WHERE scope=? ORDER BY sort_order,id');
$items->execute([SOCIAL_GALLERY_SCOPE]);
$items = $items->fetchAll();
$showForm = isset($_GET['new']) || $editItem;
$portalTitle = 'Galeri Instagram';
$portalActive = 'social-gallery';
require __DIR__ . '/../../components/portal-header.php';
?>
<div class="portal-welcome"><div><h2>Galeri Instagram Beranda</h2><p>Tempel link postingan Instagram yang mau ditampilkan - tidak perlu upload foto/video, kontennya diambil langsung dan live dari Instagram. Maksimal <?php echo SOCIAL_GALLERY_MAX; ?> link untuk web yayasan.</p></div><?php if (count($items) < SOCIAL_GALLERY_MAX): ?><a class="portal-action" href="<?php echo SITE_URL; ?>/portal/social-gallery?new=1">+ Tambah Link</a><?php endif; ?></div>

<?php if ($showForm): ?>
<section class="portal-panel" style="margin-bottom:22px"><div class="panel-head"><h3><?php echo $editItem ? 'Edit' : 'Tambah'; ?> Link Postingan</h3><a href="<?php echo SITE_URL; ?>/portal/social-gallery">Tutup</a></div>
<form class="portal-form portal-form-grid" method="post"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?php echo (int) ($editItem['id'] ?? 0); ?>">
<div class="field full"><label>Link postingan Instagram *</label><input type="url" name="instagram_url" value="<?php echo esc($editItem['instagram_url'] ?? ''); ?>" placeholder="https://www.instagram.com/p/..." required></div>
<div class="field full"><label>Keterangan singkat (opsional)</label><input name="caption" value="<?php echo esc($editItem['caption'] ?? ''); ?>" placeholder="Contoh: Kegiatan Market Day TKIT"></div>
<div class="field"><label>Urutan tampil</label><input type="number" min="0" name="sort_order" value="<?php echo (int) ($editItem['sort_order'] ?? count($items)); ?>"></div>
<div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/social-gallery">Batal</a><button class="portal-action">Simpan Link</button></div>
</form>
</section>
<?php endif; ?>

<section class="portal-panel"><div class="panel-head"><h3>Link Galeri (<?php echo count($items); ?>/<?php echo SOCIAL_GALLERY_MAX; ?>)</h3></div><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Keterangan</th><th>Tautan IG</th><th>Aksi</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><strong><?php echo esc($item['caption'] ?: '(tanpa keterangan)'); ?></strong></td><td><?php echo $item['instagram_url'] ? '<a href="' . esc($item['instagram_url']) . '" target="_blank" rel="noopener">' . esc($item['instagram_url']) . '</a>' : '-'; ?></td><td><div class="table-actions"><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/portal/social-gallery?edit=<?php echo (int) $item['id']; ?>">Edit</a><form method="post" onsubmit="return confirm('Hapus link ini?')"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>"><button class="portal-action danger small">Hapus</button></form></div></td></tr><?php endforeach; ?><?php if (!$items): ?><tr><td colspan="3" class="muted">Belum ada link. Tempel link postingan Instagram yang mau ditampilkan.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require __DIR__ . '/../../components/portal-footer.php'; ?>
