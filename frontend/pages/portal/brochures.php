<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth(['admin', 'humas']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    portal_verify_csrf();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM brochures WHERE id=?'); $stmt->execute([$id]); $old = $stmt->fetch();
        if (!$old) throw new RuntimeException('Data brosur tidak ditemukan.');
        $unitName = trim($_POST['unit_name'] ?? ''); $headline = trim($_POST['headline'] ?? ''); $description = trim($_POST['description'] ?? '');
        if ($unitName==='' || $headline==='' || $description==='') throw new RuntimeException('Nama unit, judul, dan deskripsi wajib diisi.');
        $cover = $old['cover_image']; $fileUrl = $old['file_url'];
        if (!empty($_FILES['cover']['name'])) $cover = portal_upload_image($_FILES['cover'], 'brosur-cover');
        if (!empty($_FILES['brochure']['name'])) $fileUrl = portal_upload_brochure($_FILES['brochure']);
        $pdo->prepare('UPDATE brochures SET unit_name=?,headline=?,description=?,cover_image=?,file_url=?,sort_order=?,is_active=? WHERE id=?')->execute([$unitName,$headline,$description,$cover?:null,$fileUrl?:null,max(0,(int)($_POST['sort_order']??0)),isset($_POST['is_active'])?1:0,$id]);
        if ($old['cover_image'] && $old['cover_image'] !== $cover) portal_delete_uploaded_image($old['cover_image']);
        if ($old['file_url'] && $old['file_url'] !== $fileUrl) portal_delete_uploaded_image($old['file_url']);
        portal_log($pdo,'update_brochure','Memperbarui brosur '.$unitName); portal_flash('success','Brosur unit berhasil diperbarui.');
    } catch(Throwable $e) { portal_flash('danger',$e->getMessage()); }
    header('Location: '.SITE_URL.'/portal/brochures'); exit;
}
$edit = null; if(isset($_GET['edit'])) { $stmt=$pdo->prepare('SELECT * FROM brochures WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $edit=$stmt->fetch()?:null; }
$items=$pdo->query('SELECT * FROM brochures ORDER BY sort_order,id')->fetchAll();
$portalTitle='Brosur Unit'; $portalActive='brochures'; require __DIR__.'/../../components/portal-header.php';
?>
<div class="portal-welcome"><div><h2>Brosur Per Unit</h2><p>Setiap unit memiliki halaman informasi sendiri. Upload PDF agar tombol publik langsung mengunduh brosur.</p></div></div>
<?php if($edit): ?><section class="portal-panel" style="margin-bottom:22px"><div class="panel-head"><h3>Edit Brosur <?php echo esc($edit['unit_name']); ?></h3><a href="<?php echo SITE_URL; ?>/portal/brochures">Tutup</a></div><form class="portal-form portal-form-grid" method="post" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>"><div class="field"><label>Nama unit</label><input name="unit_name" value="<?php echo esc($edit['unit_name']); ?>" required></div><div class="field"><label>Judul utama</label><input name="headline" value="<?php echo esc($edit['headline']); ?>" required></div><div class="field"><label>Cover gambar</label><input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></div><div class="field"><label>File brosur PDF</label><input type="file" name="brochure" accept="application/pdf"><small style="color:var(--portal-muted)"><?php echo $edit['file_url']?'PDF sudah tersedia. Upload baru untuk mengganti.':'Belum ada PDF.'; ?></small></div><div class="field full"><label>Penjelasan unit</label><textarea name="description" required><?php echo esc($edit['description']); ?></textarea></div><div class="field"><label>Urutan</label><input type="number" min="0" name="sort_order" value="<?php echo (int)$edit['sort_order']; ?>"></div><div class="field"><label style="display:flex;gap:8px;align-items:center;text-transform:none"><input style="width:auto" type="checkbox" name="is_active" <?php echo $edit['is_active']?'checked':''; ?>> Tampilkan di website</label></div><div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/brochures">Batal</a><button class="portal-action">Simpan Brosur</button></div></form></section><?php endif; ?>
<section class="portal-panel"><div class="panel-head"><h3>Daftar Unit</h3></div><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Cover</th><th>Unit</th><th>Judul</th><th>PDF</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach($items as $item): ?><tr><td><?php if($item['cover_image']): ?><img src="<?php echo esc($item['cover_image']); ?>" alt=""><?php endif; ?></td><td><strong><?php echo esc($item['unit_name']); ?></strong><br><small><?php echo esc($item['unit_slug']); ?></small></td><td><?php echo esc($item['headline']); ?></td><td><span class="status <?php echo $item['file_url']?'active':'inactive'; ?>"><?php echo $item['file_url']?'Siap diunduh':'Belum diunggah'; ?></span></td><td><span class="status <?php echo $item['is_active']?'active':'inactive'; ?>"><?php echo $item['is_active']?'Tampil':'Nonaktif'; ?></span></td><td><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/portal/brochures?edit=<?php echo (int)$item['id']; ?>">Edit</a></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__.'/../../components/portal-footer.php'; ?>
