<?php
require_once __DIR__ . '/spmb_shared.php';

function spmb_report_filters(string $scope, array $query): array
{
    if (!is_scalar($query['year'] ?? '') || !is_scalar($query['class'] ?? '')) {
        throw new RuntimeException('Filter rekap tidak valid.');
    }
    $year = trim((string) ($query['year'] ?? ''));
    $class = trim((string) ($query['class'] ?? ''));
    if ($year !== '' && !preg_match('/^\d{4}\/\d{4}$/', $year)) throw new RuntimeException('Tahun ajaran tidak valid.');
    if ($class !== '' && $class !== 'Belum ditentukan' && !in_array($class, spmb_entry_classes($scope), true)) {
        throw new RuntimeException('Kelas tujuan tidak valid.');
    }
    return ['year' => $year, 'class' => $class];
}

function spmb_report_rows(PDO $mainPdo, PDO $unitPdo, string $scope, array $filters): array
{
    $rows = unit_admin_spmb_rows($mainPdo, $scope);
    $legacy = $unitPdo->prepare('SELECT * FROM unit_enrollments WHERE unit_slug=? ORDER BY created_at DESC');
    $legacy->execute([$scope]);
    foreach ($legacy->fetchAll() as $item) {
        $rows[] = [
            'id' => (int)$item['id'], 'source' => 'legacy',
            'registration_number' => 'LAMA-' . $item['id'],
            'student_name' => $item['child_name'], 'entry_class' => null,
            'academic_year' => '-', 'admission_track' => 'reguler',
            'gender' => null, 'birth_date' => null,
            'parent_name' => $item['parent_name'], 'whatsapp' => $item['phone'],
            'previous_school' => null, 'registration_status' => $item['status'],
            'created_at' => $item['created_at'],
        ];
    }
    $rows = array_values(array_filter($rows, static function (array $row) use ($filters): bool {
        $class = trim((string) ($row['entry_class'] ?? '')) ?: 'Belum ditentukan';
        return ($filters['year'] === '' || $row['academic_year'] === $filters['year'])
            && ($filters['class'] === '' || $class === $filters['class']);
    }));
    usort($rows, static fn(array $a, array $b): int => strcmp((string)$b['created_at'], (string)$a['created_at']));
    return $rows;
}

function spmb_report_counts(array $rows, string $scope): array
{
    $counts = array_fill_keys(spmb_entry_classes($scope), 0);
    $counts['Belum ditentukan'] = 0;
    foreach ($rows as $row) {
        $class = trim((string) ($row['entry_class'] ?? '')) ?: 'Belum ditentukan';
        $counts[$class] = ($counts[$class] ?? 0) + 1;
    }
    if ($counts['Belum ditentukan'] === 0) unset($counts['Belum ditentukan']);
    return $counts;
}

function spmb_xlsx_cell(string $column, int $row, string|int $value, int $style = 0): string
{
    $reference = $column . $row;
    if (is_int($value)) return '<c r="' . $reference . '" s="' . $style . '"><v>' . $value . '</v></c>';
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
    $safe = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<c r="' . $reference . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $safe . '</t></is></c>';
}

