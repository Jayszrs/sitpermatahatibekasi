<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth(['admin', 'kasir']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    portal_verify_csrf();
    try {
        $action = $_POST['action'] ?? 'update_registration';
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new RuntimeException('Data pendaftar tidak valid.');
        if ($action === 'update_registration') {
            $studentName=trim($_POST['student_name']??''); $parentName=trim($_POST['parent_name']??''); $whatsapp=trim($_POST['whatsapp']??'');
            $email=trim($_POST['email']??'');
            $level=$_POST['level']??''; $registrationStatus=$_POST['registration_status']??'baru'; $documentStatus=$_POST['document_status']??'belum_lengkap'; $paymentStatus=$_POST['payment_status']??'belum_bayar';
            if ($studentName===''||$parentName===''||$whatsapp===''||!in_array($level,['SD','SMP','SMA'],true)) throw new RuntimeException('Nama siswa, orang tua, WhatsApp, dan jenjang wajib diisi.');
            if ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Format email pendaftar tidak valid.');
            if (!in_array($registrationStatus,['baru','verifikasi','lulus','cadangan','ditolak','daftar_ulang'],true)||!in_array($documentStatus,['belum_lengkap','lengkap','terverifikasi'],true)||!in_array($paymentStatus,['belum_bayar','sebagian','lunas'],true)) throw new RuntimeException('Status data tidak valid.');
            $stmt=$pdo->prepare('UPDATE spmb_registrations SET registration_number=?,student_name=?,student_nik=?,gender=?,birth_place=?,birth_date=?,parent_name=?,parent_nik=?,family_card_number=?,whatsapp=?,email=?,level=?,previous_school=?,address=?,registration_status=?,document_status=?,payment_status=?,payment_notes=?,payment_updated_by=? WHERE id=?');
            $stmt->execute([trim($_POST['registration_number']??'')?:null,$studentName,trim($_POST['student_nik']??'')?:null,($_POST['gender']??'')?:null,trim($_POST['birth_place']??'')?:null,($_POST['birth_date']??'')?:null,$parentName,trim($_POST['parent_nik']??'')?:null,trim($_POST['family_card_number']??'')?:null,$whatsapp,$email?:null,$level,trim($_POST['previous_school']??'')?:null,trim($_POST['address']??'')?:null,$registrationStatus,$documentStatus,$paymentStatus,trim($_POST['payment_notes']??'')?:null,portal_user()['id'],$id]);
            portal_log($pdo,'update_registration','Memperbarui data dan status SPMB ' . $studentName);
            portal_flash('success','Data pendaftar SPMB berhasil diperbarui.');
        } elseif ($action === 'add_payment') {
            $amount=(float)($_POST['amount']??0); $method=trim($_POST['payment_method']??''); $date=$_POST['payment_date']??''; $type=trim($_POST['payment_type']??'');
            if ($amount<=0||$method===''||$date===''||$type==='') throw new RuntimeException('Jenis, nominal, metode, dan tanggal pembayaran wajib diisi.');
            $receipt=trim($_POST['receipt_number']??'') ?: 'PHB-'.date('Ymd').'-'.str_pad((string)$id,4,'0',STR_PAD_LEFT).'-'.date('His');
            $stmt=$pdo->prepare('INSERT INTO spmb_payments (registration_id,receipt_number,payment_type,amount,payment_method,payment_date,reference_number,payer_name,notes,recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$id,$receipt,$type,$amount,$method,$date,trim($_POST['reference_number']??'')?:null,trim($_POST['payer_name']??'')?:null,trim($_POST['notes']??'')?:null,portal_user()['id']]);
            $totalStmt=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM spmb_payments WHERE registration_id=? AND status='verified'"); $totalStmt->execute([$id]); $total=$totalStmt->fetchColumn();
            $after=$_POST['payment_status_after']??'sebagian'; if(!in_array($after,['sebagian','lunas'],true)) $after='sebagian';
            $pdo->prepare('UPDATE spmb_registrations SET payment_amount=?,payment_status=?,payment_method=?,payment_date=?,payment_updated_by=? WHERE id=?')->execute([$total,$after,$method,$date,portal_user()['id'],$id]);
            portal_log($pdo,'add_payment','Mencatat transaksi '.$receipt.' sebesar Rp '.number_format($amount,0,',','.'));
            portal_flash('success','Transaksi pembayaran berhasil dicatat dengan nomor kuitansi '.$receipt.'.');
        } elseif ($action === 'cancel_payment') {
            $paymentId=(int)($_POST['payment_id']??0); $pdo->prepare("UPDATE spmb_payments SET status='cancelled' WHERE id=? AND registration_id=?")->execute([$paymentId,$id]);
            $totalStmt=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM spmb_payments WHERE registration_id=? AND status='verified'"); $totalStmt->execute([$id]); $total=(float)$totalStmt->fetchColumn();
            $pdo->prepare("UPDATE spmb_registrations SET payment_amount=?,payment_status=IF(?=0,'belum_bayar','sebagian'),payment_updated_by=? WHERE id=?")->execute([$total,$total,portal_user()['id'],$id]);
            portal_log($pdo,'cancel_payment','Membatalkan transaksi pembayaran #'.$paymentId); portal_flash('success','Transaksi dibatalkan dan total pembayaran dihitung ulang.');
        }
    } catch (PDOException $e) {
        portal_flash('danger', $e->getCode()==='23000' ? 'Nomor kuitansi atau data unik tersebut sudah digunakan.' : 'Data transaksi gagal disimpan.');
    } catch (Throwable $e) {
        portal_flash('danger', $e->getMessage());
    }
    header('Location: ' . SITE_URL . '/portal/payments');
    exit;
}

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(student_name LIKE ? OR parent_name LIKE ? OR whatsapp LIKE ? OR registration_number LIKE ? OR student_nik LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if (in_array($statusFilter, ['belum_bayar', 'sebagian', 'lunas'], true)) {
    $where[] = 'payment_status = ?';
    $params[] = $statusFilter;
}
$sql = 'SELECT * FROM spmb_registrations' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $registrations = $stmt->fetchAll();
$summary = $pdo->query("SELECT COUNT(*) total, SUM(payment_status='lunas') paid, SUM(payment_status='sebagian') partial, COALESCE(SUM(payment_amount),0) amount FROM spmb_registrations")->fetch();
$editPayment = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM spmb_registrations WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $editPayment = $stmt->fetch() ?: null;
}
$paymentRegistration = null;
if (isset($_GET['pay'])) { $stmt=$pdo->prepare('SELECT * FROM spmb_registrations WHERE id=?'); $stmt->execute([(int)$_GET['pay']]); $paymentRegistration=$stmt->fetch() ?: null; }
$transactions=$pdo->query('SELECT p.*,r.student_name,u.name recorder FROM spmb_payments p JOIN spmb_registrations r ON r.id=p.registration_id LEFT JOIN portal_users u ON u.id=p.recorded_by ORDER BY p.created_at DESC LIMIT 100')->fetchAll();
function portal_rupiah($amount): string { return 'Rp ' . number_format((float)$amount, 0, ',', '.'); }

$portalTitle = 'Pembayaran SPMB';
$portalActive = 'payments';
require __DIR__ . '/../../components/portal-header.php';
?>
<div class="portal-welcome"><div><h2>Kasir SPMB</h2><p>Catat pembayaran dan pantau status administrasi calon siswa.</p></div></div>
<div class="stat-grid">
    <div class="stat-card"><small>Total pendaftar</small><strong><?php echo (int)$summary['total']; ?></strong><span>Semua jenjang</span></div>
    <div class="stat-card"><small>Pembayaran lunas</small><strong><?php echo (int)$summary['paid']; ?></strong><span>Terverifikasi</span></div>
    <div class="stat-card"><small>Bayar sebagian</small><strong><?php echo (int)$summary['partial']; ?></strong><span>Perlu pelunasan</span></div>
    <div class="stat-card"><small>Total diterima</small><strong style="font-size:1.18rem"><?php echo portal_rupiah($summary['amount']); ?></strong><span>Akumulasi tercatat</span></div>
</div>

<?php if ($editPayment): ?>
<section class="portal-panel" style="margin-bottom:22px">
    <div class="panel-head"><h3>Data Lengkap Pendaftar — <?php echo esc($editPayment['student_name']); ?></h3><a href="<?php echo SITE_URL; ?>/portal/payments">Tutup</a></div>
    <form class="portal-form portal-form-grid" method="post">
        <input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="update_registration"><input type="hidden" name="id" value="<?php echo (int)$editPayment['id']; ?>">
        <div class="field"><label>Nomor pendaftaran</label><input name="registration_number" value="<?php echo esc($editPayment['registration_number'] ?: 'SPMB-'.date('Y').'-'.str_pad($editPayment['id'],4,'0',STR_PAD_LEFT)); ?>"></div><div class="field"><label>Nama calon siswa</label><input name="student_name" value="<?php echo esc($editPayment['student_name']); ?>" required></div>
        <div class="field"><label>NIK calon siswa</label><input name="student_nik" maxlength="30" value="<?php echo esc($editPayment['student_nik']); ?>"></div><div class="field"><label>Jenis kelamin</label><select name="gender"><option value="">-- Pilih --</option><option value="L" <?php echo $editPayment['gender']==='L'?'selected':''; ?>>Laki-laki</option><option value="P" <?php echo $editPayment['gender']==='P'?'selected':''; ?>>Perempuan</option></select></div>
        <div class="field"><label>Tempat lahir</label><input name="birth_place" value="<?php echo esc($editPayment['birth_place']); ?>"></div><div class="field"><label>Tanggal lahir</label><input type="date" name="birth_date" value="<?php echo esc($editPayment['birth_date']); ?>"></div>
        <div class="field"><label>Nama orang tua/wali</label><input name="parent_name" value="<?php echo esc($editPayment['parent_name']); ?>" required></div><div class="field"><label>NIK orang tua/wali</label><input name="parent_nik" value="<?php echo esc($editPayment['parent_nik']); ?>"></div>
        <div class="field"><label>Nomor kartu keluarga</label><input name="family_card_number" value="<?php echo esc($editPayment['family_card_number']); ?>"></div><div class="field"><label>WhatsApp</label><input name="whatsapp" value="<?php echo esc($editPayment['whatsapp']); ?>" required></div>
        <div class="field"><label>Email (opsional)</label><input type="email" name="email" value="<?php echo esc($editPayment['email']); ?>"></div><div class="field"><label>Jenjang</label><select name="level" required><?php foreach(['SD','SMP','SMA'] as $level): ?><option <?php echo $editPayment['level']===$level?'selected':''; ?>><?php echo $level; ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Asal sekolah</label><input name="previous_school" value="<?php echo esc($editPayment['previous_school']); ?>"></div><div class="field"><label>Status pendaftaran</label><select name="registration_status"><?php foreach(['baru'=>'Baru','verifikasi'=>'Verifikasi','lulus'=>'Lulus','cadangan'=>'Cadangan','ditolak'=>'Ditolak','daftar_ulang'=>'Daftar Ulang'] as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo $editPayment['registration_status']===$key?'selected':''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Status dokumen</label><select name="document_status"><?php foreach(['belum_lengkap'=>'Belum Lengkap','lengkap'=>'Lengkap','terverifikasi'=>'Terverifikasi'] as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo $editPayment['document_status']===$key?'selected':''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div><div class="field"><label>Status pembayaran</label><select name="payment_status"><?php foreach(['belum_bayar'=>'Belum Bayar','sebagian'=>'Sebagian','lunas'=>'Lunas'] as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo $editPayment['payment_status']===$key?'selected':''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div>
        <div class="field full"><label>Alamat lengkap</label><textarea name="address" style="min-height:80px"><?php echo esc($editPayment['address']); ?></textarea></div><div class="field full"><label>Catatan administrasi</label><textarea name="payment_notes" style="min-height:80px"><?php echo esc($editPayment['payment_notes']); ?></textarea></div>
        <div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/payments">Batal</a><button class="portal-action" type="submit">Simpan Data Pendaftar</button></div>
    </form>
</section>
<?php endif; ?>

<?php if ($paymentRegistration): ?>
<section class="portal-panel" style="margin-bottom:22px"><div class="panel-head"><h3>Catat Pembayaran — <?php echo esc($paymentRegistration['student_name']); ?></h3><a href="<?php echo SITE_URL; ?>/portal/payments">Tutup</a></div><form class="portal-form portal-form-grid" method="post"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="add_payment"><input type="hidden" name="id" value="<?php echo (int)$paymentRegistration['id']; ?>">
<div class="field"><label>Nomor kuitansi</label><input name="receipt_number" placeholder="Otomatis jika dikosongkan"></div><div class="field"><label>Jenis pembayaran</label><select name="payment_type" required><option value="">-- Pilih --</option><option>Formulir Pendaftaran</option><option>Daftar Ulang</option><option>Uang Pangkal</option><option>Seragam</option><option>SPP</option><option>Lainnya</option></select></div>
<div class="field"><label>Nominal (Rp)</label><input type="number" min="1" step="1000" name="amount" required></div><div class="field"><label>Metode pembayaran</label><select name="payment_method" required><option value="">-- Pilih --</option><option>Transfer Bank</option><option>Tunai</option><option>QRIS</option><option>Virtual Account</option></select></div>
<div class="field"><label>Tanggal pembayaran</label><input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required></div><div class="field"><label>Nama penyetor</label><input name="payer_name" value="<?php echo esc($paymentRegistration['parent_name']); ?>"></div><div class="field"><label>Nomor referensi bank</label><input name="reference_number"></div><div class="field"><label>Status setelah transaksi</label><select name="payment_status_after"><option value="sebagian">Bayar Sebagian</option><option value="lunas">Lunas</option></select></div><div class="field full"><label>Catatan transaksi</label><textarea name="notes" style="min-height:80px"></textarea></div><div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/payments">Batal</a><button class="portal-action">Simpan Transaksi</button></div></form></section>
<?php endif; ?>

<section class="portal-panel">
    <div class="panel-head"><h3>Data Pendaftar</h3></div>
    <form class="filters" method="get"><input name="q" value="<?php echo esc($search); ?>" placeholder="Cari nomor, NIK, siswa, orang tua, WA"><select name="status"><option value="">Semua status</option><option value="belum_bayar" <?php echo $statusFilter==='belum_bayar'?'selected':''; ?>>Belum bayar</option><option value="sebagian" <?php echo $statusFilter==='sebagian'?'selected':''; ?>>Sebagian</option><option value="lunas" <?php echo $statusFilter==='lunas'?'selected':''; ?>>Lunas</option></select><button class="portal-action small">Filter</button><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/portal/payments">Reset</a></form>
    <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Calon Siswa</th><th>Jenjang</th><th>Orang Tua / WA</th><th>Status</th><th>Nominal</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody>
    <?php if (!$registrations): ?><tr><td colspan="7" class="empty-state">Belum ada data pendaftar yang cocok.</td></tr><?php endif; ?>
    <?php foreach ($registrations as $item): ?><tr><td><strong><?php echo esc($item['student_name']); ?></strong><br><small style="color:var(--portal-muted)"><?php echo esc($item['registration_number'] ?: 'Belum ada nomor'); ?></small></td><td><?php echo esc($item['level']); ?><br><small><?php echo esc(ucwords(str_replace('_',' ',$item['registration_status']))); ?></small></td><td><?php echo esc($item['parent_name']); ?><br><small><?php echo esc($item['whatsapp']); ?></small></td><td><span class="status <?php echo esc($item['payment_status']); ?>"><?php echo esc(ucwords(str_replace('_',' ',$item['payment_status']))); ?></span><br><small><?php echo esc(ucwords(str_replace('_',' ',$item['document_status']))); ?></small></td><td class="amount"><?php echo portal_rupiah($item['payment_amount']); ?></td><td><?php echo $item['payment_date'] ? esc(date('d/m/Y', strtotime($item['payment_date']))) : '-'; ?></td><td><div class="table-actions"><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/portal/payments?edit=<?php echo (int)$item['id']; ?>">Data</a><a class="portal-action small" href="<?php echo SITE_URL; ?>/portal/payments?pay=<?php echo (int)$item['id']; ?>">Bayar</a></div></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<section class="portal-panel" style="margin-top:22px"><div class="panel-head"><h3>Riwayat Transaksi</h3></div><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Kuitansi</th><th>Siswa</th><th>Jenis</th><th>Nominal</th><th>Metode</th><th>Tanggal</th><th>Petugas</th><th>Status/Aksi</th></tr></thead><tbody><?php if(!$transactions): ?><tr><td colspan="8" class="empty-state">Belum ada transaksi pembayaran.</td></tr><?php endif; ?><?php foreach($transactions as $trx): ?><tr><td><strong><?php echo esc($trx['receipt_number']); ?></strong><?php if($trx['reference_number']): ?><br><small>Ref: <?php echo esc($trx['reference_number']); ?></small><?php endif; ?></td><td><?php echo esc($trx['student_name']); ?><br><small><?php echo esc($trx['payer_name']?:'-'); ?></small></td><td><?php echo esc($trx['payment_type']); ?></td><td class="amount"><?php echo portal_rupiah($trx['amount']); ?></td><td><?php echo esc($trx['payment_method']); ?></td><td><?php echo esc(date('d/m/Y',strtotime($trx['payment_date']))); ?></td><td><?php echo esc($trx['recorder']?:'-'); ?></td><td><?php if($trx['status']==='verified'): ?><form method="post" onsubmit="return confirm('Batalkan transaksi ini?')"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="cancel_payment"><input type="hidden" name="id" value="<?php echo (int)$trx['registration_id']; ?>"><input type="hidden" name="payment_id" value="<?php echo (int)$trx['id']; ?>"><button class="portal-action danger small">Batalkan</button></form><?php else: ?><span class="status inactive">Dibatalkan</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/../../components/portal-footer.php'; ?>
