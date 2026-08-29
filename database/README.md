# Sinkronisasi database tim

Perubahan struktur database tidak hanya disimpan di dump utama. Saat website dibuka, `backend/config/database.php` otomatis menjalankan migrasi idempotent di `backend/migrations/public_schema.php`.

Alur untuk setiap fork:

1. Pull/merge perubahan Git seperti biasa.
2. Pastikan `.env` mengarah ke database lokal masing-masing.
3. Buka website sekali. Kolom/tabel baru akan dibuat tanpa menghapus data lama.
4. Riwayat versi tercatat pada tabel `schema_migrations`.

`database/school_website.sql` tetap dipakai untuk instalasi baru. Data contoh SPMB tidak dimasukkan otomatis ke data produksi; jalankan `database/seeders/spmb_examples.sql` secara manual bila membutuhkan data demo.

Catatan: Git menyinkronkan skema, migrasi, dan seeder. Isi database lokal yang dibuat pengguna tidak ikut terkirim ke fork lain kecuali diekspor dan dikomit secara sengaja.
