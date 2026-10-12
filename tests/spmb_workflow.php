<?php
require_once __DIR__ . '/../backend/helpers/functions.php';
require_once __DIR__ . '/../backend/helpers/spmb_workflow.php';
require_once __DIR__ . '/../backend/helpers/access_control.php';
require_once __DIR__ . '/../backend/helpers/spmb_report.php';
require_once __DIR__ . '/../backend/helpers/unit_admin_shared.php';

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec("CREATE TABLE spmb_registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT, registration_number TEXT, payment_access_token TEXT,
    student_name TEXT, student_nik TEXT, nisn TEXT, gender TEXT, birth_place TEXT, birth_date TEXT,
    parent_name TEXT, parent_nik TEXT, family_card_number TEXT, whatsapp TEXT, level TEXT,
    entry_class TEXT, academic_year TEXT, admission_track TEXT, previous_school TEXT, address TEXT,
    registration_source TEXT, registration_status TEXT DEFAULT 'baru', student_number TEXT,
    payment_amount NUMERIC DEFAULT 0, total_due NUMERIC DEFAULT 0,
    payment_status TEXT DEFAULT 'belum_bayar', payment_updated_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE spmb_payment_claims (
    id INTEGER PRIMARY KEY AUTOINCREMENT, registration_id INTEGER, source TEXT, payment_type TEXT,
    claimed_amount NUMERIC, payment_method TEXT, payment_date TEXT, payer_name TEXT,
    reference_number TEXT, verified_reference TEXT UNIQUE, proof_path TEXT, status TEXT DEFAULT 'pending', review_notes TEXT,
    reviewed_by_scope TEXT, reviewed_by_id INTEGER, reviewed_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$year = array_key_first(spmb_academic_years());
$input = ['student_name'=>'Ahmad', 'parent_name'=>'Ibu Ahmad', 'whatsapp'=>'08123456789',
    'level'=>'SDIT','entry_class'=>'Kelas 1','academic_year'=>$year,'nisn'=>'1234567890'];
$result = spmb_validate_and_save($pdo,$input,null,['SDIT'=>'SDIT'],'manual');
check($result['success'], 'Pendaftaran manual gagal.');
check(str_contains($result['registration_number'],'-SDIT-'), 'Kode unit tidak tercantum.');
check(strlen($result['payment_token'])===64, 'Token pembayaran tidak dibuat.');
$id = (int)$result['id'];
$claim = spmb_add_payment_claim($pdo,$id,['amount'=>'500000','payment_date'=>date('Y-m-d'),
    'payment_method'=>'Transfer Bank','payment_type'=>'Pembayaran SPMB','payer_name'=>'Ibu Ahmad'],null,'staff');
check((float)$pdo->query('SELECT payment_amount FROM spmb_registrations')->fetchColumn()===0.0, 'Klaim pending menaikkan total.');
try {
    spmb_review_payment_claim($pdo,$claim,'unit',1,'tkit','verified','500000','MUT-001','');
    throw new RuntimeException('Pembayaran unit lain dapat disetujui.');
} catch (RuntimeException $error) {
    check(str_contains($error->getMessage(),'unit ini'), 'Penolakan lintas unit tidak tepat.');
}
try {
    spmb_review_payment_claim($pdo,$claim,'unit',1,'sdit','verified','400000','MUT-001','');
    throw new RuntimeException('Nominal tidak cocok dapat disetujui.');
} catch (RuntimeException $error) {
    check(str_contains($error->getMessage(),'Nominal mutasi'), 'Penolakan nominal tidak tepat.');
}
$review=spmb_review_payment_claim($pdo,$claim,'unit',1,'sdit','verified','500000','MUT-001','Cocok dengan mutasi');
check($review['total']===500000.0, 'Akumulasi pembayaran salah.');
$pdo->exec('UPDATE spmb_registrations SET total_due=500000,registration_status=\'daftar_ulang\' WHERE id='.$id);
spmb_recalculate_payment($pdo,$id);
spmb_update_student_number($pdo,$id);
$row=$pdo->query('SELECT payment_status,student_number FROM spmb_registrations')->fetch();
check($row['payment_status']==='lunas', 'Status lunas tidak otomatis.');
check(str_starts_with($row['student_number'],'NIS-SDIT-'), 'NIS internal tidak dibuat.');
$second=spmb_add_payment_claim($pdo,$id,['amount'=>'100000','payment_date'=>date('Y-m-d'),
    'payment_method'=>'Transfer Bank','payment_type'=>'Lainnya'],null,'staff');
spmb_review_payment_claim($pdo,$second,'unit',1,'sdit','rejected','','','Bukti tidak cocok');
check((float)$pdo->query('SELECT payment_amount FROM spmb_registrations')->fetchColumn()===500000.0, 'Klaim ditolak mengubah total.');
$duplicate=spmb_add_payment_claim($pdo,$id,['amount'=>'500000','payment_date'=>date('Y-m-d'),
    'payment_method'=>'Transfer Bank','payment_type'=>'Lainnya'],null,'staff');
try {
    spmb_review_payment_claim($pdo,$duplicate,'unit',1,'sdit','verified','500000','MUT-001','');
    throw new RuntimeException('Referensi bank ganda diterima.');
} catch (RuntimeException $error) {
    check(str_contains($error->getMessage(),'sudah dipakai'), 'Penolakan referensi ganda tidak tepat.');
}
try {
    spmb_void_payment_claim($pdo,$claim,'unit',1,'tkit','Salah cocok mutasi');
    throw new RuntimeException('Unit lain dapat membatalkan verifikasi.');
} catch (RuntimeException $error) {
    check(str_contains($error->getMessage(),'unit ini'), 'Penolakan pembatalan lintas unit tidak tepat.');
}
$voidTotal=spmb_void_payment_claim($pdo,$claim,'unit',1,'sdit','Salah cocok mutasi');
check($voidTotal===0.0, 'Pembatalan tidak mengurangi akumulasi.');
check($pdo->query('SELECT payment_status FROM spmb_registrations')->fetchColumn()==='belum_bayar', 'Status setelah pembatalan salah.');
$invalid=$input;$invalid['nisn']='123';
check(!spmb_validate_and_save($pdo,$invalid,null,['SDIT'=>'SDIT'])['success'], 'NISN tidak valid diterima.');
check(unit_action_permission('manual_spmb_registration') === 'enrollments', 'Input manual tidak dibatasi izin SPMB.');
check(unit_action_permission('record_spmb_payment') === 'enrollments', 'Pencatatan bayar tidak dibatasi izin SPMB.');
$manualInput = $input + ['action'=>'manual_spmb_registration'];
$manualInput['level'] = 'TKIT';
$manualInput['student_name'] = 'Budi';
spmb_unit_staff_action($pdo,'sdit',$manualInput);
$manualId = (int)$pdo->lastInsertId();
$manualRow = spmb_unit_registration($pdo,$manualId,'sdit');
check($manualRow['level']==='SDIT' && $manualRow['registration_source']==='manual', 'Unit input manual dapat dipalsukan.');
$staffInput = ['action'=>'record_spmb_payment','id'=>$manualId,'amount'=>'100000','payment_date'=>date('Y-m-d'),'payment_type'=>'Pembayaran SPMB','payment_method'=>'Tunai'];
try {
    spmb_unit_staff_action($pdo,'tkit',$staffInput);
    throw new RuntimeException('Pembayaran manual lintas unit diterima.');
} catch (RuntimeException $error) { check(str_contains($error->getMessage(),'unit ini'), 'Penolakan bayar manual lintas unit salah.'); }
spmb_unit_staff_action($pdo,'sdit',$staffInput);
check((float)spmb_unit_registration($pdo,$manualId,'sdit')['payment_amount']===0.0, 'Bayar manual langsung menaikkan total.');
check(str_starts_with((string)spmb_whatsapp_url('08123456789','Pesan'),'https://wa.me/628123456789'), 'Nomor WhatsApp tidak dinormalisasi.');
check(spmb_whatsapp_url('123','Pesan')===null, 'Nomor WhatsApp tidak valid dibuatkan tautan.');
$pdo->exec('CREATE TABLE unit_enrollments (id INTEGER,unit_slug TEXT,child_name TEXT,parent_name TEXT,phone TEXT,status TEXT,created_at TEXT)');
$searchRows = spmb_report_rows($pdo,$pdo,'sdit',spmb_report_filters('sdit',['q'=>'Budi','payment'=>'belum_bayar']));
check(count($searchRows)===1 && (int)$searchRows[0]['id']===$manualId, 'Filter pencarian atau status bayar salah: ' . count($searchRows) . ' baris.');
foreach(array_keys(school_admin_units()) as $unit){
    spmb_unit_staff_action($pdo,$unit,['action'=>'manual_spmb_registration','student_name'=>'Uji '.$unit,'parent_name'=>'Orang Tua','whatsapp'=>'08123456789','entry_class'=>spmb_entry_classes($unit)[0],'academic_year'=>$year,'level'=>'UNIT_PALSU']);
    $unitRegistration = spmb_unit_registration($pdo,(int)$pdo->lastInsertId(),$unit);
    check(school_unit_slug($unitRegistration['level'])===$unit && str_contains($unitRegistration['registration_number'],'-'.strtoupper($unit).'-'), 'Pendaftaran manual unit '.$unit.' salah.');
}
$exportRow = $pdo->query('SELECT * FROM spmb_registrations LIMIT 1')->fetch();
$exportRow += ['birth_date'=>null,'birth_place'=>null,'created_at'=>date('Y-m-d H:i:s')];
ob_start();
spmb_export_xlsx([$exportRow,[
    'registration_number'=>'LAMA-1','student_name'=>'Data Lama','entry_class'=>null,
    'academic_year'=>'-','admission_track'=>'reguler','parent_name'=>'Orang Tua',
    'whatsapp'=>'0812','registration_status'=>'baru','created_at'=>date('Y-m-d H:i:s'),
    'source'=>'legacy',
]],'sdit',['year'=>$year,'class'=>'']);
$spreadsheet = ob_get_clean();
$tempFile = tempnam(sys_get_temp_dir(),'spmb-test-');
file_put_contents($tempFile,$spreadsheet);
$zip = new ZipArchive();
check($zip->open($tempFile) === true, 'Ekspor Excel tidak dapat dibuka.');
$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
check($sheetXml !== false && simplexml_load_string($sheetXml) !== false, 'XML lembar Excel tidak valid.');
check(str_contains($sheetXml,'autoFilter ref="A'), 'Filter Excel tidak tersedia.');
check((bool)preg_match('/<c r="U[0-9]+" s="10"><v>[0-9]+<\/v><\/c>/', $sheetXml), 'Tagihan Excel bukan angka terformat.');
$styleXml = $zip->getFromName('xl/styles.xml');
check($styleXml !== false && simplexml_load_string($styleXml) !== false && str_contains($styleXml,'cellXfs count="12"') && str_contains($styleXml,'formatCode="&quot;Rp&quot; #,##0"'), 'Format rupiah Excel tidak valid.');
$zip->close();
unlink($tempFile);
echo "SPMB workflow OK\n";
