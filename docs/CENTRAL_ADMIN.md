# Pengguna, role, dan audit terpusat

Masuk melalui `/portal/admin` menggunakan akun dengan role database `admin`, ditampilkan sebagai **Superadmin Yayasan**. Role ini mengelola portal yayasan dan seluruh akun unit. Role `superadmin` di database unit adalah akun berbeda untuk empat CMS unit; akun itu tidak otomatis memperoleh sesi login yayasan.

## Manajemen Pengguna

Pada `/portal/users` tersedia tiga tab:

1. **Akun Yayasan:** tambah, ubah nama/ID, pilih Superadmin Yayasan/Humas/Kasir, reset password, aktifkan/nonaktifkan.
2. **Akun Semua Unit:** cari akun, filter Daycare/TKIT/SDIT/SMPIT, tambah/ubah petugas, pilih unit dan role, reset password, aktifkan/nonaktifkan. Superadmin Semua Unit tidak terikat satu unit.
3. **Role Unit:** buat nama dan kode role, centang menu yang boleh dikelola, ubah role khusus, atau hapus role yang belum dipakai akun. Role bawaan tidak dapat diubah/dihapus.

Untuk reset password, klik **Ubah / Password**, masukkan password baru minimal delapan karakter, lalu **Simpan Akun**. Kosongkan password untuk mempertahankannya. Password tersimpan sebagai hash; password lama tidak ditampilkan.

Perubahan akun unit memutus sesi lama pada request berikutnya. Perubahan izin role juga memutus sesi semua pemakainya. Menonaktifkan akun tidak menghapus data pekerjaan maupun audit. Akun superadmin terakhir dilindungi; superadmin yayasan tidak dapat menonaktifkan atau menurunkan role dirinya sendiri. Perubahan akun yayasan sendiri menjaga sesi saat ini, tetapi sesi lain dengan versi lama ditolak.

Akun yayasan dan unit berada pada tabel terpisah; ID yang sama di keduanya tetap dua akun. Jika ingin mengganti password di kedua portal, lakukan pada kedua tab. Akun yang sudah dikelola di sini tidak akan ditimpa oleh seed password environment pada request berikutnya.

## Batas role unit

| Role | Lingkup | Akses |
| --- | --- | --- |
| Superadmin Semua Unit | Empat unit | Semua menu, termasuk akun unit |
| Admin Unit | Satu unit | Semua menu operasional, tanpa manajemen akun |
| Editor / Humas Unit | Satu unit | Dashboard, konten, galeri, Instagram, karir |
| Petugas SPMB Unit | Satu unit | Dashboard dan pendaftar |
| Role khusus | Satu unit | Dashboard + pilihan menu saat role dibuat |

Setiap izin menu mencakup baca dan pengelolaan menu tersebut; versi ini belum membagi izin baca/simpan/hapus menjadi tiga izin terpisah. Role unit khusus tidak dapat memperoleh izin manajemen akun. Menebak URL, mengganti parameter unit, atau mengirim POST secara langsung tidak melewati pemeriksaan server.

Contoh: buat `editor_kegiatan`, pilih Konten dan Galeri Instagram, kemudian buat akun pada unit TKIT dengan role itu. Akun tersebut tidak boleh membaca pendaftar, mengunduh CV, mengubah kontak, atau mengelola pengguna.

## Audit Aktivitas

Menu `/portal/activity` hanya untuk Superadmin Yayasan. Catatan mencakup identitas pelaku, role saat tindakan terjadi, portal/unit, waktu WIB, tindakan, hasil, serta keterangan singkat.

- Login berhasil/gagal, logout, akses halaman/menu, dan penolakan hak akses.
- Perubahan konten, galeri, Instagram, kontak/hero, pendaftar, lowongan, serta lamaran.
- Pengelolaan akun, role, reset password (tanpa isi password), dan unduhan CV.
- Ekspor audit; CSV dapat difilter menurut portal, unit, pelaku/tindakan, hasil, dan rentang tanggal.

Riwayat yayasan lama diimpor dari `portal_activity_logs` tanpa duplikasi. Audit CMS unit **mulai sejak versi ini diterapkan**; kegiatan lama yang tidak pernah dicatat tidak dapat dimunculkan kembali. Username/role tersimpan sebagai snapshot agar riwayat baru tetap terbaca ketika akun kemudian diubah/dihapus.

CSV memakai UTF-8 dan menetralkan awalan formula spreadsheet. Password, cookie, token sesi, isi CV, dan seluruh payload formulir tidak direkam. Beberapa log yayasan lama dapat memuat nama/judul yang memang dicatat modul asal. Tidak tersedia tombol edit/hapus audit pada CMS; administrator database tetap memiliki kendali atas database, sehingga ini bukan penyimpanan log yang tahan perubahan oleh pemilik server.

## Penambahan struktur otomatis

- Database unit: tabel `unit_roles`; kolom akun `name`, `role_key`, `last_login_at`, `session_version`, `managed_at`.
- Database yayasan: tabel `admin_audit_events`; kolom akun `session_version`, `managed_at`.
- Migrasi hanya menambahkan struktur yang belum ada. Akun `unit_admin` lama tetap mendapat hak Admin Unit; akun superadmin lama tetap dikenali.

Uji regresi HTTP tersedia di `tests/deployment/central_admin.py`; jalankan hanya menggunakan database sementara `codex_railway_central_main` dan `codex_railway_central_units`, dengan server PHP lokal pada port 8768. Skrip mengubah data uji sehingga tidak boleh diarahkan ke database klien.
