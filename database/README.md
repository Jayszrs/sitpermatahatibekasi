# Sinkronisasi database tim

Perubahan struktur database tidak hanya disimpan di dump utama. Saat website dibuka, `backend/config/database.php` otomatis menjalankan migrasi idempotent di `backend/migrations/public_schema.php`.

Alur untuk setiap fork:

1. Pull/merge perubahan Git seperti biasa.
2. Pastikan `.env` mengarah ke database lokal masing-masing.
3. Buka website sekali. Kolom/tabel baru akan dibuat tanpa menghapus data lama.
4. Riwayat versi tercatat pada tabel `schema_migrations`.

`database/school_website.sql` tetap dipakai untuk instalasi baru. Data contoh SPMB tidak dimasukkan otomatis ke data produksi; jalankan `database/seeders/spmb_examples.sql` secara manual bila membutuhkan data demo.

Catatan: Git menyinkronkan skema, migrasi, dan seeder. Isi database lokal yang dibuat pengguna tidak ikut terkirim ke fork lain kecuali diekspor dan dikomit secara sengaja.

## Sinkronisasi media CMS

Upload publik disimpan di `frontend/assets/uploads/` dan tidak di-ignore, jadi
gambar berita, galeri, hero, serta brosur dapat ikut commit. Saat membagikan
konten CMS ke fork lain, commit file medianya bersama migrasi/seeder atau dump
SQL yang berisi data kontennya. Selalu periksa `git status` sebelum push.

Jangan commit `frontend/assets/uploads/careers/`. Folder tersebut berisi CV
pelamar dan sudah dikecualikan lewat `.gitignore`.

Nama database tetap dapat menggunakan `school_website`. URL aplikasi dan cookie
sesi dihitung otomatis dari lokasi folder, sedangkan nama database mengikuti nilai
`DB_NAME` pada `.env`.
