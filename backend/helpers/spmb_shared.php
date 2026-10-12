<?php
/**
 * Logika SPMB yang dipakai bersama oleh form pendaftaran yayasan
 * (frontend/pages/form-spmb.php) dan form pendaftaran di setiap portal unit
 * (daycare|tkit|sdit|smpit/page.php), agar keduanya menulis ke tabel
 * spmb_registrations yang sama dengan aturan validasi yang sama.
 */

function spmb_academic_years(): array {
    $years = [];
    $start = (int) date('Y');
    for ($i = 0; $i <= 10; $i++) {
        $year = $start + $i;
        $years[$year . '/' . ($year + 1)] = $i === 0 ? 'Pendaftaran Berjalan' : 'Waiting List';
    }
    return $years;
}

function spmb_entry_classes(string $level): array {
    return match (school_unit_slug($level)) {
        'daycare' => ['Bayi', 'Toddler', 'Prasekolah'],
        'tkit' => ['TK A', 'TK B'],
        'sdit' => ['Kelas 1', 'Kelas 2', 'Kelas 3', 'Kelas 4', 'Kelas 5', 'Kelas 6'],
        'smpit' => ['Kelas 7', 'Kelas 8', 'Kelas 9'],
        default => [],
    };
}

function spmb_fields_from_post(array $post, ?string $lockedLevel = null): array {
    return [
        'student_name' => trim((string) ($post['student_name'] ?? '')),
        'student_nik' => trim((string) ($post['student_nik'] ?? '')),
        'nisn' => trim((string) ($post['nisn'] ?? '')),
        'gender' => trim((string) ($post['gender'] ?? '')),
        'birth_place' => trim((string) ($post['birth_place'] ?? '')),
        'birth_date' => trim((string) ($post['birth_date'] ?? '')),
        'parent_name' => trim((string) ($post['parent_name'] ?? '')),
        'parent_nik' => trim((string) ($post['parent_nik'] ?? '')),
        'family_card_number' => trim((string) ($post['family_card_number'] ?? '')),
        'whatsapp' => trim((string) ($post['whatsapp'] ?? '')),
        'level' => $lockedLevel ?? trim((string) ($post['level'] ?? '')),
        'entry_class' => trim((string) ($post['entry_class'] ?? '')),
        'academic_year' => trim((string) ($post['academic_year'] ?? '')),
        'previous_school' => trim((string) ($post['previous_school'] ?? '')),
        'address' => trim((string) ($post['address'] ?? '')),
    ];
}

function spmb_empty_fields(?string $lockedLevel = null, ?array $academicYears = null): array {
    $academicYears ??= spmb_academic_years();
    return spmb_fields_from_post(['academic_year' => array_key_first($academicYears)], $lockedLevel);
}

/**
 * @param array<string,string>|null $validLevels Peta level=>label yang valid. Dilewati (null)
 *        saat level sudah dikunci ($lockedLevel) sehingga tidak perlu divalidasi ulang.
 * @return array{success:bool,errors:string[],old:array<string,string>}
 */
function spmb_validate_and_save(PDO $pdo, array $post, ?string $lockedLevel = null, ?array $validLevels = null, string $source = 'online'): array {
    $academicYears = spmb_academic_years();
    $old = spmb_fields_from_post($post, $lockedLevel);
    $errors = [];

    if ($old['student_name'] === '') $errors[] = 'Nama calon siswa wajib diisi.';
    if ($old['nisn'] !== '' && !preg_match('/^[0-9]{10}$/', $old['nisn'])) $errors[] = 'NISN harus terdiri dari 10 angka.';
    if ($old['parent_name'] === '') $errors[] = 'Nama orang tua wajib diisi.';
    if ($old['whatsapp'] === '') $errors[] = 'Nomor WhatsApp wajib diisi.';
    if ($old['level'] === '' || ($validLevels !== null && !isset($validLevels[$old['level']]))) {
        $errors[] = 'Jenjang yang dipilih tidak valid.';
    }
    if ($old['entry_class'] === '' || !in_array($old['entry_class'], spmb_entry_classes($old['level']), true)) {
        $errors[] = 'Kelas tujuan wajib dipilih sesuai jenjang.';
    }
    if (!isset($academicYears[$old['academic_year']])) $errors[] = 'Tahun ajaran yang dipilih tidak valid.';

    if (!in_array($source, ['online','manual'], true)) $errors[] = 'Sumber pendaftaran tidak valid.';
    if ($errors) return ['success' => false, 'errors' => $errors, 'old' => $old];

    $admissionTrack = $academicYears[$old['academic_year']] === 'Waiting List' ? 'waiting_list' : 'reguler';
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("INSERT INTO spmb_registrations (student_name,student_nik,nisn,gender,birth_place,birth_date,parent_name,parent_nik,family_card_number,whatsapp,level,entry_class,academic_year,admission_track,previous_school,address,registration_source,payment_access_token) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $old['student_name'],
        $old['student_nik'] ?: null, $old['nisn'] ?: null, $old['gender'] ?: null, $old['birth_place'] ?: null, $old['birth_date'] ?: null,
        $old['parent_name'],
        $old['parent_nik'] ?: null, $old['family_card_number'] ?: null,
        $old['whatsapp'],
        $old['level'],
        $old['entry_class'],
        $old['academic_year'],
        $admissionTrack,
        $old['previous_school'] ?: null,
        $old['address'] ?: null,
        $source,
        $token,
    ]);
    $newId = (int) $pdo->lastInsertId();
    require_once __DIR__ . '/spmb_workflow.php';
    $pdo->prepare('UPDATE spmb_registrations SET registration_number=? WHERE id=?')
        ->execute([spmb_registration_code($newId, $old['level'], $old['academic_year']), $newId]);

    return ['success' => true, 'errors' => [], 'old' => spmb_empty_fields($lockedLevel, $academicYears), 'id' => $newId, 'registration_number' => spmb_registration_code($newId, $old['level'], $old['academic_year']), 'payment_token' => $token];
}
