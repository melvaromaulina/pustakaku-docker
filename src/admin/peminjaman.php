<?php
require_once __DIR__ . '/../includes/config.php';
require_role('admin');

/* ---------- PROSES AKSI (setujui, tolak, kembalikan, ubah, hapus) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);
    try {
        $db->begin_transaction();
        $s = $db->prepare("SELECT book_id, status FROM loans WHERE id = ? FOR UPDATE");
        $s->bind_param('i', $id);
        $s->execute();
        $loan = $s->get_result()->fetch_assoc();
        if (!$loan) throw new RuntimeException('Data peminjaman tidak ditemukan.');

        if ($aksi === 'setujui') {
            if ($loan['status'] !== 'Diajukan') throw new RuntimeException('Hanya pengajuan yang bisa disetujui.');
            $u = $db->prepare("UPDATE books SET stok = stok - 1 WHERE id = ? AND stok > 0");
            $u->bind_param('i', $loan['book_id']);
            $u->execute();
            if ($u->affected_rows === 0) throw new RuntimeException('Stok buku habis, pengajuan tidak bisa disetujui.');
            $lama = LAMA_PINJAM;
            $u = $db->prepare("UPDATE loans SET status='Dipinjam', tgl_pinjam=CURDATE(),
                               tgl_jatuh_tempo=DATE_ADD(CURDATE(), INTERVAL ? DAY) WHERE id = ?");
            $u->bind_param('ii', $lama, $id);
            $u->execute();
            set_flash('Pengajuan disetujui. Jatuh tempo ' . LAMA_PINJAM . ' hari dari hari ini.');

        } elseif ($aksi === 'tolak') {
            if ($loan['status'] !== 'Diajukan') throw new RuntimeException('Hanya pengajuan yang bisa ditolak.');
            $db->query("UPDATE loans SET status='Ditolak' WHERE id = $id");
            set_flash('Pengajuan ditolak.', 'warning');

        } elseif ($aksi === 'kembalikan') {
            if ($loan['status'] !== 'Dipinjam') throw new RuntimeException('Buku ini tidak sedang dipinjam.');
            $per_hari = DENDA_PER_HARI;
            $u = $db->prepare("UPDATE loans SET status='Dikembalikan', tgl_kembali=CURDATE(),
                               denda = GREATEST(DATEDIFF(CURDATE(), tgl_jatuh_tempo), 0) * ? WHERE id = ?");
            $u->bind_param('ii', $per_hari, $id);
            $u->execute();
            $db->query("UPDATE books SET stok = stok + 1 WHERE id = " . (int)$loan['book_id']);
            $d = $db->query("SELECT denda FROM loans WHERE id = $id")->fetch_row()[0];
            set_flash('Buku dikembalikan. Denda: ' . rupiah($d), $d > 0 ? 'warning' : 'success');

        } elseif ($aksi === 'ubah') {
            $tempo = $_POST['tgl_jatuh_tempo'] ?? '';
            if ($loan['status'] !== 'Dipinjam' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tempo))
                throw new RuntimeException('Jatuh tempo hanya bisa diubah untuk buku yang sedang dipinjam.');
            $u = $db->prepare("UPDATE loans SET tgl_jatuh_tempo = ? WHERE id = ?");
            $u->bind_param('si', $tempo, $id);
            $u->execute();
            set_flash('Tanggal jatuh tempo diubah.');

        } elseif ($aksi === 'hapus') {
            if ($loan['status'] === 'Dipinjam')   // buku sedang dipinjam: kembalikan stoknya
                $db->query("UPDATE books SET stok = stok + 1 WHERE id = " . (int)$loan['book_id']);
            $db->query("DELETE FROM loans WHERE id = $id");
            set_flash('Data peminjaman dihapus.', 'secondary');
        }
        $db->commit();
    } catch (Throwable $t) {
        $db->rollback();
        set_flash($t->getMessage(), 'danger');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

/* ---------- AMBIL DATA ---------- */
$filter = $_GET['status'] ?? '';
$dasar = "SELECT l.*, u.nama, b.judul FROM loans l
          JOIN users u ON u.id = l.user_id JOIN books b ON b.id = l.book_id";
if (in_array($filter, ['Diajukan', 'Dipinjam', 'Dikembalikan', 'Ditolak'], true)) {
    $st = $db->prepare($dasar . " WHERE l.status = ? ORDER BY l.id DESC");
    $st->bind_param('s', $filter);
    $st->execute();
    $data = $st->get_result();
} elseif ($filter === 'Terlambat') {
    $data = $db->query($dasar . " WHERE l.status='Dipinjam' AND l.tgl_jatuh_tempo < CURDATE() ORDER BY l.id DESC");
} else {
    $data = $db->query($dasar . " ORDER BY l.id DESC");
}

