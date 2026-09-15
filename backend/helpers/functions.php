<?php
/**
 * Pengaturan Umum Website dan Fungsi Pembantu (Helpers)
 */

require_once __DIR__ . '/../config/storage.php';

// ==== PENGATURAN UMUM WEBSITE ====
define('SITE_NAME', 'SIT Permata Hati Bekasi');
define('SITE_TAGLINE', 'Sekolah Islam Terpadu - Sholeh, Cerdas, Mandiri, dan Berwawasan Global');
if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost/' . basename(dirname(__DIR__, 2)));
define('SITE_PHONE', '(021) 1234-5678');
define('SITE_WHATSAPP', '6281234567890');
// Nomor awal memakai kontak pusat. Masing-masing unit dapat diubah dari
// Portal pada menu Unit Sekolah tanpa mengubah source code.
define('SITE_DAYCARE_WHATSAPP', SITE_WHATSAPP);
define('SITE_TKIT_WHATSAPP', SITE_WHATSAPP);
define('SITE_SDIT_WHATSAPP', SITE_WHATSAPP);
define('SITE_SMPIT_WHATSAPP', SITE_WHATSAPP);
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
define('SITE_YOUTUBE', 'https://www.youtube.com/@sitpermatahatibekasi5399');
define('SITE_DAYCARE_INSTAGRAM', 'https://www.instagram.com/daycarepermatahati.bekasi/');
define('SITE_TKIT_INSTAGRAM', 'https://www.instagram.com/tkitpermatahatibekasi/');
define('SITE_SDIT_INSTAGRAM', 'https://www.instagram.com/sditphbekasi/');
define('SITE_SMPIT_INSTAGRAM', 'https://www.instagram.com/smpit_permatahati/?hl=id');
define('SITE_SDIT_YOUTUBE', 'https://www.youtube.com/@sditpermatahatibekasi99');
define('SITE_SMPIT_YOUTUBE', 'https://www.youtube.com/@smpit_permatahati');
define('SITE_DAYCARE_YOUTUBE', SITE_YOUTUBE);
define('SITE_TKIT_YOUTUBE', SITE_YOUTUBE);

