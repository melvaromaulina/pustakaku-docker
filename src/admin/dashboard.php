<?php
$judul = 'Dashboard';
$halaman = 'dashboard';
require __DIR__ . '/../includes/admin_header.php';

function hitung($db, $sql) { return (int)$db->query($sql)->fetch_row()[0]; }
$kartu = [
  ['Total buku',      hitung($db, "SELECT COUNT(*) FROM books"), 'bi-journal-bookmark', ''],
  ['Total anggota',   hitung($db, "SELECT COUNT(*) FROM users WHERE role='anggota'"), 'bi-people', ''],
  ['Sedang dipinjam', hitung($db, "SELECT COUNT(*) FROM loans WHERE status='Dipinjam'"), 'bi-bookmark-check', ''],
  ['Terlambat',       hitung($db, "SELECT COUNT(*) FROM loans WHERE status='Dipinjam' AND tgl_jatuh_tempo < CURDATE()"), 'bi-exclamation-triangle', 'bahaya'],
];
$terbaru = $db->query("SELECT l.*, u.nama, b.judul FROM loans l
                       JOIN users u ON u.id = l.user_id
                       JOIN books b ON b.id = l.book_id
                       ORDER BY l.id DESC LIMIT 6");
?>
<h1 class="h2 mb-1">Dashboard</h1>
<p class="text-secondary mb-4">Ringkasan perpustakaan hari ini.</p>

<div class="row g-3 mb-4">
  <?php foreach ($kartu as [$label, $angka, $ikon, $kelas]): ?>
    <div class="col-sm-6 col-xl-3">
      <div class="panel stat <?= $kelas ?>">
        <div class="ikon"><i class="bi <?= $ikon ?>"></i></div>
        <div><div class="angka"><?= $angka ?></div><div class="text-secondary small"><?= $label ?></div></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="panel">
  <h2 class="h5 mb-3">Peminjaman terbaru</h2>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Anggota</th><th>Buku</th><th>Jatuh tempo</th><th>Status</th></tr></thead>
      <tbody>
      <?php while ($l = $terbaru->fetch_assoc()):
          $telat = $l['status'] === 'Dipinjam' && $l['tgl_jatuh_tempo'] < date('Y-m-d'); ?>
        <tr>
          <td><?= e($l['nama']) ?></td>
          <td><?= e($l['judul']) ?></td>
          <td><?= $l['tgl_jatuh_tempo'] ? e(date('d M Y', strtotime($l['tgl_jatuh_tempo']))) : '-' ?></td>
          <td>
            <span class="badge-status st-<?= strtolower($l['status']) ?>"><?= e($l['status']) ?></span>
            <?php if ($telat): ?><span class="badge-status st-terlambat">Terlambat</span><?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
