# Railway Demo V2

Dokumen ini khusus untuk branch `deploy/railway-demo-v2`. Jangan merge branch ini ke `main` sebelum konfigurasi demo ditinjau dan akun bawaan diganti.

## Mengapa deployment lama gagal

| Pendekatan lama | Gejala | Perubahan V2 |
|---|---|---|
| Paket APT `php8.4-mysql` | Paket tidak tersedia di image deploy Railpack sehingga build berhenti dengan exit 100. | PHP dikunci ke 8.3 dan ekstensi dinyatakan sebagai requirement Composer. Railpack membaca `ext-pdo_mysql`, `ext-mbstring`, dan `ext-fileinfo` langsung dari `composer.json`. |
| Docker + Apache | `pdo_mysql` berhasil dikompilasi, tetapi container crash karena lebih dari satu MPM Apache dimuat. | Dockerfile, Apache, dan Nixpacks tidak digunakan. Runtime mengikuti Railpack + FrankenPHP. |
| `RAILPACK_PHP_EXTENSIONS` dan paket deploy APT | Hasilnya bergantung pada nama paket OS dan image runtime. | Kedua variable tersebut sengaja tidak dipasang. Composer menjadi sumber kebutuhan ekstensi. |
| Koneksi dengan fallback `localhost/root` | Railway dapat diam-diam mencoba database di container web dan exception terlihat ke pengunjung. | Railway memakai `MYSQL*`; XAMPP memakai `.env`. Konfigurasi yang hilang atau koneksi gagal menghasilkan HTTP 503 dan log tanpa credential. |
| Migrasi mengasumsikan dump sudah di-import | Database kosong tidak mempunyai tabel fondasi. | Migrasi membuat tabel fondasi, tabel konten, karir, lalu seed demo secara idempoten. Dump Downloads dan data privat tidak digunakan. |
| Upload ditulis ke source container | File hilang setelah redeploy; CV berpotensi berada di web root. | Upload memakai `/data/uploads`; media publik disajikan di `/media`, sedangkan CV berada di `private/careers` dan hanya diunduh melalui portal terautentikasi. |

