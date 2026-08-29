<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth(['admin', 'humas']);

$units = ['daycare' => 'Daycare', 'tkit' => 'TKIT', 'sdit' => 'SDIT', 'smpit' => 'SMPIT'];
$activeUnit = strtolower(trim($_GET['unit'] ?? 'daycare'));
if (!isset($units[$activeUnit])) $activeUnit = 'daycare';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    portal_verify_csrf();
    $action = $_POST['action'] ?? '';
    $unitSlug = strtolower(trim($_POST['unit_slug'] ?? $activeUnit));
    if (!isset($units[$unitSlug])) $unitSlug = 'daycare';
    try {
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $sortOrder = max(0, (int)($_POST['sort_order'] ?? 0));
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if ($title === '') throw new RuntimeException('Judul foto wajib diisi.');
            $oldImage = null; $image = null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT image FROM unit_gallery_photos WHERE id=?');
                $stmt->execute([$id]); $oldImage = $stmt->fetchColumn() ?: null; $image = $oldImage;
            }
            if (!empty($_FILES['image']['name'])) $image = portal_upload_image($_FILES['image'], 'unit-'.$unitSlug);
            if (!$image) throw new RuntimeException('Pilih gambar untuk galeri unit.');
            if ($id) {
                $stmt=$pdo->prepare('UPDATE unit_gallery_photos SET unit_slug=?,title=?,description=?,image=?,sort_order=?,is_active=? WHERE id=?');
                $stmt->execute([$unitSlug,$title,$description?:null,$image,$sortOrder,$isActive,$id]);
                if ($oldImage && $oldImage !== $image) portal_delete_uploaded_image($oldImage);
            } else {
                $stmt=$pdo->prepare('INSERT INTO unit_gallery_photos (unit_slug,title,description,image,sort_order,is_active) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$unitSlug,$title,$description?:null,$image,$sortOrder,$isActive]);
            }
            portal_log($pdo, $id ? 'update_unit_photo' : 'create_unit_photo', 'Galeri '.$units[$unitSlug].': '.$title);
            portal_flash('success', 'Foto galeri unit berhasil disimpan.');
        } elseif ($action === 'delete') {
            $id=(int)($_POST['id']??0); $stmt=$pdo->prepare('SELECT image,title FROM unit_gallery_photos WHERE id=?'); $stmt->execute([$id]); $item=$stmt->fetch();
            if($item){ $pdo->prepare('DELETE FROM unit_gallery_photos WHERE id=?')->execute([$id]); portal_delete_uploaded_image($item['image']); portal_log($pdo,'delete_unit_photo','Menghapus foto unit: '.$item['title']); }
            portal_flash('success','Foto galeri unit berhasil dihapus.');
        }
    } catch (Throwable $e) { portal_flash('danger',$e->getMessage()); }
    header('Location: '.SITE_URL.'/portal/unit-gallery?unit='.urlencode($unitSlug)); exit;
}

