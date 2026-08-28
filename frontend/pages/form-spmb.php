<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
$page_title = 'Form Pendaftaran SPMB';
$spmbLevelRows = $pdo->query("SELECT subtitle,title FROM site_content_items WHERE type='unit' AND is_active=1 ORDER BY sort_order,id")->fetchAll();
$spmbLevels = [];
foreach ($spmbLevelRows as $row) {
    $value = trim((string) ($row['subtitle'] ?: $row['title']));
    if ($value !== '') $spmbLevels[$value] = $row['title'];
}

$success = false;
$errors = [];
$old = ['student_name'=>'','student_nik'=>'','gender'=>'','birth_place'=>'','birth_date'=>'','parent_name'=>'','parent_nik'=>'','family_card_number'=>'','whatsapp'=>'','email'=>'','level'=>($_GET['level']??''),'previous_school'=>'','address'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['student_name']    = trim($_POST['student_name'] ?? '');
    $old['student_nik']     = trim($_POST['student_nik'] ?? '');
    $old['gender']          = trim($_POST['gender'] ?? '');
    $old['birth_place']     = trim($_POST['birth_place'] ?? '');
    $old['birth_date']      = trim($_POST['birth_date'] ?? '');
    $old['parent_name']     = trim($_POST['parent_name'] ?? '');
    $old['parent_nik']      = trim($_POST['parent_nik'] ?? '');
    $old['family_card_number'] = trim($_POST['family_card_number'] ?? '');
    $old['whatsapp']        = trim($_POST['whatsapp'] ?? '');
    $old['email']           = trim($_POST['email'] ?? '');
    $old['level']           = trim($_POST['level'] ?? '');
    $old['previous_school'] = trim($_POST['previous_school'] ?? '');
    $old['address']         = trim($_POST['address'] ?? '');

    // Validasi
    if ($old['student_name'] === '') $errors[] = 'Nama calon siswa wajib diisi.';
    if ($old['parent_name'] === '') $errors[] = 'Nama orang tua wajib diisi.';
    if ($old['whatsapp'] === '') $errors[] = 'Nomor WhatsApp wajib diisi.';
    if ($old['level'] === '' || !isset($spmbLevels[$old['level']])) $errors[] = 'Jenjang yang dipilih tidak valid.';
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO spmb_registrations (student_name,student_nik,gender,birth_place,birth_date,parent_name,parent_nik,family_card_number,whatsapp,email,level,previous_school,address) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $old['student_name'],
            $old['student_nik']?:null,$old['gender']?:null,$old['birth_place']?:null,$old['birth_date']?:null,
            $old['parent_name'],
            $old['parent_nik']?:null,$old['family_card_number']?:null,
            $old['whatsapp'],
            $old['email'] ?: null,
            $old['level'],
            $old['previous_school'] ?: null,
            $old['address'] ?: null,
        ]);
        $newId=(int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE spmb_registrations SET registration_number=? WHERE id=?')->execute(['SPMB-'.date('Y').'-'.str_pad((string)$newId,4,'0',STR_PAD_LEFT),$newId]);
        $success = true;
        $old = ['student_name'=>'','student_nik'=>'','gender'=>'','birth_place'=>'','birth_date'=>'','parent_name'=>'','parent_nik'=>'','family_card_number'=>'','whatsapp'=>'','email'=>'','level'=>'','previous_school'=>'','address'=>''];
    }
}

require_once __DIR__ . '/../components/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Form Pendaftaran SPMB</h1>
        <p class="breadcrumb"><a href="index.php">Beranda</a> / <a href="spmb.php">SPMB</a> / Form Pendaftaran</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="max-width: 640px; margin: 0 auto;">
            <div class="form-card">

                <?php if ($success): ?>
                    <div class="alert alert-success">Pendaftaran berhasil dikirim. Tim kami akan segera menghubungi Anda.</div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $err) echo esc($err) . '<br>'; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="form-spmb.php">
                    <div class="form-group">
                        <label for="student_name">Nama Calon Siswa *</label>
                        <input type="text" id="student_name" name="student_name" class="form-control" value="<?php echo esc($old['student_name']); ?>" required>
                    </div>
                    <div class="form-row"><div class="form-group"><label for="student_nik">NIK Calon Siswa</label><input type="text" id="student_nik" name="student_nik" class="form-control" value="<?php echo esc($old['student_nik']); ?>"></div><div class="form-group"><label for="gender">Jenis Kelamin</label><select id="gender" name="gender" class="form-control"><option value="">-- Pilih --</option><option value="L" <?php echo $old['gender']==='L'?'selected':''; ?>>Laki-laki</option><option value="P" <?php echo $old['gender']==='P'?'selected':''; ?>>Perempuan</option></select></div></div>
                    <div class="form-row"><div class="form-group"><label for="birth_place">Tempat Lahir</label><input type="text" id="birth_place" name="birth_place" class="form-control" value="<?php echo esc($old['birth_place']); ?>"></div><div class="form-group"><label for="birth_date">Tanggal Lahir</label><input type="date" id="birth_date" name="birth_date" class="form-control" value="<?php echo esc($old['birth_date']); ?>"></div></div>
                    <div class="form-group">
                        <label for="parent_name">Nama Orang Tua *</label>
                        <input type="text" id="parent_name" name="parent_name" class="form-control" value="<?php echo esc($old['parent_name']); ?>" required>
                    </div>
                    <div class="form-row"><div class="form-group"><label for="parent_nik">NIK Orang Tua/Wali</label><input type="text" id="parent_nik" name="parent_nik" class="form-control" value="<?php echo esc($old['parent_nik']); ?>"></div><div class="form-group"><label for="family_card_number">Nomor Kartu Keluarga</label><input type="text" id="family_card_number" name="family_card_number" class="form-control" value="<?php echo esc($old['family_card_number']); ?>"></div></div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="whatsapp">Nomor WhatsApp *</label>
                            <input type="text" id="whatsapp" name="whatsapp" class="form-control" value="<?php echo esc($old['whatsapp']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo esc($old['email']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="level">Jenjang *</label>
                        <select id="level" name="level" class="form-control" required>
                            <option value="">-- Pilih Jenjang --</option>
                            <?php foreach ($spmbLevels as $value => $label): ?>
                            <option value="<?php echo esc($value); ?>" <?php echo $old['level'] === $value ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="previous_school">Asal Sekolah</label>
                        <input type="text" id="previous_school" name="previous_school" class="form-control" value="<?php echo esc($old['previous_school']); ?>">
                    </div>
                    <div class="form-group"><label for="address">Alamat Lengkap</label><textarea id="address" name="address" class="form-control" rows="4"><?php echo esc($old['address']); ?></textarea></div>
                    <button type="submit" class="btn btn-primary btn-block">Kirim Pendaftaran</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
