<?php
/**
 * Pengaturan Umum Website dan Fungsi Pembantu (Helpers)
 */

// ==== PENGATURAN UMUM WEBSITE ====
define('SITE_NAME', 'SIT Permata Hati Bekasi');
define('SITE_TAGLINE', 'Sekolah Islam Terpadu - Sholeh, Cerdas, Mandiri, dan Berwawasan Global');
if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost/' . basename(dirname(__DIR__, 2)));
define('SITE_PHONE', '(021) 1234-5678');
define('SITE_WHATSAPP', '6281234567890');
define('SITE_EMAIL', 'info@sitpermatahati-bekasi.sch.id');
define('SITE_ADDRESS', 'Kp. Buwek Jaya Gg. Buser No. 23-24, Sumberjaya, Tambun Selatan, Bekasi, Jawa Barat 17510');
define('SITE_DAYCARE_TKIT_CAMPUS_LABEL', 'Daycare, TKIT');
define('SITE_DAYCARE_TKIT_CAMPUS_ADDRESS', 'Kp. Buwek Jaya Gg. Buser No. 23-24, Sumberjaya, Tambun Selatan, Bekasi, Jawa Barat 17510');
define('SITE_DAYCARE_TKIT_LATITUDE', '-6.2388771');
define('SITE_DAYCARE_TKIT_LONGITUDE', '107.0793613');
define('SITE_SDIT_CAMPUS_LABEL', 'SDIT');
define('SITE_SDIT_CAMPUS_ADDRESS', 'Jln. Raya Buwekjaya Gang Buser No. 23-24 Desa Sumberjaya Tambun Selatan Bekasi.');
define('SITE_SDIT_LATITUDE', '-6.2391594');
define('SITE_SDIT_LONGITUDE', '107.0796598');
define('SITE_MAIN_CAMPUS_LABEL', 'Daycare, TKIT, SDIT');
define('SITE_MAIN_CAMPUS_ADDRESS', SITE_DAYCARE_TKIT_CAMPUS_ADDRESS);
define('SITE_SMPIT_CAMPUS_LABEL', 'SMPIT');
define('SITE_SMPIT_CAMPUS_ADDRESS', "Jl. Astana No.98, Simpang Lima, Tridaya Sakti, Kec. Tambun Selatan, Kabupaten Bekasi, Jawa Barat 17510");
define('SITE_SMPIT_LATITUDE', '-6.2494549');
define('SITE_SMPIT_LONGITUDE', '107.0781991');
define('SITE_INSTAGRAM', 'https://instagram.com/sitpermatahatibekasi');
define('SITE_YOUTUBE', 'https://youtube.com/@sitpermatahatibekasi');
define('SITE_DAYCARE_INSTAGRAM', 'https://www.instagram.com/daycarepermatahati.bekasi/');
define('SITE_TKIT_INSTAGRAM', 'https://www.instagram.com/tkitpermatahatibekasi/');
define('SITE_SDIT_INSTAGRAM', 'https://www.instagram.com/sditphbekasi/');
define('SITE_SMPIT_INSTAGRAM', 'https://www.instagram.com/smpit_permatahati/?hl=id');
define('SITE_SDIT_YOUTUBE', 'http://www.youtube.com/@sditpermatahatibekasi99');
define('SITE_SMPIT_YOUTUBE', 'http://www.youtube.com/@smpit_permatahati');

