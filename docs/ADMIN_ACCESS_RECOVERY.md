# Pemulihan akses admin di Railway

## Mengganti dua akun lama dengan satu ID

Jika masih bisa login sebagai superadmin unit, buka CMS unit → **Akun** → **Ganti Admin Yayasan & Superadmin**. Masukkan ID baru, password baru dua kali, dan password superadmin yang sedang dipakai. Form ini hanya tersedia untuk superadmin dan memeriksa sesi, token CSRF, serta password akun tersebut.

Aplikasi membuat ID baru dengan role `admin` pada portal yayasan (akses penuh) dan `superadmin` pada empat CMS unit. Setelah kedua akun baru terverifikasi, login lama `admin` dan `superadmin` dihapus. Riwayat administrasi tetap tersimpan, sesi login akun yang dihapus tidak dapat digunakan lagi, dan bootstrap tidak membuat ulang kedua login lama. Akun humas, kasir, serta admin per unit tetap tersedia.

Masuk kembali melalui `/portal/admin` dan `/daycare/admin/index.php` menggunakan ID serta password baru yang sama. CMS TKIT, SDIT, dan SMPIT menerima kredensial yang sama. Kedua tabel akun tetap terpisah; bila password diubah kemudian melalui pengelolaan pengguna, ubah pada keduanya agar tetap sama.

Alternatif shell: isi `NEW_ADMIN_USERNAME` dan `NEW_ADMIN_PASSWORD` secara privat pada environment proses, jalankan `php tools/replace_admin_accounts.php` untuk pemeriksaan, lalu `php tools/replace_admin_accounts.php --apply`. Password minimal 8 karakter. Pemeriksaan awal tidak mengubah akun dan username akun lain tidak ditimpa. Perintah boleh dijalankan ulang menggunakan kredensial pengganti yang sama. Script hanya dapat dijalankan lewat CLI.

Kode yang di-push tidak otomatis mengganti akun: penggantian harus dijalankan pada CMS aktif atau lewat shell yang tersambung ke database deployment. Jangan menyatakan kredensial aktif sebelum login berhasil diuji pada domain klien.

## Pemulihan akun bawaan yang belum diganti

Password bawaan yang tertulis di `README.md` hanya berlaku untuk database lokal/demo. Ketika `RAILWAY_PUBLIC_DOMAIN` tersedia, aplikasi memperlakukan layanan sebagai production. Akun dengan password demo tidak diaktifkan tanpa password production yang dikonfigurasi.

Untuk memulihkan akun **admin yayasan** dan **superadmin unit** pada deployment yang benar:

1. Pastikan domain dan service Railway yang dipakai sekolah sudah benar. Perintah di bawah akan mengubah database yang terhubung ke service tersebut.
2. Atur dua variable privat pada service web Railway: `PORTAL_ADMIN_PASSWORD` dan `UNIT_SUPERADMIN_PASSWORD`. Gunakan dua password baru yang berbeda, masing-masing minimal 12 karakter. Jangan menaruh nilainya di Git, URL, atau pesan publik.
3. Railway akan me-redeploy service setelah variable diperbarui. Kunjungi halaman login agar aplikasi menyinkronkan atau membuat akun `admin` dan `superadmin` dari kedua variable tersebut.
4. Uji login di `/portal/admin` untuk `admin` dan di `/daycare/admin/index.php` (atau unit lain) untuk `superadmin`. Akun superadmin unit dapat memilih keempat unit di CMS.

Jika perlu memaksa pemulihan lewat shell, jalankan `php tools/reset_admin_access.php` untuk memeriksa koneksi, kemudian `php tools/reset_admin_access.php --apply` pada service yang benar. Perintah ini tidak mencetak password.

Script tidak mengubah akun `humas`, `kasir`, atau admin unit lainnya. Bila login masih gagal, periksa apakah domain yang diuji menunjuk ke service dan database yang sama dengan tempat perintah dijalankan.
