# Media publik dari CMS

Folder ini menyimpan upload publik seperti gambar berita, galeri, hero, dan
brosur. File di folder ini sengaja dapat dilacak Git supaya media yang dipakai
website ikut tersedia saat repository di-clone atau di-pull oleh anggota tim.

Setelah menambah media melalui portal, cek `git status`, lalu commit file media
bersama perubahan database/seed yang merujuk ke file tersebut.

Folder `careers/` dikecualikan dari Git karena berisi CV dan data pribadi
pelamar.
