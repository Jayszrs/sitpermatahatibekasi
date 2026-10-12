<?php
require_once __DIR__ . '/../config/storage.php';
require_once __DIR__ . '/spmb_shared.php';

function spmb_registration_code(int $id, string $level, string $academicYear): string
{
    return 'SPMB-' . substr($academicYear, 0, 4) . '-' . strtoupper(school_unit_slug($level)) . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
}

function spmb_student_number(int $id, string $level, string $academicYear): string
{
    return 'NIS-' . strtoupper(school_unit_slug($level)) . '-' . substr($academicYear, 0, 4) . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
}

function spmb_payment_link_token(PDO $pdo, int $registrationId): string
{
    $stmt = $pdo->prepare('SELECT payment_access_token FROM spmb_registrations WHERE id=?');
    $stmt->execute([$registrationId]);
    $token = $stmt->fetchColumn();
    if ($token === false) throw new RuntimeException('Pendaftar tidak ditemukan.');
    if ($token) return (string)$token;
    $new = bin2hex(random_bytes(32));
    $pdo->prepare('UPDATE spmb_registrations SET payment_access_token=? WHERE id=? AND payment_access_token IS NULL')->execute([$new,$registrationId]);
    $stmt->execute([$registrationId]);
    return (string)$stmt->fetchColumn();
}

function spmb_payment_url(string $token): string
{
    return SITE_URL . '/spmb/payment?token=' . rawurlencode($token);
}

function spmb_unit_registration(PDO $pdo, int $id, string $scope): array
{
    $query = $pdo->prepare('SELECT * FROM spmb_registrations WHERE id=?');
    $query->execute([$id]);
    $registration = $query->fetch();
    if (!$registration || school_unit_slug($registration['level']) !== $scope) {
        throw new RuntimeException('Pendaftar tidak ditemukan di unit ini.');
    }
    return $registration;
}

function spmb_unit_staff_action(PDO $pdo, string $scope, array $input, ?array $proof = null): string
{
    if (!isset(school_admin_units()[$scope])) throw new RuntimeException('Unit tidak valid.');
    if (($input['action'] ?? '') === 'manual_spmb_registration') {
        $level = school_unit_catalog()[$scope]['subtitle'];
        $result = spmb_validate_and_save($pdo, $input, $level, null, 'manual');
        if (!$result['success']) throw new RuntimeException(implode(' ', $result['errors']));
        return 'Pendaftar dibuat: ' . $result['registration_number'] . '. Gunakan aksi Tautan Bukti untuk membagikan halaman pembayaran.';
    }
    if (($input['action'] ?? '') === 'record_spmb_payment') {
        $registration = spmb_unit_registration($pdo, (int)($input['id'] ?? 0), $scope);
        spmb_add_payment_claim($pdo, (int)$registration['id'], $input, $proof, 'staff');
        return 'Pembayaran dicatat dan menunggu pemeriksaan. Total terbayar bertambah setelah diverifikasi.';
    }
    throw new RuntimeException('Tindakan SPMB tidak valid.');
}

function spmb_whatsapp_url(string $number, string $message): ?string
{
    $digits = preg_replace('/[^0-9]/', '', $number);
    if (str_starts_with($digits, '0')) $digits = '62' . substr($digits, 1);
    elseif (str_starts_with($digits, '8')) $digits = '62' . $digits;
    if (!preg_match('/^[1-9][0-9]{8,14}$/', $digits)) return null;
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
}

function spmb_update_student_number(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('SELECT level,academic_year,registration_status,student_number FROM spmb_registrations WHERE id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && $row['registration_status'] === 'daftar_ulang' && !$row['student_number']) {
        $pdo->prepare('UPDATE spmb_registrations SET student_number=? WHERE id=? AND student_number IS NULL')
            ->execute([spmb_student_number($id, $row['level'], $row['academic_year']), $id]);
    }
}

function spmb_upload_payment_proof(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Unggah bukti pembayaran terlebih dahulu.');
    if (($file['size'] ?? 0) < 1 || $file['size'] > 6 * 1024 * 1024) throw new RuntimeException('Bukti pembayaran maksimal 6 MB.');
    $type = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($extensions[$type])) throw new RuntimeException('Bukti pembayaran harus JPG, PNG, atau WebP.');
    $relative = 'private/spmb-proofs/proof-' . bin2hex(random_bytes(16)) . '.' . $extensions[$type];
    $path = app_storage_path($relative);
    app_ensure_storage_directory('private/spmb-proofs');
    if (!move_uploaded_file($file['tmp_name'], $path)) throw new RuntimeException('Bukti pembayaran gagal disimpan.');
    return $relative;
}

