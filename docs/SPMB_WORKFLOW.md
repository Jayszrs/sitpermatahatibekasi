# Alur SPMB dan uji pembayaran

## Pendaftaran

- Orang tua mendaftar dari formulir yayasan atau formulir Daycare, TKIT, SDIT, dan SMPIT. Semua masuk ke `spmb_registrations` pada database utama.
- Kasir atau superadmin yayasan dapat membuat pendaftar yang datang langsung melalui **Portal Yayasan → SPMB & Pembayaran → Input Pendaftar Manual**. Pilih unit dan kelas tujuan.
- `id` database adalah primary key otomatis. Kode yang ditampilkan dibentuk dari tahun ajaran, unit, dan ID, misalnya `SPMB-2026-SDIT-000123`. Petugas tidak mengisi atau mengubah kode itu.
- NISN disimpan sebagai data opsional 10 digit jika calon siswa sudah memilikinya. NIS internal dibuat otomatis dari unit, tahun, dan ID ketika status menjadi **Daftar Ulang**. NISN tidak dijadikan sumber nomor baru.

## Bukti dan verifikasi

1. Setelah pendaftaran, orang tua menyimpan nomor pendaftaran dan tautan kirim bukti. Untuk pendaftar manual, kasir dapat menyalin tautan dari detail pendaftar. Petugas unit juga dapat membuka **Tautan Bukti** pada rekap.
2. Orang tua mengisi nominal yang ditransfer, tanggal, metode, dan mengunggah screenshot JPG/PNG/WebP. Bukti disimpan di folder privat dan klaim berstatus **Menunggu pemeriksaan**. Total terbayar tetap sama.
3. Kasir yayasan atau petugas SPMB unit membuka antrean. Cocokkan nominal, tanggal, nama, dan referensi dengan mutasi rekening yang benar-benar diterima. Isi nominal di mutasi serta referensinya, lalu **Setujui & Akumulasikan** atau **Tolak** dengan alasan.
4. Setelah disetujui, sistem menjumlahkan klaim terverifikasi dan transaksi lama yang masih berlaku. Status menjadi **Belum Bayar** jika total nol, **Sebagian** jika masih di bawah tagihan, atau **Lunas** jika tagihan sudah ditetapkan dan total mencukupi.
5. Jika petugas salah menyetujui transaksi, gunakan **Batalkan verifikasi** dengan alasan. Sistem langsung mengurangi akumulasi dan memperbarui status bayar. Riwayat pembatalan tetap terlihat.

Sistem tidak memiliki koneksi API ke bank. Screenshot dan nominal yang diisi pengirim bukan bukti dana masuk; pemeriksaan mutasi dilakukan oleh petugas. Sistem menolak persetujuan jika nominal mutasi berbeda atau referensi transaksi sudah dipakai pada pembayaran terverifikasi lain.

## Contoh uji Rp 500.000

1. Buat pendaftar baru dari salah satu unit atau input manual kasir. Simpan tautan pembayaran.
2. Pada detail kasir, tetapkan **Total tagihan** menjadi Rp 500.000 jika pembayaran itu seharusnya melunasi tagihan.
3. Buka tautan sebagai orang tua. Isi Rp 500.000 dan unggah screenshot. Pastikan ringkasan masih menunjukkan Rp 0 dan antrean bertambah satu.
4. Masuk sebagai kasir yayasan atau petugas SPMB unit. Buka bukti dari antrean, lalu cocokkan dengan mutasi rekening. Isi Rp 500.000 dan referensi mutasi yang unik, kemudian setujui.
5. Muat ulang halaman orang tua dan rekap unit. Total terbayar harus Rp 500.000; status **Lunas** bila tagihan Rp 500.000. Jika bukti ditolak, total tetap Rp 0.

Jalankan pengujian logika lokal dengan `php tests/spmb_workflow.php`. Pengujian ini memakai database SQLite sementara dan tidak mengubah data produksi.