// Helper untuk output aman (mencegah XSS)
function esc(?string $string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Instagram punya endpoint iframe embed langsung (.../p/{kode}/embed/ atau
// .../reel/{kode}/embed/) yang bisa dipakai tanpa perlu memuat embed.js sama
// sekali - browser cukup <iframe src="..."> biasa. Ini menyederhanakan galeri
// Instagram: tidak perlu lagi script blockquote + lazy-load kustom, cukup
// pakai loading="lazy" bawaan browser di tag iframe-nya.
function instagram_embed_url(?string $postUrl): ?string {
    $postUrl = trim((string) $postUrl);
    if ($postUrl === '') return null;
    if (!preg_match('~instagram\.com/(p|reel|tv)/([A-Za-z0-9_-]+)~i', $postUrl, $match)) return null;
    return 'https://www.instagram.com/' . $match[1] . '/' . $match[2] . '/embed/';
}

/**
 * Mengambil media publik dari halaman embed Instagram untuk kartu beranda.
 *
 * Embed iframe Instagram sengaja tidak dipakai di beranda: response Instagram
 * menonaktifkan autoplay dan menjalankan banyak request telemetry pihak ketiga.
 * Metadata ini disimpan singkat di /tmp agar halaman tidak meminta Instagram
 * pada setiap kunjungan. Jika post sedang dibatasi/tidak tersedia, pemanggil
 * tetap mendapat fallback card yang menaut ke post aslinya.
 *
 * @return array{image:?string,video:?string,profile_image:?string,username:?string,caption:?string,is_video:bool}|null
 */
function instagram_public_media(?string $postUrl): ?array {
    $embedUrl = instagram_embed_url($postUrl);
    if (!$embedUrl || !function_exists('curl_init')) return null;

    $cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'instagram-media';
    $cacheFile = $cacheDirectory . DIRECTORY_SEPARATOR . sha1($embedUrl) . '.json';
    $cacheTtl = 6 * 60 * 60;
    $staleCached = null;
    if (is_file($cacheFile)) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        // Cache lama belum menyimpan avatar akun; refresh satu kali agar ikon
        // generik dapat diganti foto profil Instagram yang sebenarnya.
        if (is_array($cached) && !empty($cached['image']) && ($cached['cache_version'] ?? 0) >= 2) {
            $staleCached = $cached;
            if (filemtime($cacheFile) >= time() - $cacheTtl) return $cached;
        }
    }

    $curl = curl_init($embedUrl);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 7,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SITPermataHati/1.0; +' . SITE_URL . ')',
        CURLOPT_HTTPHEADER => ['Accept-Language: id-ID,id;q=0.9,en;q=0.7'],
    ]);
    $html = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    $result = null;
    if (is_string($html) && $status === 200
        && ($contextLiteral = instagram_extract_json_string($html, '"contextJSON":')) !== null) {
        $contextJson = json_decode($contextLiteral, true);
        $context = is_string($contextJson) ? json_decode($contextJson, true) : null;
        $media = $context['gql_data']['shortcode_media'] ?? null;
        $postOwner = is_array($media) && is_array($media['owner'] ?? null) ? $media['owner'] : [];

        // Carousel post menyimpan media aktual di edge_sidecar_to_children.
        if (is_array($media) && !empty($media['edge_sidecar_to_children']['edges'][0]['node'])) {
            $media = $media['edge_sidecar_to_children']['edges'][0]['node'];
        }

        if (is_array($media)) {
            $image = instagram_safe_cdn_url($media['display_url'] ?? null);
            $video = instagram_safe_cdn_url($media['video_url'] ?? null);
            $mediaOwner = is_array($media['owner'] ?? null) ? $media['owner'] : [];
            $profileImage = instagram_safe_cdn_url($mediaOwner['profile_pic_url'] ?? ($postOwner['profile_pic_url'] ?? null));
            $username = trim((string) ($mediaOwner['username'] ?? ($postOwner['username'] ?? '')));
            $caption = trim((string) ($media['edge_media_to_caption']['edges'][0]['node']['text'] ?? ''));
            if ($image) {
                $result = [
                    'cache_version' => 2,
                    'image' => $image,
                    'video' => $video,
                    'profile_image' => $profileImage,
                    'username' => $username !== '' ? $username : null,
                    'caption' => $caption !== '' ? $caption : null,
                    'is_video' => !empty($media['is_video']),
                ];
            }
        }
    }

    // Gangguan Instagram tidak boleh membuat galeri yang sebelumnya valid
    // tiba-tiba hilang. Pakai data terakhir yang berhasil sebagai fallback.
    if (!$result && $staleCached) return $staleCached;

    if (!is_dir($cacheDirectory)) @mkdir($cacheDirectory, 0775, true);
    if (is_dir($cacheDirectory)) {
        // Cache hasil kosong lebih singkat supaya post yang sesaat gagal bisa
        // pulih sendiri tanpa membuat request berulang pada setiap page view.
        if ($result) {
            @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_SLASHES), LOCK_EX);
        } else {
            @file_put_contents($cacheFile, '{}', LOCK_EX);
            @touch($cacheFile, time() - $cacheTtl + 15 * 60);
        }
    }
    return $result;
}

/** Ambil satu JSON string besar tanpa regex/backtracking pada HTML Instagram. */
function instagram_extract_json_string(string $html, string $key): ?string {
    $keyPosition = strpos($html, $key);
    if ($keyPosition === false) return null;
    $start = $keyPosition + strlen($key);
    if (($html[$start] ?? '') !== '"') return null;

    $length = strlen($html);
    for ($index = $start + 1; $index < $length; $index++) {
        if ($html[$index] === '\\') {
            $index++;
            continue;
        }
        if ($html[$index] === '"') return substr($html, $start, $index - $start + 1);
    }
    return null;
}

function instagram_safe_cdn_url($url): ?string {
    $url = is_string($url) ? html_entity_decode($url, ENT_QUOTES, 'UTF-8') : '';
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) return null;
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host !== 'cdninstagram.com' && !str_ends_with($host, '.cdninstagram.com')
        && $host !== 'fbcdn.net' && !str_ends_with($host, '.fbcdn.net')) return null;
    return $url;
}

/** Ambil username profil dari URL Instagram resmi. */
function instagram_profile_username(?string $profileUrl): ?string {
    $path = trim((string) parse_url(trim((string) $profileUrl), PHP_URL_PATH), '/');
    $username = explode('/', $path)[0] ?? '';
    return preg_match('/^[A-Za-z0-9._]+$/', $username) ? strtolower($username) : null;
}

/**
 * Sisakan hanya post Instagram yang masih tersedia dan benar-benar berasal
 * dari akun unit yang sesuai. Media hasil verifikasi disertakan agar halaman
 * tidak pernah menampilkan foto lokal sebagai pengganti post yang gagal.
 *
 * @param array<int,array<string,mixed>> $rows
 * @param array<string,string> $expectedUsernames username per scope
 * @return array<int,array<string,mixed>>
 */
