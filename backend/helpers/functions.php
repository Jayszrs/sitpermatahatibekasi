<?php
/**
 * Pengaturan Umum Website dan Fungsi Pembantu (Helpers)
 */

// ==== PENGATURAN UMUM WEBSITE ====
define('SITE_NAME', 'SIT Permata Hati Bekasi');
define('SITE_TAGLINE', 'Sekolah Islam Terpadu - Sholeh, Cerdas, Mandiri, dan Berwawasan Global');
define('SITE_URL', 'http://localhost/school-website');
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
    'kontak.php'   => 'Lokasi Sekolah',
    'alquran.php'  => 'Al Quran',
    'berita.php'   => 'News',
    'brosur'       => [
        'label' => 'Brosur',
        'children' => [
            'galeri.php'    => 'Galeri',
            'kegiatan.php'  => 'Kegiatan',
        ]
    ],
    'spmb.php'     => 'SPMB',
    'karir.php'    => 'Karir',
];