Referensi perilaku Railpack: PHP dideteksi dari `index.php`/`composer.json`, versi dibaca dari Composer, ekstensi Composer dipasang otomatis, dan `Caddyfile` serta `php.ini` di root menggantikan konfigurasi default. Lihat dokumentasi resmi [Railpack PHP](https://railpack.com/languages/php) dan [Railway Railpack](https://docs.railway.com/builds/railpack).

## Arsitektur Railway

- Project privat: `sit-permata-hati-demo-v2`
- Environment: `production`
- Web service: `web-railpack-v2`, source repo `Jayszrs/sitpermatahatibekasi`, branch `deploy/railway-demo-v2`
- Database service: `mysql-demo-v2`, dengan volume persisten pada path data MySQL
- Volume web: `uploads-v2`, mount `/data`
- Main database: nilai `MYSQLDATABASE` dari service MySQL
- Database unit: `school_units_portal` pada server MySQL yang sama

Project Railway lama tidak boleh diubah atau dihapus. Service lama `render-db-bootstrap-temp` juga tidak perlu disentuh selama project baru dapat dibuat.

## Variable web

Gunakan reference variable ke service `mysql-demo-v2`, bukan menyalin nilainya:

```text
MYSQLHOST=${{mysql-demo-v2.MYSQLHOST}}
MYSQLPORT=${{mysql-demo-v2.MYSQLPORT}}
MYSQLUSER=${{mysql-demo-v2.MYSQLUSER}}
MYSQLPASSWORD=${{mysql-demo-v2.MYSQLPASSWORD}}
MYSQLDATABASE=${{mysql-demo-v2.MYSQLDATABASE}}
APP_ENV=demo
APP_TIMEZONE=Asia/Jakarta
UNIT_DB_NAME=school_units_portal
APP_UPLOAD_ROOT=/data/uploads
RAILPACK_PHP_ROOT_DIR=/app
```

Jangan menambahkan `RAILPACK_DEPLOY_APT_PACKAGES` atau `RAILPACK_PHP_EXTENSIONS`. Jangan menaruh nilai secret dalam commit, PR, screenshot publik, atau log dukungan.

## Pengaturan service

Web service:

- Builder: Railpack (otomatis)
- Healthcheck path: `/health`
- Healthcheck timeout: 120 detik untuk bootstrap database pertama
- Restart policy: `ON_FAILURE`, maksimum 3 retry
- Replica: 1
- Sleeping/serverless: nonaktif
- Domain: domain Railway yang dibuat dari menu Networking
- Volume `uploads-v2`: mount `/data`

MySQL service:

- Volume persisten: mount ke data directory yang ditetapkan template MySQL Railway
- Tidak memiliki domain publik; web mengakses private network
- Tunggu status sehat sebelum web dideploy

Railway menyatakan volume baru tersedia saat container berjalan, bukan ketika build. Karena itu tidak ada build step yang menulis ke `/data`. Lihat [Using Volumes](https://docs.railway.com/volumes).

## Bootstrap dan keamanan data demo

Saat request aplikasi pertama masuk, migrasi idempoten membuat schema utama. Website Daycare, TKIT, SDIT, dan SMPIT membuat tabelnya di `UNIT_DB_NAME`. Menjalankan bootstrap berulang tidak menghapus atau menduplikasi data dasar.

Seed dan akun demo lama dipertahankan sesuai keputusan untuk lingkungan demo internal. Lokasi akun tetap pada catatan source/README yang sudah ada; credential sengaja tidak disalin ke dokumen ini. Deployment ini **demo-only**, tidak boleh dipublikasikan sebagai production sebelum akun bawaan diganti dan seluruh data contoh ditinjau.

## Verifikasi lokal

Gunakan dua database disposable bernama dengan prefix `codex_railway_`. Test helper menolak nama lain agar database utama tidak tersentuh.

```powershell
composer validate --strict
composer install --no-dev --prefer-dist --no-interaction
php -r "exit(in_array('mysql', PDO::getAvailableDrivers(), true) ? 0 : 1);"

$env:DB_NAME='codex_railway_main_v2'
$env:UNIT_DB_NAME='codex_railway_units_v2'
php tests/deployment/main_bootstrap.php
php tests/deployment/main_bootstrap.php
foreach ($unit in 'daycare','tkit','sdit','smpit') { php tests/deployment/unit_bootstrap.php $unit }
foreach ($unit in 'daycare','tkit','sdit','smpit') { php tests/deployment/unit_bootstrap.php $unit }
```

Isi `DB_HOST`, `DB_PORT`, `DB_USER`, dan `DB_PASS` melalui environment lokal sebelum menjalankan test. Buat dan hapus kedua schema disposable dengan akun lokal yang sesuai; jangan memakai `school_website`.

## Verifikasi Railway

1. Pastikan build log menyebut Railpack/Composer dan instalasi `pdo_mysql`. Log tidak boleh memuat `php8.4-mysql`, Docker build, Apache, atau MPM.
2. Pastikan `/health` mengembalikan HTTP 200 dengan `status: ok`; response hanya menampilkan boolean pemeriksaan, tanpa DSN atau secret.
3. Crawl homepage, profil, program, prestasi, kontak, berita, galeri, kegiatan, karir, brosur, SPMB, login/redirect portal, dan seluruh halaman empat unit. Tidak boleh ada 5xx, loop redirect, mixed content, atau asset 404.
4. Uji akun demo untuk role admin, humas, dan kasir serta akun tiap unit memakai catatan source yang sudah ada. Pastikan menu yang tidak sesuai role ditolak.
5. Unggah satu gambar uji berlabel demo dan satu CV uji tanpa data pribadi. Pastikan gambar dapat dibaca, CV tidak dapat diakses tanpa sesi portal, lalu hapus data uji.
6. Redeploy web. Pastikan gambar pada volume tetap tersedia dan `/health` kembali 200.
7. Trigger satu redeploy lagi dan ulangi health/crawl. Kriteria selesai adalah dua deployment berturut-turut berstatus sehat.

Jika integrasi Railway belum memiliki akses ke repo `Jayszrs/sitpermatahatibekasi`, izinkan repo tersebut satu kali dari pengaturan GitHub Railway. Jika connector tidak dapat membuat volume, pasang `mysql-demo-v2` ke volume database dan `web-railpack-v2` ke `uploads-v2` satu kali melalui dashboard sesuai mount path di atas.
