<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth(['admin', 'humas']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    portal_verify_csrf();
    try {
        $id=(int)($_POST['id']??0); $stmt=$pdo->prepare('SELECT * FROM brochures WHERE id=?'); $stmt->execute([$id]); $old=$stmt->fetch();
        if(!$old) throw new RuntimeException('Brosur tidak ditemukan.');
        $unitName=trim($_POST['unit_name']??''); $headline=trim($_POST['headline']??''); $description=trim($_POST['description']??'');
        $audience=trim($_POST['audience']??''); $highlights=trim($_POST['highlights']??''); $facilities=trim($_POST['facilities']??''); $schedule=trim($_POST['schedule_info']??'');
        if($unitName===''||$headline===''||$description===''||$highlights===''||$facilities==='') throw new RuntimeException('Nama unit, judul, deskripsi, program unggulan, dan fasilitas wajib diisi.');
        $cover=$old['cover_image']; $fileUrl=$old['file_url'];
        if(!empty($_FILES['cover']['name'])) $cover=portal_upload_image($_FILES['cover'],'brosur-'.$old['unit_slug']);
        if(!empty($_FILES['brochure']['name'])) $fileUrl=portal_upload_brochure($_FILES['brochure']);
        $pdo->prepare('UPDATE brochures SET unit_name=?,headline=?,description=?,audience=?,highlights=?,facilities=?,schedule_info=?,cover_image=?,file_url=?,sort_order=?,is_active=? WHERE id=?')->execute([$unitName,$headline,$description,$audience?:null,$highlights,$facilities,$schedule?:null,$cover?:null,$fileUrl?:null,max(0,(int)($_POST['sort_order']??0)),isset($_POST['is_active'])?1:0,$id]);
        if($old['cover_image']&&$old['cover_image']!==$cover) portal_delete_uploaded_image($old['cover_image']);
        if($old['file_url']&&$old['file_url']!==$fileUrl) portal_delete_uploaded_image($old['file_url']);
        portal_log($pdo,'update_brochure','Memperbarui brosur '.$unitName); portal_flash('success','Brosur unit berhasil diperbarui.');
    } catch(Throwable $e){ portal_flash('danger',$e->getMessage()); }
    header('Location: '.SITE_URL.'/portal/brochures'); exit;
}

$edit=null; if(isset($_GET['edit'])){ $stmt=$pdo->prepare('SELECT * FROM brochures WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $edit=$stmt->fetch()?:null; }
$items=$pdo->query('SELECT * FROM brochures ORDER BY sort_order,id')->fetchAll();
$portalTitle='Brosur Unit'; $portalActive='brochures'; require __DIR__.'/../../components/portal-header.php';
?>
<div class="portal-welcome"><div><h2>Promosi &amp; Brosur Unit</h2><p>Kelola narasi promosi, program unggulan, fasilitas, gambar poster, dan file PDF setiap jenjang.</p></div></div>
<?php if($edit): ?>
<section class="portal-panel" style="margin-bottom:22px"><div class="panel-head"><h3>Edit Brosur <?php echo esc($edit['unit_name']); ?></h3><a href="<?php echo SITE_URL; ?>/portal/brochures">Tutup</a></div>
<form class="portal-form portal-form-grid" method="post" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>">
<div class="field"><label>Nama unit</label><input name="unit_name" value="<?php echo esc($edit['unit_name']); ?>" required></div>
<div class="field"><label>Judul promosi utama</label><input name="headline" value="<?php echo esc($edit['headline']); ?>" required></div>
<div class="field full"><label>Deskripsi promosi</label><textarea name="description" required><?php echo esc($edit['description']); ?></textarea></div>
<div class="field full"><label>Target peserta / orang tua</label><input name="audience" value="<?php echo esc($edit['audience']??''); ?>" placeholder="Contoh: Anak usia 4–6 tahun"></div>
<div class="field"><label>Program unggulan (satu per baris)</label><textarea name="highlights" required><?php echo esc($edit['highlights']??''); ?></textarea></div>
<div class="field"><label>Fasilitas (satu per baris)</label><textarea name="facilities" required><?php echo esc($edit['facilities']??''); ?></textarea></div>
<div class="field full"><label>Informasi jadwal / layanan</label><input name="schedule_info" value="<?php echo esc($edit['schedule_info']??''); ?>" placeholder="Contoh: Senin–Jumat, program full day"></div>
<div class="field"><label>Poster / cover gambar</label><input type="file" name="cover" accept="image/jpeg,image/png,image/webp"><small style="color:var(--portal-muted)">Disarankan portrait 2:3. Upload baru untuk mengganti poster saat ini.</small></div>
<div class="field"><label>File brosur PDF</label><input type="file" name="brochure" accept="application/pdf"><small style="color:var(--portal-muted)"><?php echo $edit['file_url']?'PDF khusus sudah tersedia.':'Poster PDF otomatis tersedia; unggah jika memiliki desain resmi.'; ?></small></div>
<div class="field"><label>Urutan</label><input type="number" min="0" name="sort_order" value="<?php echo (int)$edit['sort_order']; ?>"></div>
<div class="field"><label style="display:flex;gap:8px;align-items:center;text-transform:none"><input style="width:auto" type="checkbox" name="is_active" <?php echo $edit['is_active']?'checked':''; ?>> Tampilkan di website</label></div>
<div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/brochures">Batal</a><button class="portal-action">Simpan Brosur</button></div></form></section>
<?php endif; ?>
<section class="portal-panel"><div class="panel-head"><h3>Empat Brosur Unit</h3></div><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Poster</th><th>Unit</th><th>Promosi</th><th>PDF</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach($items as $item): ?><tr><td><?php if($item['cover_image']): ?><img src="<?php echo esc($item['cover_image']); ?>" alt=""><?php endif; ?></td><td><strong><?php echo esc($item['unit_name']); ?></strong><br><small><?php echo esc($item['unit_slug']); ?></small></td><td><strong><?php echo esc($item['headline']); ?></strong><br><small><?php echo esc(mb_strimwidth($item['description'],0,80,'...')); ?></small></td><td><span class="status active"><?php echo $item['file_url']?'PDF unggahan':'PDF poster otomatis'; ?></span></td><td><span class="status <?php echo $item['is_active']?'active':'inactive'; ?>"><?php echo $item['is_active']?'Tampil':'Nonaktif'; ?></span></td><td><div class="table-actions"><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/portal/brochures?edit=<?php echo (int)$item['id']; ?>">Edit</a><a class="portal-action secondary small" target="_blank" href="<?php echo SITE_URL; ?>/brosur-unit.php?unit=<?php echo urlencode($item['unit_slug']); ?>">Lihat</a></div></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__.'/../../components/portal-footer.php'; ?>
