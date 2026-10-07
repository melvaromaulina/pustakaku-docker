# PustakaKu: Panduan untuk Pemula

Aplikasi perpustakaan (PHP + MySQL + phpMyAdmin) yang berjalan di Docker. Folder ini sudah berisi **kerangka yang sudah jadi**. Kalian tidak mulai dari nol: tinggal jalankan, lalu tambah halaman.

---

## BAGIAN 1: Menjalankan (semua orang, 10 menit)

### Langkah 1. Pasang 3 aplikasi
1. **Docker Desktop**: docker.com/products/docker-desktop. Setelah terpasang, **buka dan tunggu sampai ikon paus di bawah kanan berwarna hijau/"Engine running"**. Di Windows, jika diminta, aktifkan WSL2 lalu restart.
2. **Git**: git-scm.com/downloads (klik Next terus sampai selesai).
3. **VS Code**: code.visualstudio.com.

### Langkah 2. Buka proyek di VS Code
1. Ekstrak file zip, sehingga ada folder `pustakaku-docker`.
2. Buka VS Code, pilih **File > Open Folder**, lalu pilih folder `pustakaku-docker`.
3. Buka terminal: menu **Terminal > New Terminal**. Semua perintah di bawah diketik di terminal ini.

### Langkah 3. Salin file pengaturan
Jika ada file `.env`, lewati langkah ini. Jika tidak ada:
```
cp .env.example .env
```
(Di Windows PowerShell: `copy .env.example .env`)

### Langkah 4. Jalankan
```
docker compose up -d --build
```
Pertama kali butuh 3 sampai 10 menit karena mengunduh image. Cek hasilnya:
```
docker compose ps
```
Ada 3 container: `pustakaku_web`, `pustakaku_db`, `pustakaku_pma`, semuanya berstatus **running**.

### Langkah 5. Buka di browser
| Alamat | Isinya |
|---|---|
| http://localhost:8080 | Website |
| http://localhost:8081 | phpMyAdmin (user: `root`, password: `rootpass123`) |

Akun contoh:
- Admin: `admin@pustaka.id` / `admin123`
- Anggota: `budi@pustaka.id` / `anggota123`

### Perintah penting
| Perintah | Fungsi |
|---|---|
| `docker compose up -d --build` | Jalankan semua |
| `docker compose down` | Matikan (data **tetap aman**) |
| `docker compose down -v` | Matikan **dan hapus data**, dipakai jika `db/init.sql` diubah |
| `docker compose logs web` | Lihat pesan error website |

---

## BAGIAN 2: Di mana menaruh kode?

**Semua kode PHP ditaruh di folder `src/`. Hanya di situ.**

```
pustakaku-docker/
├── docker-compose.yml     [A] jangan diubah tanpa bilang A
├── Dockerfile             [A]
├── .env                   [A] rahasia, tidak di-upload ke GitHub
├── db/init.sql            [A] struktur tabel dan data contoh
└── src/                   <-- SEMUA CODE WEBSITE DI SINI
    ├── index.php          sudah jadi (beranda)
    ├── login.php          sudah jadi
    ├── register.php       sudah jadi
    ├── logout.php         sudah jadi
    ├── includes/          [A] header, footer, koneksi database
    ├── assets/css/app.css [A] tampilan
    ├── admin/             [B] halaman admin
    │   └── dashboard.php  sudah jadi (contoh untuk ditiru)
    └── anggota/           [C] halaman anggota
        └── katalog.php    sudah jadi (contoh untuk ditiru)
```

**Perubahan di `src/` langsung terlihat: simpan file (Ctrl+S), lalu refresh browser (F5). Tidak perlu menjalankan Docker lagi.**

### Cara membuat file baru
Di VS Code, klik kanan folder (misalnya `src/admin`), pilih **New File**, ketik nama file (misalnya `buku.php`), lalu tempel kodenya.

### Kerangka setiap halaman (tinggal salin)
**Halaman admin** (`src/admin/namahalaman.php`):
```php
<?php
$judul = 'Kelola buku';
$halaman = 'buku';      // buku / anggota / peminjaman, supaya menu aktif
require __DIR__ . '/../includes/admin_header.php';

// ... ambil data dari database di sini ...
?>

<h1 class="h2">Kelola buku</h1>
<div class="panel">isi halaman di sini</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
```

**Halaman anggota** (`src/anggota/namahalaman.php`):
```php
<?php
require __DIR__ . '/../includes/config.php';
require_role('anggota');
$judul = 'Riwayat';
require __DIR__ . '/../includes/header.php';
?>
<div class="container mt-4">
  <h1 class="h2">Riwayat</h1>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
```

### Cara mengambil data dari database
```php
// Ambil banyak baris
$hasil = $db->query("SELECT * FROM books ORDER BY judul");
while ($b = $hasil->fetch_assoc()) {
    echo e($b['judul']);        // e() wajib agar aman
}

// Simpan data dari form (pakai tanda tanya, jangan tempel variabel langsung)
$stmt = $db->prepare("INSERT INTO books (judul, penulis, stok) VALUES (?, ?, ?)");
$stmt->bind_param('ssi', $judul, $penulis, $stok);   // s = teks, i = angka
$stmt->execute();
```

---

## BAGIAN 3: Pembagian tugas

