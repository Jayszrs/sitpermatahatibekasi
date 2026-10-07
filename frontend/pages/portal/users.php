<?php
require_once __DIR__.'/../../../backend/config/database.php';
require_once __DIR__.'/../../../backend/helpers/functions.php';
require_once __DIR__.'/../../../backend/auth.php';
portal_require_auth(['admin']);
require_once __DIR__.'/../../../backend/config/unit_database.php';
require_once __DIR__.'/../../../backend/helpers/central_users.php';
$unitPdo=unit_database_connection();unit_access_schema($unitPdo);
$currentUser=portal_user();$units=school_admin_units();
$scope=(string)($_GET['scope']??'portal');if(!in_array($scope,['portal','unit','roles'],true))$scope='portal';
$base=SITE_URL.'/portal/users';
if($_SERVER['REQUEST_METHOD']==='POST'){
    portal_verify_csrf();$action=(string)($_POST['action']??'');
    try{
        if($action==='save_user' || $action==='toggle_user'){
            $target=(string)($_POST['scope']??'portal');
            if(!in_array($target,['portal','unit'],true))throw new RuntimeException('Lingkup akun tidak valid.');
            $connection=$target==='unit'?$unitPdo:$pdo;$table=$target==='unit'?'unit_users':'portal_users';
            $input=$_POST;
            if($action==='toggle_user'){
                $q=$connection->prepare("SELECT * FROM $table WHERE id=?");$q->execute([(int)($_POST['id']??0)]);$row=$q->fetch();
                if(!$row)throw new RuntimeException('Akun tidak ditemukan.');
                $input=['id'=>$row['id'],'name'=>$row['name']?:$row['username'],'username'=>$row['username'],'role'=>$row['role'],
                    'role_key'=>$row['role_key']??$row['role'],'user_unit'=>$row['unit_slug']??'','is_active'=>!$row['is_active'],'password'=>''];
            }
            $id=$target==='unit'?admin_save_unit_user($unitPdo,$input):central_save_portal_user($pdo,$input,$currentUser);
            portal_log($pdo,$action,($target==='unit'?'Akun unit':'Akun yayasan').' #'.$id.' / '.(string)$input['username'],$target==='unit'&&isset($units[$input['user_unit']??''])?$input['user_unit']:null);
            portal_flash('success','Akun berhasil disimpan. Perubahan akses berlaku pada permintaan berikutnya.');
            $scope=$target;
        }elseif($action==='save_role'){
            $key=central_save_role($unitPdo,$_POST);portal_log($pdo,'save_unit_role','Menyimpan role unit: '.$key);
            portal_flash('success','Role disimpan. Pengguna role ini perlu login kembali.');$scope='roles';
        }elseif($action==='delete_role'){
            $key=(string)($_POST['role_key']??'');central_delete_role($unitPdo,$key);portal_log($pdo,'delete_unit_role','Menghapus role unit: '.$key);
            portal_flash('success','Role dihapus.');$scope='roles';
        }else throw new RuntimeException('Tindakan tidak dikenal.');
    }catch(PDOException $e){portal_flash('danger',$e->getCode()==='23000'?'Username sudah dipakai.':'Perubahan gagal disimpan.');}
    catch(Throwable $e){portal_flash('danger',$e->getMessage());}
    header('Location: '.$base.'?scope='.rawurlencode($scope));exit;
}
$roles=$unitPdo->query('SELECT * FROM unit_roles ORDER BY is_system DESC,name')->fetchAll();
$roleLabels=['superadmin'=>'Superadmin Semua Unit'];foreach($roles as $r)$roleLabels[$r['role_key']]=$r['name'];
$portalRoles=['admin'=>'Superadmin Yayasan','humas'=>'Humas Yayasan','kasir'=>'Kasir SPMB'];
$editUser=null;$editRole=null;
if(isset($_GET['edit']) && $scope!=='roles'){
    $connection=$scope==='unit'?$unitPdo:$pdo;$table=$scope==='unit'?'unit_users':'portal_users';
    $q=$connection->prepare("SELECT * FROM $table WHERE id=?");$q->execute([(int)$_GET['edit']]);$editUser=$q->fetch()?:null;
    if(!$editUser){http_response_code(404);exit('Akun tidak ditemukan.');}
}
if(isset($_GET['edit_role'])){foreach($roles as $r)if($r['role_key']===$_GET['edit_role'] && !$r['is_system'])$editRole=$r;}
$search=trim((string)($_GET['q']??''));$filterUnit=(string)($_GET['unit']??'');
$users=[];$total=0;$page=max(1,(int)($_GET['page']??1));$perPage=30;
if($scope!=='roles'){
    $connection=$scope==='unit'?$unitPdo:$pdo;$table=$scope==='unit'?'unit_users':'portal_users';$where=['1=1'];$args=[];
    if($search!==''){$where[]='(username LIKE ? OR name LIKE ?)';$args[]='%'.$search.'%';$args[]='%'.$search.'%';}
    if($scope==='unit' && isset($units[$filterUnit])){$where[]='unit_slug=?';$args[]=$filterUnit;}
    $clause=implode(' AND ',$where);$q=$connection->prepare("SELECT COUNT(*) FROM $table WHERE $clause");$q->execute($args);$total=(int)$q->fetchColumn();
    $page=min($page,max(1,(int)ceil($total/$perPage)));$offset=($page-1)*$perPage;
    $q=$connection->prepare("SELECT * FROM $table WHERE $clause ORDER BY is_active DESC,username LIMIT $perPage OFFSET $offset");$q->execute($args);$users=$q->fetchAll();
}
$portalTitle='Manajemen Pengguna';$portalActive='users';require __DIR__.'/../../components/portal-header.php';
?>
<div class="portal-welcome"><div><h2>Pengguna &amp; Hak Akses</h2><p>Kelola akun yayasan, empat unit, dan role dari satu tempat.</p></div><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/activity">Audit Aktivitas</a></div>
<nav class="content-tabs" aria-label="Lingkup pengguna"><?php foreach(['portal'=>'Akun Yayasan','unit'=>'Akun Semua Unit','roles'=>'Role Unit'] as $key=>$label): ?><a class="<?php echo $scope===$key?'active':''; ?>" href="<?php echo $base.'?scope='.$key; ?>"><?php echo esc($label); ?></a><?php endforeach; ?></nav>
<?php if($scope==='roles'): ?>
<section class="portal-panel"><h3><?php echo $editRole?'Ubah Role':'Tambah Role Unit'; ?></h3><p>Role khusus dapat dipakai di seluruh unit. Akun tetap dibatasi ke unit yang ditugaskan. Dashboard selalu tersedia.</p>
<form method="post" class="portal-form portal-form-grid">
<input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save_role"><input type="hidden" name="editing" value="<?php echo $editRole?1:0; ?>">
<div class="field"><label>Kode role<input name="role_key" required pattern="[a-z][a-z0-9_-]{2,59}" value="<?php echo esc($editRole['role_key']??''); ?>" <?php echo $editRole?'readonly':''; ?> placeholder="editor_kegiatan"></label></div>
<div class="field"><label>Nama role<input name="name" required maxlength="100" value="<?php echo esc($editRole['name']??''); ?>" placeholder="Editor Kegiatan"></label></div>
<fieldset class="field full"><legend>Menu yang boleh dibuka dan dikelola</legend><div class="permission-grid"><?php $selected=json_decode($editRole['permissions']??'["dashboard"]',true);foreach(unit_permission_labels() as $key=>$label): ?><label><input type="checkbox" name="permissions[]" value="<?php echo $key; ?>" <?php echo in_array($key,$selected,true)?'checked':''; ?> <?php echo $key==='dashboard'?'disabled':''; ?>> <?php echo esc($label); ?></label><?php endforeach; ?></div></fieldset>
<div class="form-actions field full"><button class="portal-action">Simpan Role</button><a class="portal-action secondary" href="<?php echo $base; ?>?scope=roles">Batal / Bersihkan</a></div></form></section>
<section class="portal-panel"><h3>Role yang Tersedia</h3><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Role</th><th>Hak akses</th><th>Aksi</th></tr></thead><tbody>
<tr><td>Superadmin Semua Unit</td><td>Seluruh menu dan seluruh unit</td><td>Bawaan</td></tr>
<?php foreach($roles as $r): ?><tr><td><strong><?php echo esc($r['name']); ?></strong><br><code><?php echo esc($r['role_key']); ?></code></td><td><?php echo esc(implode(', ',array_map(fn($key)=>unit_permission_labels()[$key]??$key,json_decode($r['permissions'],true)))); ?></td><td><?php if($r['is_system']): ?>Bawaan<?php else: ?><div class="table-actions"><a class="portal-action secondary small" href="<?php echo $base.'?scope=roles&edit_role='.rawurlencode($r['role_key']); ?>">Ubah</a><form method="post"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="delete_role"><input type="hidden" name="role_key" value="<?php echo esc($r['role_key']); ?>"><button class="portal-action danger small" data-confirm="Hapus role ini? Hanya role yang tidak dipakai akun yang dapat dihapus.">Hapus</button></form></div><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php else: ?>
<section class="portal-panel"><form method="get" class="user-filters"><input type="hidden" name="scope" value="<?php echo $scope; ?>"><label>Cari nama atau ID<input name="q" value="<?php echo esc($search); ?>"></label><?php if($scope==='unit'): ?><label>Unit<select name="unit"><option value="">Semua Unit &amp; Superadmin</option><?php foreach($units as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo $filterUnit===$key?'selected':''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></label><?php endif; ?><button class="portal-action secondary">Cari</button><a class="portal-action secondary" href="<?php echo $base.'?scope='.$scope; ?>">Reset</a><a class="portal-action" href="<?php echo $base.'?scope='.$scope.'&new=1'; ?>">+ Tambah Akun</a></form></section>
<?php if(isset($_GET['new'])||$editUser): ?>
<section class="portal-panel"><h3><?php echo $editUser?'Ubah Akun / Reset Password':'Akun Baru'; ?></h3><p><?php echo $scope==='unit'?'Akun ini login melalui CMS unit yang ditugaskan.':'Superadmin Yayasan memiliki akses penuh, termasuk semua akun unit dan audit.'; ?></p>
<form method="post" class="portal-form portal-form-grid"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save_user"><input type="hidden" name="scope" value="<?php echo $scope; ?>"><input type="hidden" name="id" value="<?php echo (int)($editUser['id']??0); ?>">
<div class="field"><label>Nama petugas<input name="name" required maxlength="120" value="<?php echo esc($editUser['name']??$editUser['username']??''); ?>"></label></div>
<div class="field"><label>ID / Username<input name="username" required pattern="[a-z0-9._-]{3,30}" value="<?php echo esc($editUser['username']??''); ?>" autocomplete="off"></label></div>
<div class="field"><label>Role<select name="<?php echo $scope==='unit'?'role_key':'role'; ?>" id="accountRole"><?php foreach($scope==='unit'?$roleLabels:$portalRoles as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo ($editUser['role_key']??$editUser['role']??($scope==='unit'?'unit_admin':'humas'))===$key?'selected':''; ?>><?php echo esc($label); ?></option><?php endforeach; ?></select></label></div>
<?php if($scope==='unit'): ?><div class="field"><label>Penugasan unit<select name="user_unit" id="accountUnit"><?php foreach($units as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo ($editUser['unit_slug']??'')===$key?'selected':''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></label><small>Superadmin otomatis mendapat akses semua unit.</small></div><?php endif; ?>
<div class="field"><label>Password <?php echo $editUser?'baru (kosongkan jika tetap)':''; ?><input name="password" type="password" minlength="8" autocomplete="new-password" <?php echo $editUser?'':'required'; ?>></label></div>
<div class="field"><label>Status<select name="is_active"><option value="1" <?php echo !$editUser||$editUser['is_active']?'selected':''; ?>>Aktif</option><option value="0" <?php echo $editUser&&!$editUser['is_active']?'selected':''; ?>>Nonaktif</option></select></label></div>
<div class="form-actions field full"><button class="portal-action">Simpan Akun</button><a class="portal-action secondary" href="<?php echo $base.'?scope='.$scope; ?>">Batal</a></div></form></section><?php endif; ?>
<section class="portal-panel"><h3>Daftar Pengguna (<?php echo $total; ?>)</h3><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Nama / ID</th><th>Role &amp; Unit</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($users as $item): ?><tr><td><strong><?php echo esc($item['name']?:$item['username']); ?></strong><br><code><?php echo esc($item['username']); ?></code></td><td><?php echo esc($scope==='unit'?($roleLabels[$item['role_key']?:$item['role']]??'Role tidak ditemukan'):$portalRoles[$item['role']]); ?><br><small><?php echo esc($scope==='unit'?($units[$item['unit_slug']]??'Semua Unit'):'Yayasan'); ?></small></td><td><?php echo $item['is_active']?'Aktif':'Nonaktif'; ?></td><td><?php echo esc($item['last_login_at']??'-'); ?></td><td><div class="table-actions"><a class="portal-action secondary small" href="<?php echo $base.'?scope='.$scope.'&edit='.(int)$item['id']; ?>">Ubah / Password</a><?php if($scope==='unit'||(int)$item['id']!==$currentUser['id']): ?><form method="post"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="toggle_user"><input type="hidden" name="scope" value="<?php echo $scope; ?>"><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>"><button class="portal-action secondary small"><?php echo $item['is_active']?'Nonaktifkan':'Aktifkan'; ?></button></form><?php endif; ?></div></td></tr><?php endforeach;if(!$users): ?><tr><td colspan="5">Tidak ada akun yang sesuai filter.</td></tr><?php endif; ?></tbody></table></div>
<div class="table-actions"><?php for($p=1;$p<=max(1,(int)ceil($total/$perPage));$p++): ?><a class="portal-action secondary small" aria-label="Halaman <?php echo $p; ?>" href="<?php echo esc($base.'?'.http_build_query(['scope'=>$scope,'q'=>$search,'unit'=>$filterUnit,'page'=>$p])); ?>"><?php echo $p===$page?'['.$p.']':$p; ?></a><?php endfor; ?></div></section>
<?php endif; ?>
<script>document.querySelectorAll('[data-confirm]').forEach(function(button){button.addEventListener('click',function(e){if(!confirm(button.dataset.confirm))e.preventDefault();});});(function(){var role=document.getElementById('accountRole'),unit=document.getElementById('accountUnit');if(!role||!unit)return;function update(){unit.disabled=role.value==='superadmin';}role.addEventListener('change',update);update();})();</script>
<?php require __DIR__.'/../../components/portal-footer.php'; ?>
