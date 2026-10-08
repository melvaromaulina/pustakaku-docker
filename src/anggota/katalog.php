<?php
require_once __DIR__ . '/../includes/config.php';
require_role('anggota');
$judul = 'Katalog buku';
$uid = (int)$_SESSION['user']['id'];
$warna = ['#14403b', '#2f6f62', '#c08a2e', '#7a3b2e', '#264f73', '#5b4b8a', '#3d6b3a'];

function kartu_buku($b, $warna) {
    $ada = $b['stok'] > 0; ?>
    <div class="col-6 col-md-4 col-xl-3">
      <div class="book">
        <div class="book-cover" style="background:<?= $warna[$b['id'] % count($warna)] ?>">
          <div class="judul"><?= e($b['judul']) ?></div>
          <div class="penulis"><?= e($b['penulis']) ?></div>
        </div>
        <div class="book-body">
          <div class="small text-secondary"><?= e($b['penerbit']) ?>, <?= e($b['tahun']) ?></div>
          <div><span class="badge-status <?= $ada ? 'st-tersedia' : 'st-habis' ?>"><?= $ada ? 'Tersedia (' . (int)$b['stok'] . ')' : 'Habis' ?></span></div>
          <?php if ($ada): ?>
            <a class="btn btn-brass btn-sm mt-auto" href="/anggota/ajukan.php?id=<?= (int)$b['id'] ?>">Ajukan pinjam</a>
          <?php else: ?>
            <button class="btn btn-outline-secondary btn-sm mt-auto" disabled>Belum tersedia</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php }

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';
$stmt = $db->prepare("SELECT * FROM books WHERE judul LIKE ? OR penulis LIKE ? ORDER BY judul");
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$buku = $stmt->get_result();

// FITUR PEMBEDA 1: pengingat jatuh tempo (H-2 sampai terlambat)
$s = $db->prepare("SELECT b.judul, DATEDIFF(l.tgl_jatuh_tempo, CURDATE()) AS sisa
                   FROM loans l JOIN books b ON b.id = l.book_id
                   WHERE l.user_id = ? AND l.status = 'Dipinjam'
                     AND l.tgl_jatuh_tempo <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
                   ORDER BY l.tgl_jatuh_tempo");
$s->bind_param('i', $uid);
$s->execute();
$pengingat = $s->get_result()->fetch_all(MYSQLI_ASSOC);

// FITUR PEMBEDA 2: rekomendasi berdasarkan kategori yang paling sering dipinjam
$rekomendasi = []; $alasan = '';
if ($q === '') {
    $s = $db->prepare("SELECT b.kategori FROM loans l JOIN books b ON b.id = l.book_id
                       WHERE l.user_id = ? AND l.status <> 'Ditolak' AND b.kategori <> ''
                       GROUP BY b.kategori ORDER BY COUNT(*) DESC LIMIT 1");
    $s->bind_param('i', $uid);
    $s->execute();
    $fav = $s->get_result()->fetch_row()[0] ?? null;

    if ($fav) {
        $s = $db->prepare("SELECT * FROM books WHERE kategori = ? AND stok > 0
                           AND id NOT IN (SELECT book_id FROM loans WHERE user_id = ?)
                           ORDER BY RAND() LIMIT 4");
        $s->bind_param('si', $fav, $uid);
        $s->execute();
        $rekomendasi = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        $alasan = 'Kamu sering meminjam kategori ' . $fav;
    }
    if (!$rekomendasi) {   // belum ada riwayat: tampilkan yang paling populer
        $s = $db->prepare("SELECT b.* FROM books b LEFT JOIN loans l ON l.book_id = b.id AND l.status <> 'Ditolak'
                           WHERE b.stok > 0 AND b.id NOT IN (SELECT book_id FROM loans WHERE user_id = ?)
                           GROUP BY b.id ORDER BY COUNT(l.id) DESC, b.judul LIMIT 4");
        $s->bind_param('i', $uid);
        $s->execute();
        $rekomendasi = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        $alasan = 'Buku populer di perpustakaan';
    }
}

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

  <?php foreach ($pengingat as $p):
      $sisa = (int)$p['sisa'];
      if ($sisa < 0)      { $tipe = 'danger';  $teks = 'terlambat ' . abs($sisa) . ' hari. Denda berjalan sekitar ' . rupiah(abs($sisa) * DENDA_PER_HARI) . '.'; }
      elseif ($sisa === 0){ $tipe = 'danger';  $teks = 'jatuh tempo hari ini.'; }
      else                { $tipe = 'warning'; $teks = 'jatuh tempo ' . $sisa . ' hari lagi (H-' . $sisa . ').'; } ?>
    <div class="alert alert-<?= $tipe ?> d-flex justify-content-between align-items-center">
      <span><i class="bi bi-bell me-2"></i><strong><?= e($p['judul']) ?></strong> <?= e($teks) ?></span>
      <a class="btn btn-sm btn-outline-dark" href="/anggota/riwayat.php">Lihat</a>
    </div>
  <?php endforeach; ?>

  <?php if ($rekomendasi): ?>
    <div class="mb-2 d-flex align-items-baseline gap-2">
      <h2 class="h4 mb-0">Untuk kamu</h2><span class="text-secondary small"><?= e($alasan) ?></span>
    </div>
    <div class="row g-4 mb-5">
      <?php foreach ($rekomendasi as $b) kartu_buku($b, $warna); ?>
    </div>
    <h2 class="h4 mb-3">Semua buku</h2>
  <?php endif; ?>

  <div class="row g-4">
    <?php if ($buku->num_rows === 0): ?>
      <div class="col-12"><div class="panel text-center">Tidak ada buku untuk "<?= e($q) ?>". Coba kata kunci lain.</div></div>
    <?php endif; ?>
    <?php while ($b = $buku->fetch_assoc()) kartu_buku($b, $warna); ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
