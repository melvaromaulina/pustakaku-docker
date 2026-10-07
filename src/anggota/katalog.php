<?php
require __DIR__ . '/../includes/config.php';
require_role('anggota');
$judul = 'Katalog buku';
$warna = ['#14403b', '#2f6f62', '#c08a2e', '#7a3b2e', '#264f73', '#5b4b8a', '#3d6b3a'];

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';
$stmt = $db->prepare("SELECT * FROM books WHERE judul LIKE ? OR penulis LIKE ? ORDER BY judul");
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$buku = $stmt->get_result();

require __DIR__ . '/../includes/header.php';
?>
<div class="container mt-4">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
      <h1 class="h2 mb-1">Katalog buku</h1>
      <p class="text-secondary mb-0">Halo, <?= e($_SESSION['user']['nama']) ?>. Pilih buku yang ingin dipinjam.</p>
    </div>
    <form class="d-flex gap-2" method="get">
      <input class="form-control" name="q" placeholder="Cari judul atau penulis" value="<?= e($q) ?>">
      <button class="btn btn-forest"><i class="bi bi-search"></i></button>
    </form>
  </div>
  <?php show_flash(); ?>

  <div class="row g-4">
    <?php if ($buku->num_rows === 0): ?>
      <div class="col-12"><div class="panel text-center">Tidak ada buku untuk "<?= e($q) ?>". Coba kata kunci lain.</div></div>
    <?php endif; ?>
    <?php while ($b = $buku->fetch_assoc()): $ada = $b['stok'] > 0; ?>
      <div class="col-6 col-md-4 col-xl-3">
        <div class="book">
          <div class="book-cover" style="background:<?= $warna[$b['id'] % count($warna)] ?>">
            <div class="judul"><?= e($b['judul']) ?></div>
            <div class="penulis"><?= e($b['penulis']) ?></div>
          </div>
          <div class="book-body">
            <div class="small text-secondary"><?= e($b['penerbit']) ?>, <?= e($b['tahun']) ?></div>
            <div>
              <span class="badge-status <?= $ada ? 'st-tersedia' : 'st-habis' ?>"><?= $ada ? 'Tersedia (' . (int)$b['stok'] . ')' : 'Habis' ?></span>
            </div>
            <?php if ($ada): ?>
              <a class="btn btn-brass btn-sm mt-auto" href="/anggota/ajukan.php?id=<?= (int)$b['id'] ?>">Ajukan pinjam</a>
            <?php else: ?>
              <button class="btn btn-outline-secondary btn-sm mt-auto" disabled>Belum tersedia</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