function tgl($t) { return $t ? date('d M Y', strtotime($t)) : '-'; }

$judul = 'Peminjaman';
$halaman = 'peminjaman';
require __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="h2 mb-1">Peminjaman</h1>
<p class="text-secondary mb-3">Setujui pengajuan, proses pengembalian, dan pantau denda.</p>
<?php show_flash(); ?>

<div class="d-flex flex-wrap gap-2 mb-3">
  <?php foreach (['' => 'Semua', 'Diajukan' => 'Diajukan', 'Dipinjam' => 'Dipinjam', 'Terlambat' => 'Terlambat', 'Dikembalikan' => 'Dikembalikan', 'Ditolak' => 'Ditolak'] as $k => $label): ?>
    <a href="?status=<?= $k ?>" class="btn btn-sm <?= $filter === $k ? 'btn-forest' : 'btn-outline-secondary' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="panel">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Anggota</th><th>Buku</th><th>Dipinjam</th><th>Jatuh tempo</th><th>Status</th><th>Denda</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php if ($data->num_rows === 0): ?>
        <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada data untuk filter ini.</td></tr>
      <?php endif; ?>
      <?php while ($l = $data->fetch_assoc()):
          $hari_telat = 0;
          if ($l['status'] === 'Dipinjam' && $l['tgl_jatuh_tempo'])
              $hari_telat = max(0, (int)((strtotime(date('Y-m-d')) - strtotime($l['tgl_jatuh_tempo'])) / 86400));
      ?>
        <tr>
          <td><?= e($l['nama']) ?></td>
          <td><?= e($l['judul']) ?></td>
          <td><?= tgl($l['tgl_pinjam']) ?></td>
          <td><?= tgl($l['tgl_jatuh_tempo']) ?></td>
          <td>
            <span class="badge-status st-<?= strtolower($l['status']) ?>"><?= e($l['status']) ?></span>
            <?php if ($hari_telat > 0): ?><span class="badge-status st-terlambat">Terlambat <?= $hari_telat ?> hari</span><?php endif; ?>
          </td>
          <td>
            <?php if ($l['status'] === 'Dikembalikan'): ?><?= rupiah($l['denda']) ?>
            <?php elseif ($hari_telat > 0): ?><span class="text-danger">~<?= rupiah($hari_telat * DENDA_PER_HARI) ?></span>
            <?php else: ?>-<?php endif; ?>
          </td>
          <td class="text-end text-nowrap">
            <?php if ($l['status'] === 'Diajukan'): ?>
              <form method="post" class="d-inline"><input type="hidden" name="id" value="<?= $l['id'] ?>">
                <button name="aksi" value="setujui" class="btn btn-sm btn-forest">Setujui</button>
                <button name="aksi" value="tolak" class="btn btn-sm btn-outline-danger">Tolak</button>
              </form>
            <?php elseif ($l['status'] === 'Dipinjam'): ?>
              <form method="post" class="d-inline"><input type="hidden" name="id" value="<?= $l['id'] ?>">
                <button name="aksi" value="kembalikan" class="btn btn-sm btn-brass">Kembalikan</button>
              </form>
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalUbah"
                      onclick="isiUbah(<?= $l['id'] ?>, '<?= e($l['tgl_jatuh_tempo']) ?>')" title="Ubah jatuh tempo"><i class="bi bi-pencil"></i></button>
            <?php endif; ?>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus data peminjaman ini?')">
              <input type="hidden" name="id" value="<?= $l['id'] ?>">
              <button name="aksi" value="hapus" class="btn btn-sm btn-outline-secondary" title="Hapus"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalUbah" tabindex="-1">
  <div class="modal-dialog"><form method="post" class="modal-content">
    <div class="modal-header"><h2 class="modal-title h5">Ubah jatuh tempo</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id" id="ubah_id">
      <label class="form-label" for="ubah_tempo">Tanggal jatuh tempo baru</label>
      <input type="date" class="form-control" name="tgl_jatuh_tempo" id="ubah_tempo" required>
    </div>
    <div class="modal-footer"><button class="btn btn-forest">Simpan perubahan</button></div>
  </form></div>
</div>
<script>
function isiUbah(id, tempo) {
  document.getElementById('ubah_id').value = id;
  document.getElementById('ubah_tempo').value = tempo;
}
</script>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
