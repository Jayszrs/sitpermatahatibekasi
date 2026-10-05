# Pemulihan akses admin di Railway

Password bawaan yang tertulis di `README.md` hanya berlaku untuk database lokal/demo. Ketika `RAILWAY_PUBLIC_DOMAIN` tersedia, aplikasi memperlakukan layanan sebagai production. Akun dengan password demo tidak diaktifkan tanpa password production yang dikonfigurasi.

Untuk memulihkan akun **admin yayasan** dan **superadmin unit** pada deployment yang benar:

1. Pastikan domain dan service Railway yang dipakai sekolah sudah benar. Perintah di bawah akan mengubah database yang terhubung ke service tersebut.
2. Atur dua variable privat pada service web Railway: `PORTAL_ADMIN_PASSWORD` dan `UNIT_SUPERADMIN_PASSWORD`. Gunakan dua password baru yang berbeda, masing-masing minimal 12 karakter. Jangan menaruh nilainya di Git, URL, atau pesan publik.
3. Railway akan me-redeploy service setelah variable diperbarui. Kunjungi halaman login agar aplikasi menyinkronkan atau membuat akun `admin` dan `superadmin` dari kedua variable tersebut.
4. Uji login di `/portal/admin` untuk `admin` dan di `/daycare/admin/index.php` (atau unit lain) untuk `superadmin`. Akun superadmin unit dapat memilih keempat unit di CMS.

Jika perlu memaksa pemulihan lewat shell, jalankan `php tools/reset_admin_access.php` untuk memeriksa koneksi, kemudian `php tools/reset_admin_access.php --apply` pada service yang benar. Perintah ini tidak mencetak password.

Script tidak mengubah akun `humas`, `kasir`, atau admin unit lainnya. Bila login masih gagal, periksa apakah domain yang diuji menunjuk ke service dan database yang sama dengan tempat perintah dijalankan.
