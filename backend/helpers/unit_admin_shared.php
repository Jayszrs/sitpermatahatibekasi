<?php

function unit_admin_spmb_rows(PDO $mainPdo, string $scope): array {
    $rows = $mainPdo->query('SELECT * FROM spmb_registrations ORDER BY created_at DESC, id DESC')->fetchAll();
    return array_values(array_filter($rows, static fn(array $row): bool => school_unit_slug((string) $row['level']) === $scope));
}

function unit_admin_career_jobs(PDO $mainPdo, string $scope): array {
    $rows = $mainPdo->query('SELECT * FROM job_vacancies ORDER BY created_at DESC, id DESC')->fetchAll();
    return array_values(array_filter($rows, static fn(array $row): bool => school_unit_slug((string) $row['unit']) === $scope));
}

function unit_admin_career_job(PDO $mainPdo, int $id, string $scope): ?array {
    $stmt = $mainPdo->prepare('SELECT * FROM job_vacancies WHERE id=?');
    $stmt->execute([$id]);
    $job = $stmt->fetch();
    return $job && school_unit_slug((string) $job['unit']) === $scope ? $job : null;
}

function unit_admin_career_action(PDO $mainPdo, array $post, string $scope, ?string $image = null): string {
    $action = (string) ($post['action'] ?? '');
    $id = (int) ($post['id'] ?? 0);
    if ($action === 'save_job') {
        $existing = $id ? unit_admin_career_job($mainPdo, $id, $scope) : null;
        if ($id && !$existing) throw new RuntimeException('Lowongan tidak ditemukan di unit ini.');
        $fields = [];
        foreach (['title','department','employment_type','work_location','education','experience','summary','description','responsibilities','requirements','benefits','salary_note'] as $key) {
            $fields[$key] = trim((string) ($post[$key] ?? ''));
        }
        foreach (['title','department','employment_type','work_location','summary','description','responsibilities','requirements'] as $key) {
            if ($fields[$key] === '') throw new RuntimeException('Isi semua kolom utama lowongan.');
        }
        $deadline = trim((string) ($post['deadline'] ?? ''));
        if ($deadline !== '' && (!DateTimeImmutable::createFromFormat('!Y-m-d', $deadline) || DateTimeImmutable::createFromFormat('!Y-m-d', $deadline)->format('Y-m-d') !== $deadline)) throw new RuntimeException('Tanggal batas lamaran tidak valid.');
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fields['title']) ?: $fields['title']), '-')) ?: 'lowongan';
        $slug = $base; $suffix = 2;
        $check = $mainPdo->prepare('SELECT COUNT(*) FROM job_vacancies WHERE slug=? AND id<>?');
        do { $check->execute([$slug, $id]); if (!(int) $check->fetchColumn()) break; $slug = $base . '-' . $suffix++; } while (true);
        $unit = school_unit_catalog()[$scope]['subtitle'];
        $values = [$fields['title'],$slug,$unit,$fields['department'],$fields['employment_type'],$fields['work_location'],$fields['education'] ?: null,$fields['experience'] ?: null,$fields['summary'],$fields['description'],$fields['responsibilities'],$fields['requirements'],$fields['benefits'] ?: null,$fields['salary_note'] ?: null,$image ?: ($existing['image'] ?? null),$deadline ?: null,isset($post['is_featured']) ? 1 : 0,isset($post['is_active']) ? 1 : 0];
        if ($id) {
            $mainPdo->prepare('UPDATE job_vacancies SET title=?,slug=?,unit=?,department=?,employment_type=?,work_location=?,education=?,experience=?,summary=?,description=?,responsibilities=?,requirements=?,benefits=?,salary_note=?,image=?,deadline=?,is_featured=?,is_active=? WHERE id=?')->execute([...$values,$id]);
        } else {
            $mainPdo->prepare('INSERT INTO job_vacancies (title,slug,unit,department,employment_type,work_location,education,experience,summary,description,responsibilities,requirements,benefits,salary_note,image,deadline,is_featured,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($values);
        }
        return 'Lowongan berhasil disimpan.';
    }
    if ($action === 'archive_job') {
        if (!unit_admin_career_job($mainPdo, $id, $scope)) throw new RuntimeException('Lowongan tidak ditemukan di unit ini.');
        $mainPdo->prepare('UPDATE job_vacancies SET is_active=0 WHERE id=?')->execute([$id]);
        return 'Lowongan diarsipkan.';
    }
    if ($action === 'update_application') {
        $stmt = $mainPdo->prepare('SELECT v.unit FROM job_applications a JOIN job_vacancies v ON v.id=a.vacancy_id WHERE a.id=?');
        $stmt->execute([$id]);
        $unit = $stmt->fetchColumn();
        if (!$unit || school_unit_slug((string) $unit) !== $scope) throw new RuntimeException('Lamaran tidak ditemukan di unit ini.');
        $status = (string) ($post['status'] ?? '');
        if (!in_array($status, ['baru','ditinjau','wawancara','diterima','ditolak'], true)) throw new RuntimeException('Status lamaran tidak valid.');
        $mainPdo->prepare('UPDATE job_applications SET status=?,admin_notes=? WHERE id=?')->execute([$status,trim((string) ($post['admin_notes'] ?? '')) ?: null,$id]);
        return 'Status pelamar diperbarui.';
    }
    throw new RuntimeException('Aksi karir tidak dikenal.');
}