function instagram_verified_gallery(array $rows, array $expectedUsernames, int $limit = 24): array {
    $verified = [];
    foreach ($rows as $row) {
        if (($row['media_type'] ?? '') !== 'embed' || empty($row['instagram_url'])) continue;
        $media = instagram_public_media((string) $row['instagram_url']);
        if (!$media || empty($media['image']) || empty($media['username'])) continue;

        $scope = strtolower((string) ($row['scope'] ?? ''));
        $expected = strtolower((string) ($expectedUsernames[$scope] ?? ''));
        if ($expected === '' || strtolower((string) $media['username']) !== $expected) continue;

        // Reel tanpa video_url publik hanya akan terlihat seperti poster diam.
        // Jangan tampilkan kartu semacam itu sebagai video yang seolah rusak.
        if (!empty($media['is_video']) && empty($media['video'])) continue;

        $row['public_media'] = $media;
        $verified[] = $row;
        if (count($verified) >= $limit) break;
    }
    return $verified;
}

/**
 * Ambil video terbaru dari halaman channel YouTube publik tanpa API key.
 * Hasil disimpan singkat agar render halaman tetap cepat dan tahan gangguan.
 *
 * @return array<int,array{id:string,title:string,url:string,embed_url:string,thumbnail:string}>
 */
function youtube_public_videos(?string $channelUrl, int $limit = 2): array {
    $channelUrl = rtrim(trim((string) $channelUrl), '/');
    $host = strtolower((string) parse_url($channelUrl, PHP_URL_HOST));
    if ($channelUrl === '' || !in_array($host, ['youtube.com', 'www.youtube.com'], true) || !function_exists('curl_init')) return [];
    $limit = max(1, min($limit, 8));
    $videosUrl = preg_replace('~/videos$~', '', $channelUrl) . '/videos';

    $cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'youtube-media';
    $cacheFile = $cacheDirectory . DIRECTORY_SEPARATOR . sha1($videosUrl) . '.json';
    $cacheTtl = 60 * 60;
    if (is_file($cacheFile) && filemtime($cacheFile) >= time() - $cacheTtl) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) return array_slice($cached, 0, $limit);
    }

    $curl = curl_init($videosUrl);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SITPermataHati/1.0; +' . SITE_URL . ')',
        CURLOPT_HTTPHEADER => ['Accept-Language: id-ID,id;q=0.9,en;q=0.7'],
    ]);
    $html = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if (!is_string($html) || $status !== 200) return [];

    preg_match_all('/"videoId":"([A-Za-z0-9_-]{11})"/', $html, $matches);
    $videoIds = array_values(array_unique($matches[1] ?? []));
    $videos = [];
    foreach ($videoIds as $videoId) {
        $position = strpos($html, '"videoId":"' . $videoId . '"');
        $fragment = $position === false ? '' : substr($html, $position, 8000);
        preg_match('/"title":(?:\{"runs":\[\{"text":"|\{"content":")((?:\\\\.|[^"\\\\])*)"/', $fragment, $titleMatch);
        $title = isset($titleMatch[1]) ? json_decode('"' . $titleMatch[1] . '"') : null;
        if (!is_string($title) || trim($title) === '') $title = 'Video terbaru SIT Permata Hati';
        $videos[] = [
            'id' => $videoId,
            'title' => trim($title),
            'url' => 'https://www.youtube.com/watch?v=' . $videoId,
            'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $videoId . '?autoplay=1&mute=1&loop=1&playlist=' . $videoId . '&playsinline=1&rel=0',
            'thumbnail' => 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg',
        ];
        if (count($videos) >= 8) break;
    }

    if (!is_dir($cacheDirectory)) @mkdir($cacheDirectory, 0775, true);
    if (is_dir($cacheDirectory)) @file_put_contents($cacheFile, json_encode($videos, JSON_UNESCAPED_SLASHES), LOCK_EX);
    return array_slice($videos, 0, $limit);
}

// Tambahkan versi berdasarkan waktu perubahan file agar browser tidak memakai
// CSS/JS lama setelah source code diperbarui dari Git.
function asset_url(string $relativePath): string {
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $optimizedRelative = preg_replace('/\.(?:jpe?g|png)$/i', '.optimized.webp', $relativePath);
    $optimizedAbsolute = $optimizedRelative
        ? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $optimizedRelative)
        : '';
    if ($optimizedAbsolute !== '' && is_file($optimizedAbsolute)) {
        $relativePath = $optimizedRelative;
        $absolutePath = $optimizedAbsolute;
    }
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : '1';
    return SITE_URL . '/' . $relativePath . '?v=' . rawurlencode($version);
}