// Helper untuk output aman (mencegah XSS)
function esc($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Tambahkan versi berdasarkan waktu perubahan file agar browser tidak memakai
// CSS/JS lama setelah source code diperbarui dari Git.
function asset_url(string $relativePath): string {
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : '1';
    return SITE_URL . '/' . $relativePath . '?v=' . rawurlencode($version);
}

function school_unit_slug(string $value): string {
    $value = strtolower(trim($value));
    if (str_contains($value, 'daycare')) return 'daycare';
    if (str_contains($value, 'tkit') || preg_match('/(^|\s)tk($|\s)/', $value)) return 'tkit';
    if (str_contains($value, 'sdit') || preg_match('/(^|\s)sd($|\s)/', $value)) return 'sdit';
    if (str_contains($value, 'smpit') || preg_match('/(^|\s)smp($|\s)/', $value)) return 'smpit';
    return preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'unit';
}

function school_unit_catalog(): array {
    return [
        'daycare' => [
            'subtitle' => 'Daycare',
            'title' => 'Daycare Permata Hati Bekasi',
            'description' => 'Layanan pengasuhan anak usia dini dengan suasana aman, hangat, dan pembiasaan adab Islami sejak awal.',
            'extra' => "Stimulasi motorik\nPembiasaan doa\nAktivitas sensorik\nLaporan harian",
            'image' => SITE_URL . '/frontend/assets/images/units/daycare-building.webp',
            'address' => SITE_DAYCARE_TKIT_CAMPUS_ADDRESS,
            'latitude' => SITE_DAYCARE_TKIT_LATITUDE,
            'longitude' => SITE_DAYCARE_TKIT_LONGITUDE,
            'instagram' => SITE_DAYCARE_INSTAGRAM,
        ],
        'tkit' => [
            'subtitle' => 'TKIT',
            'title' => 'TKIT Permata Hati Bekasi',
            'description' => 'Jenjang taman kanak-kanak Islam terpadu yang menumbuhkan kemandirian, kreativitas, dan cinta Al-Quran.',
            'extra' => "Sentra bermain\nTahsin dasar\nDoa harian\nKemandirian",
            'image' => SITE_URL . '/frontend/assets/images/units/tkit-building.webp',
            'address' => SITE_DAYCARE_TKIT_CAMPUS_ADDRESS,
            'latitude' => SITE_DAYCARE_TKIT_LATITUDE,
            'longitude' => SITE_DAYCARE_TKIT_LONGITUDE,
            'instagram' => SITE_TKIT_INSTAGRAM,
        ],
        'sdit' => [
            'subtitle' => 'SDIT',
            'title' => 'SDIT Permata Hati Bekasi',
            'description' => 'Pendidikan dasar terpadu yang menguatkan akademik, tahfidz, adab, dan karakter mandiri siswa.',
            'extra' => "Tahfidz Juz 30\nLiterasi numerasi\nEkstrakurikuler\nFull Day School",
            'image' => SITE_URL . '/frontend/assets/images/units/sdit-building.webp',
            'address' => SITE_SDIT_CAMPUS_ADDRESS,
            'latitude' => SITE_SDIT_LATITUDE,
            'longitude' => SITE_SDIT_LONGITUDE,
            'instagram' => SITE_SDIT_INSTAGRAM,
        ],
        'smpit' => [
            'subtitle' => 'SMPIT',
            'title' => 'SMPIT Permata Hati Bekasi',
            'description' => 'Jenjang menengah pertama yang membangun kompetensi akademik, kepemimpinan, dan akhlak remaja muslim.',
            'extra' => "Tahfidz lanjutan\nEnglish Club\nKlub Sains\nLeadership Project",
            'image' => SITE_URL . '/frontend/assets/images/units/smpit-building.webp',
            'address' => SITE_SMPIT_CAMPUS_ADDRESS,
            'latitude' => SITE_SMPIT_LATITUDE,
            'longitude' => SITE_SMPIT_LONGITUDE,
            'instagram' => SITE_SMPIT_INSTAGRAM,
        ],
    ];
}

function fetch_school_units(PDO $pdo): array {
    $catalog = school_unit_catalog();
    $rows = $pdo->query("SELECT * FROM site_content_items WHERE type='unit' AND is_active=1 ORDER BY sort_order,id")->fetchAll();
    $selected = [];
    foreach ($rows as $row) {
        $slug = school_unit_slug((string) ($row['subtitle'] ?: $row['title']));
        if (isset($catalog[$slug]) && !isset($selected[$slug])) $selected[$slug] = $row;
    }
    foreach ($catalog as $slug => $defaults) {
        $row = $selected[$slug] ?? [];
        $selected[$slug] = array_merge($defaults, $row);
        $selected[$slug]['slug'] = $slug;
        if (empty($selected[$slug]['image'])) $selected[$slug]['image'] = $defaults['image'];
    }
    return array_values($selected);
}

function school_advantages(): array {
    return [
        'pendidikan-islami' => [
            'number' => '1', 'title' => 'Pendidikan Islami',
            'summary' => "Kurikulum terintegrasi nilai-nilai Al-Qur'an dan Sunnah.",
            'intro' => 'Nilai Islam hadir dalam pembelajaran, pembiasaan, dan interaksi sehari-hari agar ilmu tumbuh bersama adab.',
            'points' => ['Pembiasaan ibadah dan doa harian', 'Tahsin, tahfidz, dan pemahaman adab', 'Keteladanan guru dalam keseharian', 'Kolaborasi pembinaan bersama orang tua'],
        ],
        'guru-profesional' => [
            'number' => '2', 'title' => 'Guru Profesional',
            'summary' => 'Tenaga pendidik berpengalaman dan bersertifikasi.',
            'intro' => 'Guru mendampingi siswa dengan perencanaan belajar yang terarah, komunikasi yang hangat, dan evaluasi yang berkelanjutan.',
            'points' => ['Seleksi pendidik sesuai kompetensi', 'Pelatihan dan pengembangan berkala', 'Pendampingan akademik dan karakter', 'Komunikasi perkembangan siswa'],
        ],
        'kurikulum-berkualitas' => [
            'number' => '3', 'title' => 'Kurikulum Berkualitas',
            'summary' => 'Perpaduan kurikulum nasional dan pengembangan karakter.',
            'intro' => 'Pembelajaran dirancang relevan, terukur, dan menantang agar siswa memiliki fondasi akademik serta kecakapan hidup.',
            'points' => ['Literasi dan numerasi yang kuat', 'Pembelajaran berbasis proyek', 'Integrasi teknologi secara terarah', 'Evaluasi belajar yang menyeluruh'],
        ],
        'lingkungan-nyaman' => [
            'number' => '4', 'title' => 'Lingkungan Nyaman',
            'summary' => 'Suasana belajar yang aman, asri, dan mendukung.',
            'intro' => 'Lingkungan sekolah dibangun untuk membuat anak merasa aman, dihargai, dan berani mengeksplorasi potensinya.',
            'points' => ['Budaya sekolah yang ramah anak', 'Kelas tertata dan mendukung fokus', 'Pembiasaan hidup bersih dan tertib', 'Pendampingan sosial-emosional'],
        ],
        'fasilitas-lengkap' => [
            'number' => '5', 'title' => 'Fasilitas Lengkap',
            'summary' => 'Sarana pembelajaran modern dan lengkap.',
            'intro' => 'Fasilitas digunakan sebagai ruang eksplorasi untuk menguatkan pengalaman belajar di dalam maupun di luar kelas.',
            'points' => ['Ruang kelas dan area aktivitas', 'Masjid dan sarana pembiasaan ibadah', 'Lapangan serta fasilitas olahraga', 'Media belajar dan perangkat digital'],
        ],
        'pengembangan-karakter' => [
            'number' => '6', 'title' => 'Pengembangan Karakter',
            'summary' => 'Program pembinaan akhlak dan kepemimpinan berkelanjutan.',
            'intro' => 'Siswa dilatih bertanggung jawab, mandiri, peduli, dan mampu bekerja sama melalui pengalaman nyata.',
            'points' => ['Pembiasaan disiplin dan tanggung jawab', 'Proyek kolaborasi dan kepemimpinan', 'Kegiatan sosial dan kepedulian', 'Refleksi serta apresiasi perkembangan'],
        ],
    ];
}

function public_form_csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('phb_public_session');
        session_set_cookie_params(['lifetime' => 0, 'path' => defined('APP_COOKIE_PATH') ? APP_COOKIE_PATH : '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
        session_start();
    }
    if (empty($_SESSION['public_csrf'])) $_SESSION['public_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['public_csrf'];
}

function public_verify_csrf(?string $token): void {
    $expected = public_form_csrf_token();
    if (!$token || !hash_equals($expected, $token)) throw new RuntimeException('Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.');
}

function upload_career_document(array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) throw new RuntimeException('CV wajib diunggah.');
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('CV gagal diunggah.');
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Ukuran CV maksimal 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
    if (!isset($extensions[$mime])) throw new RuntimeException('Format CV harus PDF, DOC, atau DOCX.');
    $directory = dirname(__DIR__, 2) . '/frontend/assets/uploads/careers';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('Folder CV tidak dapat dibuat.');
    $filename = 'cv-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) throw new RuntimeException('CV gagal disimpan.');
    return ['url' => SITE_URL . '/frontend/assets/uploads/careers/' . $filename, 'name' => basename((string)($file['name'] ?? 'CV'))];
}

// Helper untuk format tanggal Indonesia
function tanggal_indo($tanggal) {
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $timestamp = strtotime($tanggal);
    return date('d', $timestamp) . ' ' . $bulan[(int)date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

// Menu navigasi dengan dropdown support
$nav_menu = [
    'index.php'    => 'Home',
    'profil'       => [
        'label' => 'Profil',
        'children' => [
            'tentang.php'  => 'Tentang Kami',
            'unit.php'     => 'Unit Sekolah',
            'program.php'  => 'Program',
            'prestasi.php' => 'Prestasi',
        ]
    ],
    'kontak.php'   => 'Lokasi',
    'berita.php'   => 'Berita',
    'galeri.php'   => 'Galeri',
    'brosur'       => [
        'label' => 'Brosur',
        'children' => [
            'brosur.php'                 => 'Semua Brosur',
            'brosur-unit.php?unit=daycare' => 'Brosur Daycare',
            'brosur-unit.php?unit=tkit'    => 'Brosur TKIT',
            'brosur-unit.php?unit=sdit'    => 'Brosur SDIT',
            'brosur-unit.php?unit=smpit'   => 'Brosur SMPIT',
        ]
    ],
    'spmb.php'     => 'SPMB',
    'karir.php'    => 'Karir',
];