function spmb_add_payment_claim(PDO $pdo, int $registrationId, array $input, ?array $proof, string $source): int
{
    if (!in_array($source, ['online','staff'], true)) throw new RuntimeException('Sumber pembayaran tidak valid.');
    $check = $pdo->prepare('SELECT id FROM spmb_registrations WHERE id=?');
    $check->execute([$registrationId]);
    if (!$check->fetchColumn()) throw new RuntimeException('Pendaftar tidak ditemukan.');
    $amount = trim((string)($input['amount'] ?? ''));
    $date = trim((string)($input['payment_date'] ?? ''));
    $type = trim((string)($input['payment_type'] ?? 'Pembayaran SPMB'));
    $method = trim((string)($input['payment_method'] ?? 'Transfer Bank'));
    $payer = trim((string)($input['payer_name'] ?? ''));
    $reference = trim((string)($input['reference_number'] ?? ''));
    if (!preg_match('/^[1-9][0-9]{0,11}$/', $amount)) throw new RuntimeException('Nominal pembayaran harus angka rupiah positif.');
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) throw new RuntimeException('Tanggal pembayaran tidak valid.');
    if ($parsed > new DateTimeImmutable('today')) throw new RuntimeException('Tanggal pembayaran tidak boleh di masa depan.');
    if (!in_array($type, ['Pembayaran SPMB','Formulir Pendaftaran','Daftar Ulang','Uang Pangkal','Seragam','SPP','Lainnya'], true)
        || !in_array($method, ['Transfer Bank','Tunai','QRIS','Virtual Account'], true)) throw new RuntimeException('Jenis atau metode pembayaran tidak valid.');
    if ($source === 'online' && $method === 'Tunai') throw new RuntimeException('Pembayaran tunai dicatat oleh petugas.');
    if (mb_strlen($payer) > 150 || mb_strlen($reference) > 100) throw new RuntimeException('Nama penyetor atau referensi terlalu panjang.');
    $proofPath = null;
    if ($source === 'online' || ($proof && ($proof['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
        $proofPath = spmb_upload_payment_proof($proof ?? []);
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO spmb_payment_claims (registration_id,source,payment_type,claimed_amount,payment_method,payment_date,payer_name,reference_number,proof_path) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$registrationId,$source,$type,$amount,$method,$date,$payer ?: null,$reference ?: null,$proofPath]);
        return (int)$pdo->lastInsertId();
    } catch (Throwable $error) {
        if ($proofPath) @unlink(app_storage_path($proofPath));
        throw $error;
    }
}

function spmb_legacy_payment_total(PDO $pdo, int $registrationId): float
{
    $exists = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
        ? $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='spmb_payments'")->fetchColumn()
        : $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='spmb_payments'")->fetchColumn();
    if (!$exists) return 0.0;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM spmb_payments WHERE registration_id=? AND status='verified'");
    $stmt->execute([$registrationId]);
    return (float)$stmt->fetchColumn();
}

function spmb_recalculate_payment(PDO $pdo, int $registrationId, ?int $portalUserId = null): float
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(claimed_amount),0) FROM spmb_payment_claims WHERE registration_id=? AND status='verified'");
    $stmt->execute([$registrationId]);
    $total = (float)$stmt->fetchColumn() + spmb_legacy_payment_total($pdo,$registrationId);
    $stmt = $pdo->prepare('SELECT total_due FROM spmb_registrations WHERE id=?');
    $stmt->execute([$registrationId]);
    $due = (float)$stmt->fetchColumn();
    $status = $total <= 0 ? 'belum_bayar' : ($due > 0 && $total >= $due ? 'lunas' : 'sebagian');
    $pdo->prepare('UPDATE spmb_registrations SET payment_amount=?,payment_status=?,payment_updated_by=? WHERE id=?')
        ->execute([$total,$status,$portalUserId,$registrationId]);
    return $total;
}