/**
 * Membuat URL media CMS tetap portabel ketika project dipindah folder/host.
 * URL localhost lama dan URL absolut hasil upload dinormalisasi ke SITE_URL
 * saat ini. Jika file lokalnya tidak ada, gambar bawaan dipakai sebagai
 * fallback agar kartu publik tidak menampilkan ikon gambar rusak.
 */
function public_media_url(?string $url, ?string $fallback = null): string {
    $fallback ??= SITE_URL . '/frontend/assets/images/school/hero-school.png';
    $value = trim((string) $url);
    if ($value === '') {
        $value = $fallback;
        $fallback = SITE_URL . '/frontend/assets/images/school/hero-school.optimized.webp';
    }

    $normalized = str_replace('\\', '/', $value);
    if (str_starts_with($normalized, '/') && str_contains($normalized, '/media/')) return $normalized;
    $marker = '/frontend/assets/';
    $markerPosition = strpos($normalized, $marker);
    if ($markerPosition !== false) {
        $assetPath = substr($normalized, $markerPosition);
        $pathOnly = (string) (parse_url($assetPath, PHP_URL_PATH) ?: $assetPath);
        $absolutePath = dirname(__DIR__, 2) . str_replace('/', DIRECTORY_SEPARATOR, $pathOnly);
        $optimizedPath = preg_replace('/\\.(?:jpe?g|png)$/i', '.optimized.webp', $pathOnly);
        $optimizedAbsolute = $optimizedPath ? dirname(__DIR__, 2) . str_replace('/', DIRECTORY_SEPARATOR, $optimizedPath) : '';
        if ($optimizedAbsolute !== '' && is_file($optimizedAbsolute)) {
            return SITE_URL . $optimizedPath . '?v=' . rawurlencode((string)filemtime($optimizedAbsolute));
        }
        return is_file($absolutePath) ? SITE_URL . $assetPath : $fallback;
    }

    if (str_starts_with($normalized, 'frontend/assets/')) {
        return public_media_url(SITE_URL . '/' . $normalized, $fallback);
    }

    // URL eksternal (misalnya CDN resmi) tetap diizinkan.
    if (preg_match('~^https?://~i', $normalized)) return $normalized;
    return $fallback;
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
            'youtube' => SITE_DAYCARE_YOUTUBE,
            'whatsapp' => SITE_DAYCARE_WHATSAPP,
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
            'youtube' => SITE_TKIT_YOUTUBE,
            'whatsapp' => SITE_TKIT_WHATSAPP,
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
            'youtube' => SITE_SDIT_YOUTUBE,
            'whatsapp' => SITE_SDIT_WHATSAPP,
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
            'youtube' => SITE_SMPIT_YOUTUBE,
            'whatsapp' => SITE_SMPIT_WHATSAPP,
        ],
    ];
}

/** URL embed peta ringan tanpa memuat Google Maps JavaScript atau API key. */
function openstreetmap_embed_url(string $latitude, string $longitude): string {
    $lat = filter_var($latitude, FILTER_VALIDATE_FLOAT);
    $lon = filter_var($longitude, FILTER_VALIDATE_FLOAT);
    if ($lat === false || $lon === false) return 'https://www.openstreetmap.org/export/embed.html?layer=mapnik';

    $lat = (float) $lat;
    $lon = (float) $lon;
    $bbox = implode(',', [
        number_format($lon - 0.006, 6, '.', ''),
        number_format($lat - 0.004, 6, '.', ''),
        number_format($lon + 0.006, 6, '.', ''),
        number_format($lat + 0.004, 6, '.', ''),
    ]);
    return 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode($bbox)
        . '&layer=mapnik&marker=' . rawurlencode(number_format($lat, 7, '.', '') . ',' . number_format($lon, 7, '.', ''));
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
        $selected[$slug]['image'] = public_media_url($selected[$slug]['image'], $defaults['image']);
        if (!empty($row['instagram_url'])) $selected[$slug]['instagram'] = $row['instagram_url'];
        if (!empty($row['youtube_url'])) $selected[$slug]['youtube'] = $row['youtube_url'];
        foreach (['whatsapp', 'instagram', 'youtube'] as $contactField) {
            if (empty($selected[$slug][$contactField])) $selected[$slug][$contactField] = $defaults[$contactField];
        }
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
        session_set_cookie_params(['lifetime' => 0, 'path' => defined('APP_COOKIE_PATH') ? APP_COOKIE_PATH : '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https']);
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
    $directory = app_ensure_storage_directory('private/careers');
    $filename = 'cv-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) throw new RuntimeException('CV gagal disimpan.');
    return ['url' => 'private/careers/' . $filename, 'name' => basename((string)($file['name'] ?? 'CV'))];
}

// Helper untuk format tanggal Indonesia
function tanggal_indo(string $tanggal) {
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
