# Panduan hosting — SIT Permata Hati

Diperbarui 7 Oktober 2026. Berlaku untuk satu website yayasan dengan empat unit pada subfolder `/daycare`, `/tkit`, `/sdit`, `/smpit`.

## Pilihan hosting

Harga berikut adalah angka yang tampil di halaman resmi saat diperiksa; final checkout menentukan periode pembayaran, pajak, bonus domain, dan harga perpanjangan. Estimasi tahunan hanya harga bulanan × 12, bukan janji total invoice.

| Pilihan | Harga yang tampil | Estimasi hosting / tahun | Catatan |
| --- | --- | --- | --- |
| **DomaiNesia Nimbus Go — rekomendasi awal** | Rp32.000/bulan; perpanjangan tertulis Rp32.000/bulan | Rp384.000 | 15 GB, RAM 1 GB, database tidak dibatasi, cPanel, SSH dan remote backup. Cocok untuk mulai dengan trafik sekolah yang belum tinggi. |
| DomaiNesia Nimbus Plus | Rp59.000/bulan; perpanjangan tertulis Rp59.000/bulan | Rp708.000 | 25 GB, RAM 2 GB; pilihan saya jika pendaftaran dan banyak admin aktif bersamaan. |
| Rumahweb Small | Promo Rp17.900/bulan; angka normal ditampilkan Rp50.000 | Promo ekuivalen Rp214.800 | RAM 1 GB, database MariaDB tidak dibatasi, SSL dan SSH. Konfirmasi periode promo serta invoice renewal. |
| Hostinger Premium | Rp24.900/bulan untuk 48 bulan; bayar awal Rp1.195.200; renewal Rp84.900/bulan | Renewal ekuivalen Rp1.018.800 | 20 GB, backup mingguan. Pertimbangkan total pembayaran di muka dan kenaikan renewal. |