function spmb_review_payment_claim(PDO $pdo, int $claimId, string $actorScope, int $actorId, ?string $allowedUnit, string $decision, string $confirmedAmount, string $bankReference, string $notes): array
{
    if (!in_array($decision, ['verified','rejected'], true)) throw new RuntimeException('Keputusan tidak valid.');
    $pdo->beginTransaction();
    try {
        $lock = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $stmt = $pdo->prepare('SELECT c.*,r.level,r.student_name FROM spmb_payment_claims c JOIN spmb_registrations r ON r.id=c.registration_id WHERE c.id=?' . $lock);
        $stmt->execute([$claimId]);
        $claim = $stmt->fetch();
        if (!$claim || $claim['status'] !== 'pending') throw new RuntimeException('Bukti pembayaran tidak ditemukan atau sudah diperiksa.');
        if ($allowedUnit !== null && school_unit_slug($claim['level']) !== $allowedUnit) throw new RuntimeException('Bukti pembayaran bukan milik unit ini.');
        $bankReference = trim($bankReference);
        $notes = trim($notes);
        if ($decision === 'verified') {
            if (!preg_match('/^[1-9][0-9]{0,11}$/', $confirmedAmount) || (int)$confirmedAmount !== (int)$claim['claimed_amount']) {
                throw new RuntimeException('Nominal mutasi harus sama dengan nominal klaim. Jika berbeda, tolak bukti dan minta pengiriman ulang.');
            }
            if ($claim['payment_method'] !== 'Tunai' && $bankReference === '') throw new RuntimeException('Isi referensi mutasi atau transaksi bank sebelum menyetujui.');
            if ($claim['payment_method'] !== 'Tunai') {
                $duplicate = $pdo->prepare("SELECT COUNT(*) FROM spmb_payment_claims WHERE status='verified' AND reference_number=? AND id<>?");
                $duplicate->execute([$bankReference,$claimId]);
                if ((int)$duplicate->fetchColumn() > 0) throw new RuntimeException('Referensi transaksi sudah dipakai pada pembayaran terverifikasi lain.');
            }
        } elseif ($notes === '') {
            throw new RuntimeException('Alasan penolakan wajib diisi.');
        }
        $pdo->prepare('UPDATE spmb_payment_claims SET status=?,reference_number=COALESCE(NULLIF(?,\'\'),reference_number),verified_reference=?,review_notes=?,reviewed_by_scope=?,reviewed_by_id=?,reviewed_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute([$decision,$bankReference,$decision === 'verified' && $claim['payment_method'] !== 'Tunai' ? $bankReference : null,$notes ?: null,$actorScope,$actorId,$claimId]);
        $total = spmb_recalculate_payment($pdo,(int)$claim['registration_id'],$actorScope === 'portal' ? $actorId : null);
        $pdo->commit();
        return ['claim'=>$claim,'total'=>$total];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function spmb_void_payment_claim(PDO $pdo, int $claimId, string $actorScope, int $actorId, ?string $allowedUnit, string $reason): float
{
    $reason = trim($reason);
    if ($reason === '') throw new RuntimeException('Alasan pembatalan wajib diisi.');
    $pdo->beginTransaction();
    try {
        $lock = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $stmt = $pdo->prepare('SELECT c.*,r.level FROM spmb_payment_claims c JOIN spmb_registrations r ON r.id=c.registration_id WHERE c.id=?' . $lock);
        $stmt->execute([$claimId]);
        $claim = $stmt->fetch();
        if (!$claim || $claim['status'] !== 'verified') throw new RuntimeException('Pembayaran tidak ditemukan atau sudah dibatalkan.');
        if ($allowedUnit !== null && school_unit_slug($claim['level']) !== $allowedUnit) throw new RuntimeException('Pembayaran bukan milik unit ini.');
        $pdo->prepare('UPDATE spmb_payment_claims SET status=\'voided\',verified_reference=NULL,review_notes=?,reviewed_by_scope=?,reviewed_by_id=?,reviewed_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute(['Verifikasi dibatalkan: '.$reason,$actorScope,$actorId,$claimId]);
        $total = spmb_recalculate_payment($pdo,(int)$claim['registration_id'],$actorScope === 'portal' ? $actorId : null);
        $pdo->commit();
        return $total;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function spmb_proof_path(string $relative): ?string
{
    if (!preg_match('~^private/spmb-proofs/proof-[a-f0-9]{32}\.(jpg|png|webp)$~', $relative)) return null;
    $path = app_storage_path($relative);
    return is_file($path) ? $path : null;
}

function spmb_stream_proof(string $relative): never
{
    $path = spmb_proof_path($relative);
    if (!$path) { http_response_code(404); exit('Bukti tidak ditemukan.'); }
    $types = ['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
    header('Content-Type: ' . $types[pathinfo($path, PATHINFO_EXTENSION)]);
    header('Content-Disposition: inline; filename="bukti-pembayaran.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}
