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
$menunggu = hitung($db, "SELECT COUNT(*) FROM loans WHERE status='Diajukan'");

// FITUR PEMBEDA: statistik buku terpopuler dan komposisi status
$label_buku = []; $nilai_buku = [];
$r = $db->query("SELECT b.judul, COUNT(l.id) AS jml FROM loans l JOIN books b ON b.id = l.book_id
                 WHERE l.status <> 'Ditolak' GROUP BY b.id, b.judul ORDER BY jml DESC, b.judul LIMIT 5");
while ($x = $r->fetch_assoc()) { $label_buku[] = $x['judul']; $nilai_buku[] = (int)$x['jml']; }

$label_status = []; $nilai_status = [];
$r = $db->query("SELECT status, COUNT(*) AS jml FROM loans GROUP BY status");
while ($x = $r->fetch_assoc()) { $label_status[] = $x['status']; $nilai_status[] = (int)$x['jml']; }

$terbaru = $db->query("SELECT l.*, u.nama, b.judul FROM loans l
                       JOIN users u ON u.id = l.user_id
                       JOIN books b ON b.id = l.book_id
                       ORDER BY l.id DESC LIMIT 6");
?>
<h1 class="h2 mb-1">Dashboard</h1>
<p class="text-secondary mb-4">Ringkasan perpustakaan hari ini.</p>

<?php if ($menunggu > 0): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="bi bi-hourglass-split me-2"></i><strong><?= $menunggu ?> pengajuan</strong> menunggu persetujuan.</span>
    <a class="btn btn-sm btn-brass" href="/admin/peminjaman.php?status=Diajukan">Tinjau sekarang</a>
  </div>
<?php endif; ?>

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

<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="panel h-100">
      <h2 class="h5 mb-3">Buku paling sering dipinjam</h2>
      <?php if (!$label_buku): ?>
        <p class="text-secondary mb-0">Belum ada data peminjaman untuk ditampilkan.</p>
      <?php else: ?>
        <div style="position:relative;height:260px"><canvas id="grafikBuku"></canvas></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel h-100">
      <h2 class="h5 mb-3">Komposisi status peminjaman</h2>
      <?php if (!$label_status): ?>
        <p class="text-secondary mb-0">Belum ada data.</p>
      <?php else: ?>
        <div style="position:relative;height:260px"><canvas id="grafikStatus"></canvas></div>
      <?php endif; ?>
    </div>
  </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const labelBuku  = <?= json_encode($label_buku,  JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const nilaiBuku  = <?= json_encode($nilai_buku) ?>;
const labelStat  = <?= json_encode($label_status, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const nilaiStat  = <?= json_encode($nilai_status) ?>;
const warnaStat  = {Diajukan:'#c08a2e', Dipinjam:'#264f73', Dikembalikan:'#2f6f62', Ditolak:'#a12d18'};

if (document.getElementById('grafikBuku')) {
  new Chart(document.getElementById('grafikBuku'), {
    type: 'bar',
    data: { labels: labelBuku, datasets: [{ label: 'Kali dipinjam', data: nilaiBuku, backgroundColor: '#2f6f62', borderRadius: 6 }] },
    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } },
               scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
  });
}
if (document.getElementById('grafikStatus')) {
  new Chart(document.getElementById('grafikStatus'), {
    type: 'doughnut',
    data: { labels: labelStat, datasets: [{ data: nilaiStat, backgroundColor: labelStat.map(s => warnaStat[s] || '#999') }] },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
  });
}
</script>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
