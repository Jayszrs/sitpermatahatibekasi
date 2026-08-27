# School Website — Panduan Instalasi (XAMPP)

## 1. Salin Folder Project
Salin folder `school-website` ke dalam:
```
C:\xampp\htdocs\school-website
```

## 2. Jalankan XAMPP
Buka XAMPP Control Panel, lalu **Start** service:
- Apache
- MySQL

## 3. Buat & Import Database
1. Buka browser, akses `http://localhost/phpmyadmin`
2. Klik **New**, buat database bernama `school_website` (atau langsung import, karena file SQL sudah membuat database-nya).
3. Klik tab **Import** → pilih file `database/school_website.sql` → klik **Go**.

## 4. Buka Website
Akses melalui browser:
```
http://localhost/school-website/
```

## Portal Internal (CRM)

Portal tidak ditampilkan pada navigasi website publik. Semua role masuk melalui satu URL:

`http://localhost/school-website/portal/admin`

Login menggunakan username (tanpa email). Menu di dashboard otomatis disesuaikan dengan role Admin, Humas, atau Kasir SPMB.

Akun awal:

- `admin` / `AdminPHB#2026`
- `humas` / `HumasPHB#2026`
- `kasir` / `KasirPHB#2026`

Akun awal dibuat otomatis ketika salah satu halaman portal pertama kali dibuka. Admin dapat mengganti password, menambah akun, atau menonaktifkan akun melalui menu **Manajemen Pengguna**.

## 5. Konfigurasi (opsional)
Semua pengaturan umum (nama sekolah, alamat, no WhatsApp, dsb) ada di:
```
includes/config.php
```

## 6. Mengganti Gambar
Gambar saat ini menggunakan placeholder online (placehold.co) agar mudah dilihat dan diganti nanti.
Untuk mengganti dengan foto asli:
1. Simpan foto ke folder `assets/images/` (atau subfolder terkait).
2. Ganti path `src="https://placehold.co/..."` pada file PHP terkait dengan path lokal, misalnya:
   `src="<?php echo SITE_URL; ?>/assets/images/hero.jpg"`
3. Logo sekolah: simpan sebagai `assets/images/logo.png` (ukuran disarankan 200x200px transparan).

## Struktur Project
```
school-website/
├── index.php, tentang.php, unit.php, program.php, prestasi.php,
│   kegiatan.php, galeri.php, berita.php, spmb.php, kontak.php
├── detail-berita.php, galeri-detail.php, form-spmb.php
├── includes/ (header.php, footer.php, config.php)
├── assets/css/style.css
├── assets/images/
└── database/school_website.sql
```