$edit=null;
if(isset($_GET['edit'])){ $stmt=$pdo->prepare('SELECT * FROM unit_gallery_photos WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $edit=$stmt->fetch()?:null; if($edit&&isset($units[$edit['unit_slug']])) $activeUnit=$edit['unit_slug']; }
$stmt=$pdo->prepare('SELECT * FROM unit_gallery_photos WHERE unit_slug=? ORDER BY sort_order,id'); $stmt->execute([$activeUnit]); $photos=$stmt->fetchAll();
$portalTitle='Galeri Unit'; $portalActive='unit-gallery'; require __DIR__.'/../../components/portal-header.php';
?>
<div class="portal-welcome"><div><h2>Galeri Unit Tanpa Batas</h2><p>Tambahkan foto sebanyak yang dibutuhkan. Website akan menyusunnya sebagai kolase dan membuka setiap foto dalam preview.</p></div><a class="portal-action" href="<?php echo SITE_URL; ?>/portal/unit-gallery?unit=<?php echo esc($activeUnit); ?>&new=1">+ Tambah Foto</a></div>
<div class="content-tabs"><?php foreach($units as $slug=>$label): ?><a class="<?php echo $activeUnit===$slug?'active':''; ?>" href="<?php echo SITE_URL; ?>/portal/unit-gallery?unit=<?php echo $slug; ?>"><?php echo esc($label); ?></a><?php endforeach; ?></div>
<?php if(isset($_GET['new'])||$edit): ?>
<section class="portal-panel" style="margin-bottom:22px"><div class="panel-head"><h3><?php echo $edit?'Edit':'Tambah'; ?> Foto <?php echo esc($units[$activeUnit]); ?></h3><a href="<?php echo SITE_URL; ?>/portal/unit-gallery?unit=<?php echo esc($activeUnit); ?>">Tutup</a></div>
<form class="portal-form portal-form-grid" method="post" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?php echo (int)($edit['id']??0); ?>">
<div class="field"><label>Unit sekolah</label><select name="unit_slug"><?php foreach($units as $slug=>$label): ?><option value="<?php echo $slug; ?>" <?php echo (($edit['unit_slug']??$activeUnit)===$slug)?'selected':''; ?>><?php echo esc($label); ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Judul foto</label><input name="title" value="<?php echo esc($edit['title']??''); ?>" placeholder="Contoh: Ruang Kelas Kreatif" required></div>
<div class="field"><label>File gambar <?php echo $edit?'(kosongkan jika tetap)':''; ?></label><input type="file" name="image" accept="image/jpeg,image/png,image/webp" <?php echo $edit?'':'required'; ?>><small style="color:var(--portal-muted)">JPG, PNG, atau WebP maksimal 5 MB.</small></div>
<div class="field"><label>Urutan tampil</label><input type="number" min="0" name="sort_order" value="<?php echo (int)($edit['sort_order']??count($photos)+1); ?>"></div>
<div class="field full"><label>Keterangan foto</label><textarea name="description" placeholder="Ceritakan fasilitas atau aktivitas yang terlihat."><?php echo esc($edit['description']??''); ?></textarea></div>
<div class="field full"><label style="display:flex;align-items:center;gap:8px;text-transform:none"><input style="width:auto" type="checkbox" name="is_active" <?php echo !isset($edit['is_active'])||$edit['is_active']?'checked':''; ?>> Tampilkan di website</label></div>
<div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/unit-gallery?unit=<?php echo esc($activeUnit); ?>">Batal</a><button class="portal-action">Simpan Foto</button></div></form></section>
<?php endif; ?>
<section class="portal-panel"><div class="panel-head"><h3>Foto <?php echo esc($units[$activeUnit]); ?> (<?php echo count($photos); ?>)</h3></div><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Preview</th><th>Judul</th><th>Keterangan</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php if(!$photos): ?><tr><td colspan="6" class="empty-state">Belum ada foto untuk unit ini.</td></tr><?php endif; ?><?php foreach($photos as $photo): ?><tr><td><img src="<?php echo esc($photo['image']); ?>" alt=""></td><td><strong><?php echo esc($photo['title']); ?></strong></td><td><?php echo esc(mb_strimwidth($photo['description']??'',0,80,'...')); ?></td><td><?php echo (int)$photo['sort_order']; ?></td><td><span class="status <?php echo $photo['is_active']?'active':'inactive'; ?>"><?php echo $photo['is_active']?'Tampil':'Nonaktif'; ?></span></td><td><div class="table-actions"><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/portal/unit-gallery?unit=<?php echo esc($activeUnit); ?>&edit=<?php echo (int)$photo['id']; ?>">Edit</a><form method="post" onsubmit="return confirm('Hapus foto ini?')"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="unit_slug" value="<?php echo esc($activeUnit); ?>"><input type="hidden" name="id" value="<?php echo (int)$photo['id']; ?>"><button class="portal-action danger small">Hapus</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__.'/../../components/portal-footer.php'; ?>
