<?php
require_once __DIR__ . '/../includes/config.php';
require_role('anggota');
$uid = (int)$_SESSION['user']['id'];

// Perpanjang 1 kali, hanya untuk pinjaman milik sendiri yang belum terlambat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'perpanjang') {
    $id = (int)($_POST['id'] ?? 0);
    $lama = LAMA_PINJAM;
    $u = $db->prepare("UPDATE loans SET tgl_jatuh_tempo = DATE_ADD(tgl_jatuh_tempo, INTERVAL ? DAY), sudah_perpanjang = 1
                       WHERE id = ? AND user_id = ? AND status = 'Dipinjam'
                         AND sudah_perpanjang = 0 AND tgl_jatuh_tempo >= CURDATE()");
    $u->bind_param('iii', $lama, $id, $uid);
    $u->execute();
    if ($u->affected_rows > 0) set_flash('Peminjaman diperpanjang ' . LAMA_PINJAM . ' hari.');
    else set_flash('Tidak bisa diperpanjang: sudah pernah diperpanjang atau sudah lewat jatuh tempo.', 'danger');
    header('Location: /anggota/riwayat.php');
    exit;
}

$s = $db->prepare("SELECT l.*, b.judul, b.penulis FROM loans l JOIN books b ON b.id = l.book_id
                   WHERE l.user_id = ? ORDER BY l.id DESC");
$s->bind_param('i', $uid);
$s->execute();
$data = $s->get_result();

function tgl($t) { return $t ? date('d M Y', strtotime($t)) : '-'; }

$judul = 'Peminjaman saya';
require __DIR__ . '/../includes/header.php';
?>
<div class="container mt-4">
  <h1 class="h2 mb-1">Peminjaman saya</h1>
  <p class="text-secondary mb-3">Pantau status, jatuh tempo, dan denda.</p>
  <?php show_flash(); ?>

  <div class="panel">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Buku</th><th>Diajukan</th><th>Jatuh tempo</th><th>Status</th><th>Denda</th><th></th></tr></thead>
        <tbody>
        <?php if ($data->num_rows === 0): ?>
          <tr><td colspan="6" class="text-center text-secondary py-4">
            Belum ada peminjaman. <a href="/anggota/katalog.php">Pilih buku di katalog</a>.</td></tr>
        <?php endif; ?>
        <?php while ($l = $data->fetch_assoc()):
            $telat = 0;
            if ($l['status'] === 'Dipinjam' && $l['tgl_jatuh_tempo'])
                $telat = max(0, (int)((strtotime(date('Y-m-d')) - strtotime($l['tgl_jatuh_tempo'])) / 86400));
            $bisa_perpanjang = $l['status'] === 'Dipinjam' && !$l['sudah_perpanjang'] && $telat === 0;
        ?>
          <tr>
            <td><div class="fw-semibold"><?= e($l['judul']) ?></div><div class="small text-secondary"><?= e($l['penulis']) ?></div></td>
            <td><?= tgl($l['tgl_ajuan']) ?></td>
            <td><?= tgl($l['tgl_jatuh_tempo']) ?></td>
            <td>
              <span class="badge-status st-<?= strtolower($l['status']) ?>"><?= e($l['status']) ?></span>
              <?php if ($telat > 0): ?><span class="badge-status st-terlambat">Terlambat <?= $telat ?> hari</span><?php endif; ?>
            </td>
            <td>
              <?php if ($l['status'] === 'Dikembalikan'): ?><?= rupiah($l['denda']) ?>
              <?php elseif ($telat > 0): ?><span class="text-danger">~<?= rupiah($telat * DENDA_PER_HARI) ?></span>
              <?php else: ?>-<?php endif; ?>
            </td>
            <td class="text-end">
              <?php if ($bisa_perpanjang): ?>
                <form method="post">
                  <input type="hidden" name="aksi" value="perpanjang"><input type="hidden" name="id" value="<?= $l['id'] ?>">
                  <button class="btn btn-sm btn-outline-secondary text-nowrap">Perpanjang <?= LAMA_PINJAM ?> hari</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
