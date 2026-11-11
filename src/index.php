<?php
$judul = 'Beranda';
require __DIR__ . '/includes/header.php';
$warna = ['#14403b', '#2f6f62', '#c08a2e', '#7a3b2e', '#264f73', '#5b4b8a', '#3d6b3a'];
$tinggi = [170, 210, 190, 230, 160, 200, 180, 220, 175, 205];
$terbaru = $db->query("SELECT * FROM books ORDER BY id DESC LIMIT 4");
?>
<section class="hero">
  <div class="container">
    <div class="row align-items-end g-5">
      <div class="col-lg-6">
        <h1>Pinjam buku tanpa antre.</h1>
        <p class="mt-3">Cari buku, ajukan peminjaman dari rumah, dan pantau jatuh tempo serta denda dalam satu tempat.</p>
        <div class="d-flex gap-2 mt-4">
          <a href="/register.php" class="btn btn-brass btn-lg px-4">Daftar anggota</a>
          <a href="/login.php" class="btn btn-outline-light btn-lg px-4">Masuk</a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="shelf" aria-hidden="true">
          <?php foreach ($tinggi as $i => $t): ?>
            <div class="spine" style="height:<?= $t ?>px;background:<?= $warna[$i % count($warna)] ?>"></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container mt-5">
  <h2 class="mb-4">Baru masuk rak</h2>
  <div class="row g-4">
    <?php while ($b = $terbaru->fetch_assoc()): ?>
      <div class="col-6 col-lg-3">
        <div class="book">
          <div class="book-cover" style="background:<?= $warna[$b['id'] % count($warna)] ?>">
            <div class="judul"><?= e($b['judul']) ?></div>
            <div class="penulis"><?= e($b['penulis']) ?></div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

  <div class="row g-4 mt-4">
    <div class="col-md-4"><div class="panel h-100"><h3 class="h5">Katalog lengkap</h3><p class="mb-0">Cari berdasarkan judul atau penulis dan lihat langsung apakah bukunya tersedia.</p></div></div>
    <div class="col-md-4"><div class="panel h-100"><h3 class="h5">Ajukan dari mana saja</h3><p class="mb-0">Pilih buku, ajukan, lalu ambil setelah disetujui petugas. Batas pinjam <?= LAMA_PINJAM ?> hari.</p></div></div>
    <div class="col-md-4"><div class="panel h-100"><h3 class="h5">Denda terhitung otomatis</h3><p class="mb-0">Terlambat mengembalikan? Denda <?= rupiah(DENDA_PER_HARI) ?> per hari, selalu terlihat di riwayat.</p></div></div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