function unit_admin_render_careers(PDO $mainPdo, string $scope, string $csrf): void {
    $jobs = unit_admin_career_jobs($mainPdo, $scope);
    $edit = isset($_GET['edit_job']) ? unit_admin_career_job($mainPdo, (int) $_GET['edit_job'], $scope) : null;
    $applications = $mainPdo->query('SELECT a.*,v.title job_title,v.unit job_unit FROM job_applications a JOIN job_vacancies v ON v.id=a.vacancy_id ORDER BY a.created_at DESC')->fetchAll();
    $applications = array_values(array_filter($applications, static fn(array $row): bool => school_unit_slug((string) $row['job_unit']) === $scope));
    $base = '?tab=careers&amp;unit=' . unit_e($scope);
    ?>
    <section class="panel"><h2><?php echo $edit ? 'Ubah' : 'Tambah'; ?> Lowongan <?php echo unit_e(strtoupper($scope)); ?></h2><p class="muted">Lowongan yang disimpan tampil di halaman Karir unit dan website yayasan. Lamaran masuk tercatat di bawah.</p>
    <form method="post" enctype="multipart/form-data" class="form-grid"><input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>"><input type="hidden" name="action" value="save_job"><input type="hidden" name="id" value="<?php echo (int) ($edit['id'] ?? 0); ?>">
    <?php foreach (['title'=>'Nama posisi','department'=>'Bidang / divisi','work_location'=>'Lokasi kerja','education'=>'Pendidikan','experience'=>'Pengalaman','summary'=>'Ringkasan','salary_note'=>'Informasi kompensasi'] as $key=>$label): ?><label class="<?php echo $key==='summary'?'full':''; ?>"><?php echo $label; ?><input name="<?php echo $key; ?>" value="<?php echo unit_e($edit[$key] ?? ''); ?>" <?php echo in_array($key,['title','department','work_location','summary'],true)?'required':''; ?>></label><?php endforeach; ?>
    <label>Jenis pekerjaan<select name="employment_type"><?php foreach (['Penuh Waktu','Paruh Waktu','Kontrak','Magang','Freelance'] as $type): ?><option <?php echo ($edit['employment_type'] ?? '')===$type?'selected':''; ?>><?php echo unit_e($type); ?></option><?php endforeach; ?></select></label><label>Batas lamaran<input type="date" name="deadline" value="<?php echo unit_e($edit['deadline'] ?? ''); ?>"></label>
    <?php foreach (['description'=>'Deskripsi posisi','responsibilities'=>'Tanggung jawab','requirements'=>'Kualifikasi','benefits'=>'Fasilitas / benefit'] as $key=>$label): ?><label class="full"><?php echo $label; ?><textarea name="<?php echo $key; ?>" rows="4" <?php echo $key==='benefits'?'':'required'; ?>><?php echo unit_e($edit[$key] ?? ''); ?></textarea></label><?php endforeach; ?>
    <label class="full">Gambar lowongan (opsional)<input type="file" name="career_image" accept="image/jpeg,image/png,image/webp"></label><label><input type="checkbox" name="is_featured" <?php echo !empty($edit['is_featured'])?'checked':''; ?>> Posisi prioritas</label><label><input type="checkbox" name="is_active" <?php echo !isset($edit['is_active']) || $edit['is_active']?'checked':''; ?>> Tampilkan di website</label><div class="full actions"><button>Simpan Lowongan</button><?php if ($edit): ?><a href="<?php echo $base; ?>">Batal ubah</a><?php endif; ?></div></form></section>
    <section class="panel"><h2>Lowongan Saat Ini (<?php echo count($jobs); ?>)</h2><table><tr><th>Posisi</th><th>Status</th><th>Pelamar</th><th>Aksi</th></tr><?php foreach ($jobs as $job): $count = 0; foreach ($applications as $application) if ((int) $application['vacancy_id'] === (int) $job['id']) $count++; ?><tr><td><strong><?php echo unit_e($job['title']); ?></strong><br><span class="muted"><?php echo unit_e($job['department']); ?></span></td><td><?php echo $job['is_active']?'Tayang':'Arsip'; ?></td><td><?php echo $count; ?></td><td><div class="actions"><a href="?tab=careers&amp;unit=<?php echo unit_e($scope); ?>&amp;edit_job=<?php echo (int) $job['id']; ?>">Ubah</a><a href="<?php echo unit_e(unit_portal_url($scope,'karir.php?slug='.urlencode($job['slug']))); ?>" target="_blank" rel="noopener">Lihat</a><form method="post" onsubmit="return confirm('Arsipkan lowongan ini?')"><input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>"><input type="hidden" name="action" value="archive_job"><input type="hidden" name="id" value="<?php echo (int) $job['id']; ?>"><button class="danger">Arsipkan</button></form></div></td></tr><?php endforeach; if (!$jobs): ?><tr><td colspan="4" class="muted">Belum ada lowongan untuk unit ini.</td></tr><?php endif; ?></table></section>
    <section class="panel"><h2>Lamaran Masuk (<?php echo count($applications); ?>)</h2><table><tr><th>Pelamar / Posisi</th><th>Kontak</th><th>Status</th><th>Aksi</th></tr><?php foreach ($applications as $application): ?><tr><td><strong><?php echo unit_e($application['full_name']); ?></strong><br><?php echo unit_e($application['job_title']); ?><details><summary>Lihat pengantar</summary><p><?php echo nl2br(unit_e($application['cover_letter'])); ?></p></details></td><td><?php echo unit_e($application['email']); ?><br><?php echo unit_e($application['phone']); ?></td><td><?php echo unit_e($application['status']); ?></td><td><a href="?tab=careers&amp;unit=<?php echo unit_e($scope); ?>&amp;download_cv=<?php echo (int) $application['id']; ?>">Unduh CV</a><form method="post"><input type="hidden" name="csrf" value="<?php echo unit_e($csrf); ?>"><input type="hidden" name="action" value="update_application"><input type="hidden" name="id" value="<?php echo (int) $application['id']; ?>"><select name="status"><?php foreach (['baru','ditinjau','wawancara','diterima','ditolak'] as $status): ?><option value="<?php echo $status; ?>" <?php echo $application['status']===$status?'selected':''; ?>><?php echo ucfirst($status); ?></option><?php endforeach; ?></select><input name="admin_notes" value="<?php echo unit_e($application['admin_notes'] ?? ''); ?>" placeholder="Catatan internal"><button>Simpan</button></form></td></tr><?php endforeach; if (!$applications): ?><tr><td colspan="4" class="muted">Belum ada lamaran.</td></tr><?php endif; ?></table></section>
    <?php
}