**Sudah jadi:** Docker (3 container), database 3 tabel, tema tampilan, beranda, register, login, logout, dashboard admin, katalog anggota.

### Orang A: Docker, GitHub, database
- Jalankan Bagian 1, upload ke GitHub (Bagian 4), undang B dan C.
- Rekam video bagian Docker. Siapkan README dan bantu merge.
- Tambahan: `healthcheck`, push image ke Docker Hub (opsional).

### Orang B: Admin (folder `src/admin/`)
Buat 3 file ini (menu di sidebar sudah menuju ke sana):
1. `buku.php` (daftar, tambah, ubah, hapus buku): mulai dari daftar saja, lalu tambah form.
2. `anggota.php`: CRUD anggota.
3. `peminjaman.php`: daftar peminjaman + tombol **Setujui** (stok -1, jatuh tempo +7 hari), **Kembalikan** (stok +1, hitung denda), **Tolak**, **Hapus**.

Rumus denda: `max(0, selisih_hari_terlambat) * DENDA_PER_HARI`. Contoh:
```php
$telat = (strtotime(date('Y-m-d')) - strtotime($l['tgl_jatuh_tempo'])) / 86400;
$denda = max(0, (int)$telat) * DENDA_PER_HARI;
```

### Orang C: Anggota (folder `src/anggota/`) + laporan + video
1. `ajukan.php?id=...`: simpan ke tabel `loans` dengan status `Diajukan`, lalu kembali ke katalog dengan `set_flash('Pengajuan terkirim')`.
2. `riwayat.php`: tabel peminjaman milik sendiri (`WHERE user_id = ?` dengan `$_SESSION['user']['id']`).
3. `profil.php`: ubah nama, email, no HP, alamat.
4. Laporan PDF dan video.

Fitur pembeda (pilih 1 sampai 2): perpanjang sekali, batas 3 pinjaman aktif, grafik buku terpopuler.

---

## BAGIAN 4: GitHub tanpa pusing

### Orang A: upload pertama kali
1. Di github.com buat repo baru bernama `pustakaku-docker` (**tanpa** centang README).
2. Di terminal VS Code:
```
git init
git add .
git commit -m "chore: proyek awal"
git branch -M main
git remote add origin https://github.com/USERNAME/pustakaku-docker.git
git push -u origin main
```
(Ganti `USERNAME` dengan username GitHub A. File `.env` otomatis tidak ikut karena ada di `.gitignore`.)
3. Undang teman: repo > **Settings > Collaborators > Add people**, masukkan username B dan C. Mereka harus klik **Accept** di email.

### Orang B dan C: ambil proyek
```
git clone https://github.com/USERNAME/pustakaku-docker.git
cd pustakaku-docker
cp .env.example .env
docker compose up -d --build
git checkout -b fitur-admin        # C pakai: git checkout -b fitur-anggota
```

### Setiap selesai satu halaman
```
git add .
git commit -m "feat: halaman kelola buku"
git push origin fitur-admin
```
Lalu buka GitHub > klik tombol hijau **Compare & pull request** > **Create pull request**, minta teman memeriksa, lalu **Merge**.

### Sebelum mulai kerja setiap hari
```
git checkout main
git pull
git checkout fitur-admin
git merge main
```

**Aturan emas:** hanya ubah file di folder tugasmu. Ingin mengubah `includes/` atau `app.css`? Bilang ke A dulu.

---

## BAGIAN 5: Kalau ada masalah

| Masalah | Solusi |
|---|---|
| `docker: command not found` / error daemon | Buka Docker Desktop dulu, tunggu hijau |
| Halaman error "Connection refused" ke database | Tunggu 30 detik (MySQL masih menyala), refresh |
| Perubahan `init.sql` tidak terbaca | `docker compose down -v` lalu `docker compose up -d --build` |
| Port 8080 sudah dipakai | Di `docker-compose.yml` ubah `"8080:80"` jadi `"8082:80"` |
| Halaman putih kosong | `docker compose logs web` untuk melihat pesan error |
| Merge conflict | Buka file yang bertanda merah di VS Code, pilih **Accept Both Changes**, lalu commit lagi |

---

## BAGIAN 6: Isi video dokumentasi (10 sampai 15 menit)
1. Perkenalan tim dan aplikasi.
2. Tunjukkan GitHub (collaborator, branch, pull request).
3. Jelaskan `Dockerfile` dan `docker-compose.yml` baris per baris.
4. Jalankan `docker compose up -d --build`, lalu `docker compose ps`.
5. Demo anggota: daftar, cari buku, ajukan, lihat riwayat.
6. Demo admin: setujui, kembalikan, denda, dashboard.
7. **Bukti volume:** `docker compose down`, jalankan lagi, data masih ada.
8. Tunjukkan isi database di phpMyAdmin.

## BAGIAN 7: Isi laporan PDF
Nama file: `Tugas01_[NIM_perwakilan].pdf`. Isi: (1) nama aplikasi dan anggota, (2) deskripsi dan cara pakai dengan screenshot, (3) detail aplikasi dan sumber referensi, (4) file pendukung (Bootstrap, Bootstrap Icons, Google Fonts, image php:8.2-apache, mysql:8.0, phpmyadmin), (5) link GitHub dan link video.