function spmb_export_xlsx(array $rows, string $scope, array $filters): void
{
    if (!class_exists(ZipArchive::class)) throw new RuntimeException('Ekstensi ZIP diperlukan untuk ekspor Excel.');
    $units = school_admin_units();
    $unit = $units[$scope];
    $counts = spmb_report_counts($rows, $scope);
    $xmlRows = [];
    $xmlRows[] = '<row r="1" ht="32">' . spmb_xlsx_cell('A', 1, 'REKAP PENDAFTAR SPMB · ' . strtoupper($unit), 1) . '</row>';
    $xmlRows[] = '<row r="2" ht="24">' . spmb_xlsx_cell('A', 2, 'SIT Permata Hati Bekasi  |  Tahun ajaran: ' . ($filters['year'] ?: 'Semua') . '  |  Kelas: ' . ($filters['class'] ?: 'Semua'), 2) . '</row>';
    $xmlRows[] = '<row r="3" ht="22">' . spmb_xlsx_cell('A', 3, 'Total pendaftar', 3) . spmb_xlsx_cell('B', 3, count($rows), 4) . spmb_xlsx_cell('D', 3, 'Diekspor', 3) . spmb_xlsx_cell('E', 3, date('d/m/Y H:i'), 4) . '</row>';
    $xmlRows[] = '<row r="5" ht="24">' . spmb_xlsx_cell('A', 5, 'RINGKASAN PER KELAS', 5) . '</row>';
    $line = 6;
    foreach ($counts as $class => $count) {
        $xmlRows[] = '<row r="' . $line . '" ht="21">' . spmb_xlsx_cell('A', $line, $class, 6) . spmb_xlsx_cell('B', $line, $count, 7) . '</row>';
        $line++;
    }
    $line += 2;
    $headerRow = $line;
    $headers = ['No', 'No. Registrasi', 'Nama Calon Siswa', 'NIK Calon Siswa', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Kelas Tujuan', 'Tahun Ajaran', 'Jalur', 'Nama Orang Tua', 'NIK Orang Tua', 'No. KK', 'WhatsApp', 'Asal Sekolah', 'Alamat', 'Status', 'Tanggal Daftar'];
    $columns = range('A', 'R');
    $cells = '';
    foreach ($headers as $index => $header) $cells .= spmb_xlsx_cell($columns[$index], $line, $header, 5);
    $xmlRows[] = '<row r="' . $line . '" ht="30">' . $cells . '</row>';
    $labels = ['baru'=>'Baru','verifikasi'=>'Verifikasi','lulus'=>'Lulus','cadangan'=>'Cadangan','ditolak'=>'Ditolak','daftar_ulang'=>'Daftar Ulang'];
    foreach ($rows as $index => $item) {
        $line++;
        $values = [$index + 1, (string)($item['registration_number'] ?: '-'), (string)$item['student_name'], (string)($item['student_nik'] ?? ''), $item['gender'] === 'L' ? 'Laki-laki' : ($item['gender'] === 'P' ? 'Perempuan' : '-'), (string)($item['birth_place'] ?? ''), (string)($item['birth_date'] ?: '-'), (string)($item['entry_class'] ?: 'Belum ditentukan'), (string)$item['academic_year'], $item['admission_track'] === 'waiting_list' ? 'Waiting List' : 'Reguler', (string)$item['parent_name'], (string)($item['parent_nik'] ?? ''), (string)($item['family_card_number'] ?? ''), (string)$item['whatsapp'], (string)($item['previous_school'] ?: '-'), (string)($item['address'] ?? ''), $labels[$item['registration_status']] ?? (string)$item['registration_status'], (string)$item['created_at']];
        $cells = '';
        foreach ($values as $column => $value) $cells .= spmb_xlsx_cell($columns[$column], $line, $value, $index % 2 ? 9 : 8);
        $xmlRows[] = '<row r="' . $line . '" ht="24">' . $cells . '</row>';
    }
    $widths = [7,23,32,22,17,20,18,20,17,17,31,22,22,19,28,42,18,22];
    $cols = '';
    foreach ($widths as $index => $width) { $n=$index+1; $cols .= '<col min="'.$n.'" max="'.$n.'" width="'.$width.'" customWidth="1"/>'; }
    $last = max($line, $headerRow + 1);
    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$headerRow.'" topLeftCell="A'.($headerRow+1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="20"/><cols>'.$cols.'</cols><sheetData>'.implode('', $xmlRows).'</sheetData><mergeCells count="3"><mergeCell ref="A1:R1"/><mergeCell ref="A2:R2"/><mergeCell ref="A5:R5"/></mergeCells><autoFilter ref="A'.$headerRow.':R'.$last.'"/><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/></worksheet>';
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="4"><font><sz val="10"/><name val="Aptos"/></font><font><b/><sz val="17"/><color rgb="FFFFFFFF"/><name val="Aptos Display"/></font><font><sz val="10"/><color rgb="FF526477"/><name val="Aptos"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Aptos"/></font></fonts><fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF075F46"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE8F5EE"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF5F8FA"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="10"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFill="1" applyFont="1"/><xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFill="1" applyFont="1"/><xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFill="1" applyFont="1"/><xf numFmtId="0" fontId="0" fillId="3" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="0" fillId="3" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="0" fillId="4" borderId="0" xfId="0" applyFill="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    $files = [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
        'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Rekap SPMB" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'xl/styles.xml' => $styles,
        'xl/worksheets/sheet1.xml' => $sheet,
    ];
    $path = tempnam(sys_get_temp_dir(), 'spmb-');
    if ($path === false) throw new RuntimeException('File ekspor sementara tidak dapat dibuat.');
    try {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('File Excel gagal dibuat.');
        foreach ($files as $name => $content) $zip->addFromString($name, $content);
        $zip->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="rekap-spmb-' . $scope . '-' . date('Ymd-His') . '.xlsx"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, no-store');
        readfile($path);
    } finally {
        @unlink($path);
    }
}
