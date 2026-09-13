<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
require_once __DIR__ . '/../../backend/helpers/spmb_shared.php';
$publicCsrfToken = public_form_csrf_token();
$page_title = 'Form Pendaftaran SPMB';
$spmbLevelRows = fetch_school_units($pdo);
$spmbLevels = [];
foreach ($spmbLevelRows as $row) {
    $value = trim((string) ($row['subtitle'] ?: $row['title']));
    if ($value !== '') $spmbLevels[$value] = $row['title'];
}

$success = false;
$errors = [];
$requestedLevel = trim($_GET['level'] ?? '');
foreach (array_keys($spmbLevels) as $availableLevel) if (strcasecmp($availableLevel, $requestedLevel) === 0) $requestedLevel = $availableLevel;
$academicYears = spmb_academic_years();
$old = spmb_empty_fields($requestedLevel !== '' ? $requestedLevel : null, $academicYears);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        public_verify_csrf($_POST['_token'] ?? null);
        $result = spmb_validate_and_save($pdo, $_POST, null, $spmbLevels);
        $success = $result['success'];
        $errors = $result['errors'];
        $old = $result['old'];
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
        $old = spmb_fields_from_post($_POST);
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
        <div class="spmb-form-shell">
            <div class="form-card">

                <?php if ($success): ?>
                    <div class="alert alert-success">Pendaftaran berhasil dikirim. Tim kami akan segera menghubungi Anda.</div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $err) echo esc($err) . '<br>'; ?>
                    </div>
                <?php endif; ?>

                <div class="spmb-form-intro"><div><span class="section-eyebrow">Data Calon Siswa</span><h2>Mulai Pendaftaran</h2><p>Contoh berwarna samar di dalam kolom hanya panduan dan tidak ikut terkirim. Tim SPMB akan melanjutkan verifikasi melalui WhatsApp.</p></div></div>
                <form method="POST" action="form-spmb.php" id="spmbRegistrationForm">
                    <input type="hidden" name="_token" value="<?php echo esc($publicCsrfToken); ?>">
                    <div class="form-group">
                        <label for="student_name">Nama Calon Siswa *</label>
                        <input type="text" id="student_name" name="student_name" class="form-control" value="<?php echo esc($old['student_name']); ?>" placeholder="Contoh: Ahmad Fadhil Ramadhan" required>
                    </div>
                    <div class="form-row"><div class="form-group"><label for="student_nik">NIK Calon Siswa</label><input type="text" id="student_nik" name="student_nik" class="form-control" value="<?php echo esc($old['student_nik']); ?>" placeholder="Contoh: 327501120820180001"></div><div class="form-group"><label for="gender">Jenis Kelamin</label><select id="gender" name="gender" class="form-control"><option value="">-- Pilih --</option><option value="L" <?php echo $old['gender']==='L'?'selected':''; ?>>Laki-laki</option><option value="P" <?php echo $old['gender']==='P'?'selected':''; ?>>Perempuan</option></select></div></div>
                    <div class="form-row"><div class="form-group"><label for="birth_place">Tempat Lahir</label><input type="text" id="birth_place" name="birth_place" class="form-control" value="<?php echo esc($old['birth_place']); ?>" placeholder="Contoh: Bekasi"></div><div class="form-group"><label for="birth_date">Tanggal Lahir</label><input type="date" id="birth_date" name="birth_date" class="form-control" value="<?php echo esc($old['birth_date']); ?>"></div></div>
                    <div class="form-group">
                        <label for="parent_name">Nama Orang Tua *</label>
                        <input type="text" id="parent_name" name="parent_name" class="form-control" value="<?php echo esc($old['parent_name']); ?>" placeholder="Contoh: Budi Ramadhan" required>
                    </div>
                    <div class="form-row"><div class="form-group"><label for="parent_nik">NIK Orang Tua/Wali</label><input type="text" id="parent_nik" name="parent_nik" class="form-control" value="<?php echo esc($old['parent_nik']); ?>" placeholder="Contoh: 3275010101900001"></div><div class="form-group"><label for="family_card_number">Nomor Kartu Keluarga</label><input type="text" id="family_card_number" name="family_card_number" class="form-control" value="<?php echo esc($old['family_card_number']); ?>" placeholder="Contoh: 3275010101900001"></div></div>
                    <div class="form-group">
                            <label for="whatsapp">Nomor WhatsApp *</label>
                            <input type="tel" id="whatsapp" name="whatsapp" class="form-control" value="<?php echo esc($old['whatsapp']); ?>" placeholder="Contoh: 081234567890" required>
                    </div>
                    <div class="form-row"><div class="form-group">
                        <label for="level">Jenjang *</label>
                        <select id="level" name="level" class="form-control" required>
                            <option value="">-- Pilih Jenjang --</option>
                            <?php foreach ($spmbLevels as $value => $label): ?>
                            <option value="<?php echo esc($value); ?>" <?php echo $old['level'] === $value ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div><div class="form-group"><label for="academic_year">Tahun Ajaran *</label><select id="academic_year" name="academic_year" class="form-control" required><?php foreach($academicYears as $year=>$track): ?><option value="<?php echo esc($year); ?>" <?php echo $old['academic_year']===$year?'selected':''; ?>><?php echo esc($year.' — '.$track); ?></option><?php endforeach; ?></select><small class="field-help">Tahun berikutnya otomatis tercatat sebagai waiting list.</small></div></div>
                    <div class="form-group">
                        <label for="previous_school">Asal Sekolah</label>
                        <input type="text" id="previous_school" name="previous_school" class="form-control" value="<?php echo esc($old['previous_school']); ?>" placeholder="Contoh: TK Islam Ceria">
                    </div>
                    <div class="form-group"><label for="address">Alamat Lengkap</label><textarea id="address" name="address" class="form-control" rows="4" placeholder="Contoh: Perumahan ..., Tambun Selatan, Kabupaten Bekasi"><?php echo esc($old['address']); ?></textarea></div>
                    <button type="submit" class="btn btn-primary btn-block">Kirim Pendaftaran</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
