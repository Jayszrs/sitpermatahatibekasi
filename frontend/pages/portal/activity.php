<?php
require_once __DIR__.'/../../../backend/config/database.php';
require_once __DIR__.'/../../../backend/helpers/functions.php';
require_once __DIR__.'/../../../backend/auth.php';
portal_require_auth(['admin']);
admin_audit_import_portal($pdo);
$units=school_admin_units();$args=[];$where=['1=1'];
$filters=[];foreach(['q','scope','unit','outcome','from','to'] as $key)$filters[$key]=trim((string)($_GET[$key]??''));
if($filters['q']!==''){$where[]='(actor_username LIKE ? OR action LIKE ? OR description LIKE ?)';for($i=0;$i<3;$i++)$args[]='%'.$filters['q'].'%';}
if(in_array($filters['scope'],['portal','unit'],true)){$where[]='actor_scope=?';$args[]=$filters['scope'];}
if(isset($units[$filters['unit']])){$where[]='unit_slug=?';$args[]=$filters['unit'];}
if(in_array($filters['outcome'],['success','failure'],true)){$where[]='outcome=?';$args[]=$filters['outcome'];}
foreach(['from'=>'>=','to'=>'<='] as $key=>$operator){
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$filters[$key]);
    if($date && $date->format('Y-m-d')===$filters[$key]){$where[]="created_at $operator ?";$args[]=$filters[$key].($key==='from'?' 00:00:00':' 23:59:59');}
}
$clause=implode(' AND ',$where);
$count=$pdo->prepare('SELECT COUNT(*) FROM admin_audit_events WHERE '.$clause);$count->execute($args);$total=(int)$count->fetchColumn();
$page=min(max(1,(int)($_GET['page']??1)),max(1,(int)ceil($total/50)));$offset=($page-1)*50;
if(isset($_GET['export'])){
    portal_log($pdo,'export_audit','Mengunduh audit aktivitas sesuai filter');
    header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="audit-admin-'.date('Ymd-His').'.csv"');
    $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
    fputcsv($out,['Waktu (Asia/Jakarta)','Portal','Akun','Role','Unit','Tindakan','Hasil','Keterangan'],',','"','');
    // Cursor pagination keeps export memory bounded, including large audit histories.
    $cursor=PHP_INT_MAX;
    do{
        $query=$pdo->prepare('SELECT * FROM admin_audit_events WHERE '.$clause.' AND id<? ORDER BY id DESC LIMIT 500');$query->execute([...$args,$cursor]);$batch=$query->fetchAll();
        foreach($batch as $row){
            $cells=[$row['created_at'],$row['actor_scope'],$row['actor_username'],$row['actor_role'],$row['unit_slug']??'Yayasan / Semua',$row['action'],$row['outcome'],$row['description']];
            $cells=array_map(static fn($value)=>preg_match('/^[\s]*[=+@-]/u',(string)$value)?"'".$value:$value,$cells);
            fputcsv($out,$cells,',','"','');$cursor=(int)$row['id'];
        }
    }while(count($batch)===500);
    fclose($out);exit;
}
$query=$pdo->prepare('SELECT * FROM admin_audit_events WHERE '.$clause." ORDER BY id DESC LIMIT 50 OFFSET $offset");$query->execute($args);$rows=$query->fetchAll();
$portalTitle='Audit Aktivitas Admin';$portalActive='activity';require __DIR__.'/../../components/portal-header.php';
$url=SITE_URL.'/portal/activity';
?>
<div class="portal-welcome"><div><h2>Aktivitas Yayasan &amp; Semua Unit</h2><p>Riwayat login, akses menu, perubahan data, unduhan CV, serta pengelolaan pengguna dan role.</p></div><a class="portal-action" href="<?php echo esc($url.'?'.http_build_query([...$filters,'export'=>1])); ?>">Unduh CSV</a></div>
<section class="portal-panel"><p>Aktivitas unit mulai tercatat sejak fitur ini dipasang. Riwayat yayasan yang sebelumnya tersedia ikut ditampilkan. Password dan isi dokumen tidak dicatat.</p>
<form class="user-filters" method="get">
<label>Cari akun / tindakan<input name="q" value="<?php echo esc($filters['q']); ?>"></label>
<label>Portal<select name="scope"><option value="">Semua Portal</option><option value="portal" <?php echo $filters['scope']==='portal'?'selected':''; ?>>Yayasan</option><option value="unit" <?php echo $filters['scope']==='unit'?'selected':''; ?>>CMS Unit</option></select></label>
<label>Unit<select name="unit"><option value="">Semua Unit</option><?php foreach($units as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo $filters['unit']===$key?'selected':''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></label>
<label>Hasil<select name="outcome"><option value="">Semua Hasil</option><option value="success" <?php echo $filters['outcome']==='success'?'selected':''; ?>>Berhasil</option><option value="failure" <?php echo $filters['outcome']==='failure'?'selected':''; ?>>Ditolak / Gagal</option></select></label>
<label>Dari tanggal<input type="date" name="from" value="<?php echo esc($filters['from']); ?>"></label><label>Sampai tanggal<input type="date" name="to" value="<?php echo esc($filters['to']); ?>"></label>
<button class="portal-action secondary">Terapkan Filter</button><a class="portal-action secondary" href="<?php echo $url; ?>">Reset</a></form></section>
<section class="portal-panel"><h3><?php echo $total; ?> Aktivitas</h3><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Waktu (WIB)</th><th>Pelaku</th><th>Portal / Unit</th><th>Tindakan</th><th>Hasil</th><th>Keterangan</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr><td><?php echo esc($row['created_at']); ?></td><td><strong><?php echo esc($row['actor_username']); ?></strong><br><?php echo esc($row['actor_role']); ?></td><td><?php echo $row['actor_scope']==='portal'?'Yayasan':'CMS Unit'; ?><br><?php echo esc($units[$row['unit_slug']]??'Semua / Yayasan'); ?></td><td><?php echo esc($row['action']); ?></td><td><?php echo $row['outcome']==='success'?'Berhasil':'Ditolak / Gagal'; ?></td><td><?php echo esc($row['description']); ?></td></tr><?php endforeach;if(!$rows): ?><tr><td colspan="6">Belum ada aktivitas yang sesuai filter.</td></tr><?php endif; ?></tbody></table></div>
<div class="table-actions"><?php if($page>1): ?><a class="portal-action secondary" href="<?php echo esc($url.'?'.http_build_query([...$filters,'page'=>$page-1])); ?>">Sebelumnya</a><?php endif; ?><span>Halaman <?php echo $page; ?> / <?php echo max(1,(int)ceil($total/50)); ?></span><?php if($page*50<$total): ?><a class="portal-action secondary" href="<?php echo esc($url.'?'.http_build_query([...$filters,'page'=>$page+1])); ?>">Berikutnya</a><?php endif; ?></div></section>
<?php require __DIR__.'/../../components/portal-footer.php'; ?>
