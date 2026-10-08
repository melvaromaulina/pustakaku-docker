PustakaKu

Aplikasi web manajemen perpustakaan yang berjalan di dalam container Docker. Admin mengelola data buku, anggota, dan peminjaman, sedangkan anggota dapat mencari buku, mengajukan peminjaman, dan memantau jatuh tempo serta denda.

Proyek ini dibuat untuk Tugas Kelompok-1 MID: Pengembangan Aplikasi yang Berjalan pada Container.

Fitur

Anggota

Registrasi, login, dan ubah profil
Katalog buku dengan pencarian judul/penulis dan status Tersedia/Habis
Pengajuan peminjaman (maksimal 3 pinjaman aktif)
Riwayat peminjaman: status, jatuh tempo, dan denda
Perpanjangan peminjaman satu kali
Pengingat jatuh tempo (H-2 sampai terlambat) dan rekomendasi buku berdasarkan kategori favorit

Admin

Dashboard: ringkasan jumlah, grafik buku terpopuler, dan komposisi status peminjaman
CRUD buku beserta stok
CRUD anggota
Kelola peminjaman: setujui, tolak, kembalikan, ubah jatuh tempo, hapus

Aturan peminjaman

Jatuh tempo otomatis 7 hari setelah disetujui
Stok berkurang saat disetujui dan bertambah saat dikembalikan
Denda Rp1.000 per hari keterlambatan, dihitung saat buku dikembalikan
Teknologi
Komponen	Keterangan
Web server	PHP 8.2 + Apache (php:8.2-apache)
Database	MySQL 8.0
Manajemen database	phpMyAdmin
Tampilan	Bootstrap 5.3, Bootstrap Icons, Chart.js
Container	Docker dan Docker Compose
Arsitektur container

Tiga container berjalan dalam satu network (pustaka-net):

Service	Image	Port	Fungsi
web	dibangun dari Dockerfile	8080	Aplikasi PHP
db	mysql:8.0	internal	Database, dengan healthcheck
phpmyadmin	phpmyadmin:latest	8081	Antarmuka database

Data MySQL disimpan pada volume db_data sehingga tetap ada saat container dimatikan. Konfigurasi dibaca dari file .env. Struktur tabel dan data contoh dimuat otomatis dari db/init.sql saat database pertama kali dibuat.

Menjalankan aplikasi

Prasyarat: Docker Desktop (atau Docker Engine dengan plugin Compose) dan Git.

bash
git clone https://github.com/melvaromaulina/pustakaku-docker.git
cd pustakaku-docker
cp .env.example .env        # Windows: copy .env.example .env
docker compose up -d --build

Setelah semua container berjalan (docker compose ps):

Aplikasi: http://localhost:8080
phpMyAdmin: http://localhost:8081
Akun contoh
Peran	Email	Password
Admin	admin@pustaka.id	admin123
Anggota	budi@pustaka.id	anggota123

Akun phpMyAdmin: user root, password sesuai MYSQL_ROOT_PASSWORD pada .env.

Password pada .env.example hanya untuk percobaan lokal. Ganti jika aplikasi dipakai di lingkungan sebenarnya.

Perintah yang sering dipakai
bash
docker compose ps             # status container
docker compose logs web       # log aplikasi
docker compose down           # matikan container (data tetap tersimpan)
docker compose down -v        # matikan dan hapus data database

Setelah mengubah db/init.sql, jalankan docker compose down -v lalu docker compose up -d --build agar skema dimuat ulang.

Menjalankan dari image (tanpa mount)

Pada docker-compose.yml, folder src/ di-mount ke container supaya perubahan kode langsung terlihat saat pengembangan. Untuk menguji bahwa aplikasi sudah terpasang di dalam image:

bash
docker build -t pustakaku-web .
docker run -d -p 8082:80 --name uji-image \
  --network pustakaku-docker_pustaka-net --env-file .env pustakaku-web

Aplikasi dapat dibuka di http://localhost:8082. Container database harus sudah berjalan (docker compose up -d).

Struktur proyek
pustakaku-docker/
├── Dockerfile
├── docker-compose.yml
├── .env.example
├── db/
│   └── init.sql            # skema dan data contoh
└── src/
    ├── index.php           # beranda
    ├── login.php, register.php, logout.php
    ├── includes/           # koneksi database, header, footer, fungsi bantu
    ├── assets/css/app.css
    ├── admin/              # dashboard, buku, anggota, peminjaman
    └── anggota/            # katalog, ajukan, riwayat, profil
Database
Tabel	Isi
users	Akun admin dan anggota (password disimpan sebagai hash)
books	Data buku dan stok
loans	Transaksi peminjaman; berelasi ke users dan books

Status peminjaman: Diajukan, Dipinjam, Dikembalikan, Ditolak. Status Terlambat ditentukan dari tanggal jatuh tempo.

Keamanan
Password di-hash dengan password_hash dan diperiksa dengan password_verify
Query memakai prepared statement
Output di-escape dengan htmlspecialchars
Halaman admin dibatasi berdasarkan role pada session
Tim
Nama	NIM	Kontribusi
[Melva ROmaulina Br Sitorus]	[231110028]	Docker, database, GitHub
[Hotnida Junita Pasaribu]	[221111292]	Halaman admin
[Karin Kenisa Br Sitepu]	[231111550]	Halaman anggota, laporan, video
Catatan