Sumber: [DomaiNesia](https://www.domainesia.com/hosting/), [Rumahweb](https://www.rumahweb.com/hosting-murah/), [Hostinger](https://www.hostinger.com/id/web-hosting). Pilihan didasarkan pada spesifikasi dan biaya yang dipublikasikan; belum ada benchmark langsung pada akun hosting tersebut.

**Saran:** mulai Nimbus Go untuk anggaran ketat; Nimbus Plus jika anggaran sekitar Rp700 ribu/tahun di luar domain tersedia. Pilih lokasi server Indonesia saat checkout dan konfirmasikan ketersediaannya. Website ini tidak memerlukan VPS atau Node.js. Lima halaman jenjang pada satu domain tetap satu instalasi website; perlu **dua database terpisah**, bukan lima paket hosting. Ukuran source/aset terlacak saat audit sekitar 138 MiB, belum termasuk media upload Railway dan CV.

Rumahweb menerapkan batas inode dan syarat backup pada storage “unlimited”; halaman paket menyebut backup mingguan hanya untuk penggunaan di bawah 5 GB dan/atau 75.000 inode. Pastikan backup sendiri tetap tersedia. Jangan menilai paket dari kata “unlimited” saja.

## 1. Persyaratan sebelum membeli

- Apache/LiteSpeed dengan `.htaccess` dan rewrite, PHP **8.3 atau 8.4**; disarankan 8.4.
- Ekstensi `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `session`, `json`, `iconv`.
- MySQL/MariaDB dengan InnoDB, `utf8mb4`, dua database. Pengguna database harus bisa SELECT/INSERT/UPDATE/DELETE/CREATE/ALTER/INDEX di database aplikasi. Tidak perlu privilege global CREATE DATABASE.
- SSL, File Manager/SFTP, phpMyAdmin, cron/backup; SSH sangat membantu migrasi.
- Batas upload 80 MB, POST 90 MB, memory 256 MB. Provider bisa memiliki batas lebih rendah yang harus disesuaikan lewat panel/support.

## 2. Backup website klien sebelum pindah

1. Sepakati jendela migrasi. Hentikan sementara input admin/pendaftaran ketika mengambil backup final agar data tidak tertinggal di server lama.
2. Ekspor **database aktif yayasan** dan **database aktif unit** dari Railway/phpMyAdmin/MySQL, struktur + data. Jangan memakai `database/school_website.sql` sebagai pengganti data klien: itu berisi instalasi/demo lama.
3. Unduh seluruh isi `APP_UPLOAD_ROOT` server lama, termasuk `public`, `units`, `units-hero`, `units-social`, dan `private/careers`. Pada Railway biasanya berada di volume `/data/uploads`; file ini tidak otomatis ikut Git.
4. Simpan backup database dan CV di tempat privat, di luar `public_html`. Jangan commit atau memasukkannya ke ZIP publik.
5. Catat konfigurasi lama, DNS, kedua nama database dan tanggal backup; simpan server lama sampai uji penerimaan selesai.

## 3. Paket kode dan upload

Paket rilis dibuat dengan `python tools/build_hosting_package.py` dari file yang sudah dilacak Git. Hasilnya `dist/school-website-hosting.zip` beserta SHA256. Paket tidak memuat `.env` aktif, dump database, file CV, `.git`, folder `tmp`, atau tes.

1. Tambahkan domain pada panel hosting. Aktifkan SSL.
2. Pilih PHP 8.4 dan ekstensi di atas melalui PHP Selector. Terapkan batas upload/memory lewat panel; `.user.ini` menyediakan nilai awal untuk PHP-FPM/CGI.
3. Upload ZIP ke document root domain (`public_html` atau folder domain addon), lalu ekstrak **isinya langsung** di document root. `index.php`, `backend`, `frontend`, dan empat folder unit harus sejajar.
4. Tampilkan hidden files dan pastikan `.htaccess` ikut ter-upload. Hapus ZIP dari document root setelah ekstraksi.
5. Upload kode menggunakan File Manager/SFTP; file 644, direktori 755. Berikan write permission hanya pada direktori upload milik akun hosting. Hindari 777.

Untuk update berikutnya, timpa file kode tanpa menghapus `.env` dan folder upload. Backup dahulu karena versi baru dapat menambah kolom/tabel otomatis.

## 4. Database dan konfigurasi

1. Buat dua database lewat panel, contoh `akun_yayasan` dan `akun_units`. Nama asli biasanya memakai prefiks username hosting.
2. Buat pengguna database dan berikan hak akses pada masing-masing database. Boleh memakai satu pengguna untuk keduanya atau pengguna berbeda.
3. Di phpMyAdmin pilih database tujuan sebelum Import backup yang sesuai. Jika dump berisi `CREATE DATABASE` atau `USE` dengan nama Railway/lokal, hapus dua perintah itu pada **salinan dump** sebelum import. Jangan impor keduanya ke database yang sama: ada nama tabel yang dipakai oleh kedua skema.
4. Salin `.env.example` menjadi `.env`, lalu isi:

```dotenv
APP_ENV=production
APP_URL=https://sekolah.example
APP_TIMEZONE=Asia/Jakarta
APP_ALLOW_DATABASE_CREATE=0
DB_HOST=localhost
DB_PORT=3306
DB_NAME=akun_yayasan
DB_USER=akun_pengguna_yayasan
DB_PASS="ISI_PASSWORD_DATABASE_YAYASAN"
UNIT_DB_NAME=akun_units
UNIT_DB_HOST=localhost
UNIT_DB_PORT=3306
UNIT_DB_USER=akun_pengguna_unit
UNIT_DB_PASS="ISI_PASSWORD_DATABASE_UNIT"
APP_UPLOAD_ROOT=/home/akun/school_uploads
```

`APP_URL` adalah domain asli, tanpa slash terakhir. Bila sengaja memakai subfolder, masukkan juga subfoldernya. `UNIT_DB_*` selain nama boleh dikosongkan jika kredensialnya sama dengan `DB_*`. Jangan menyalin `MYSQLHOST`/`RAILWAY_PUBLIC_DOMAIN` dari Railway ke shared hosting.

`APP_UPLOAD_ROOT` sebaiknya folder di luar `public_html`, misalnya `/home/akun/school_uploads`, dapat ditulis PHP. Salin isi volume upload lama ke sini dengan struktur folder yang sama. URL `/media/public/...` dan `/media/units/...` disajikan aplikasi; `/media/private/...` ditolak. CV hanya dapat diunduh melalui CMS berizin.

## 5. Akun dan migrasi struktur

Pada database yang diimpor, akun beserta hash password tetap ikut. Buka `/portal/admin` dan setiap halaman login CMS unit sekali untuk menjalankan migrasi idempoten. Migrasi tidak menghapus konten/pendaftar.

Untuk instalasi **baru**, buat dua database kosong terlebih dahulu. Isi `PORTAL_ADMIN_PASSWORD` dan `UNIT_SUPERADMIN_PASSWORD` dengan password privat sementara di `.env`, lalu buka halaman login yayasan dan unit. Akun bootstrap adalah `admin` dan `superadmin`; password demo tidak diaktifkan pada production. Login superadmin unit dapat mengganti dua akun awal melalui menu Akun → Ganti Admin Yayasan & Superadmin. Alternatif shell dijelaskan di `ADMIN_ACCESS_RECOVERY.md`.

Setelah login yayasan, buka **Manajemen Pengguna** untuk membuat akun humas, kasir, dan petugas masing-masing unit. Password yang sudah dikelola dari halaman ini tidak ditimpa lagi oleh bootstrap `.env`. Mengubah role, unit, status, atau password akun unit mengakhiri sesi lamanya pada request berikutnya.

## 6. URL domain lama dan media

Link upload baru memakai `/media/...`. Data lama mungkin masih menyimpan URL absolut domain Railway atau `localhost`. Sebelum mengganti URL, buat backup kedua database. Minta migrator melakukan search/replace domain lama **hanya pada kolom konten dan URL**, bukan pada password, data pendaftar atau dokumen. Tabel yang umum: `news.image`, `site_content_items.image/link_url/description`, `site_profile.image/history_content`, `hero_media.media_url/poster_url/cta_url`, `brochures.cover_image/file_url`, dan `unit_content.image/body` serta `unit_settings.setting_value` di database unit. Periksa tautan satu per satu setelah migrasi; jangan mengubah tautan Instagram/YouTube.

## 7. Checklist sebelum DNS dialihkan

- `/health` mengembalikan HTTP 200 dan status `ok`.
- Homepage, profil, berita, galeri, brosur, karir, dan SPMB yayasan serta empat unit dapat dibuka.
- Admin yayasan bisa membuka akun yayasan/unit, membuat akun uji, mengubah role, menonaktifkan, mengaktifkan, dan mereset password.
- Role terbatas hanya membuka menu dan unitnya; URL/menu lain serta POST langsung ditolak. Sesi lama tidak bisa dipakai setelah akses dicabut.
- Setiap unit mengirim satu formulir pendaftaran berlabel UJI; datanya muncul di CMS yang sesuai. Hapus data uji lewat prosedur admin/database setelah verifikasi.
- Upload gambar → buka URL `/media/...`; upload PDF brosur → unduh; video bisa diputar dan seek. Uji CV hanya dari CMS yang berizin.
- Login/perubahan data keempat unit muncul di Audit Aktivitas yayasan; filter dan CSV bekerja.
- `/.env`, `/database/`, `/tools/`, `/tmp/`, `/media/private/`, serta `/frontend/assets/uploads/private/` ditolak (403/404). Folder tidak menampilkan directory listing.
- Logout, login ulang, menu mobile, tautan WhatsApp/Instagram/Maps, serta tombol submit diuji pada desktop dan HP. API/embed eksternal tetap tergantung layanan penyedianya.

Setelah lulus, ubah A record/nameserver, periksa SSL pada domain utama dan www, lalu ambil backup pertama di hosting baru. Jangan aktifkan page cache/CDN cache untuk `/portal/*`, `/*/admin/*`, endpoint formulir, atau unduhan CV.

## 8. Perawatan dan pemulihan

Backup harian dua database dan folder upload; simpan salinan di luar provider. Jadwalkan uji restore. Ekspor audit sebelum arsip rutin; aplikasi tidak menyediakan tombol menghapus audit. Audit unit sebelum fitur ini dipasang tidak bisa direkonstruksi; riwayat yayasan yang memang tersimpan sebelumnya ditampilkan.

Jika 500: periksa error log hosting, versi PHP, aturan `.htaccess`, dan permission. Jika 503: cocokkan prefiks DB, username, password, privilege database dan ekstensi `pdo_mysql`. Jika 404 clean URL: periksa document root dan rewrite. Jika gambar upload 404: periksa `APP_UPLOAD_ROOT` dan salinan volume. Jika POST hilang atau 413: tingkatkan batas upload/POST pada panel.

Rollback: pulihkan paket kode sebelumnya **beserta** backup kedua database dan media yang konsisten; lalu uji login serta formulir. Jangan rollback database setelah ada data klien baru tanpa mengekspor perubahan tersebut terlebih dahulu.
