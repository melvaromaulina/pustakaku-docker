<?php
require_once __DIR__ . '/../includes/config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $id       = (int)($_POST['id'] ?? 0);
        $judul    = trim($_POST['judul'] ?? '');
        $penulis  = trim($_POST['penulis'] ?? '');
        $penerbit = trim($_POST['penerbit'] ?? '');
        $tahun    = (int)($_POST['tahun'] ?? 0);
        $kategori = trim($_POST['kategori'] ?? '');
        $stok     = max(0, (int)($_POST['stok'] ?? 0));
        if ($judul === '' || $penulis === '') {
            set_flash('Judul dan penulis wajib diisi.', 'danger');
        } elseif ($id > 0) {
            $s = $db->prepare("UPDATE books SET judul=?, penulis=?, penerbit=?, tahun=?, kategori=?, stok=? WHERE id=?");
            $s->bind_param('sssisii', $judul, $penulis, $penerbit, $tahun, $kategori, $stok, $id);
            $s->execute();
            set_flash('Data buku diperbarui.');
        } else {
            $s = $db->prepare("INSERT INTO books (judul,penulis,penerbit,tahun,kategori,stok) VALUES (?,?,?,?,?,?)");
            $s->bind_param('sssisi', $judul, $penulis, $penerbit, $tahun, $kategori, $stok);
            $s->execute();
            set_flash('Buku baru ditambahkan.');
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $c = $db->prepare("SELECT COUNT(*) FROM loans WHERE book_id=? AND status IN ('Diajukan','Dipinjam')");
        $c->bind_param('i', $id);
        $c->execute();
        if ($c->get_result()->fetch_row()[0] > 0) {
            set_flash('Buku tidak bisa dihapus: masih ada peminjaman aktif.', 'danger');
        } else {
            $s = $db->prepare("DELETE FROM books WHERE id=?");
            $s->bind_param('i', $id);
            $s->execute();
            set_flash('Buku dihapus.', 'secondary');
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';
$st = $db->prepare("SELECT * FROM books WHERE judul LIKE ? OR penulis LIKE ? ORDER BY judul");
$st->bind_param('ss', $like, $like);
$st->execute();
$buku = $st->get_result();

$judul = 'Kelola buku';
$halaman = 'buku';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
  <div><h1 class="h2 mb-1">Kelola buku</h1><p class="text-secondary mb-0">Tambah, ubah, dan atur stok buku.</p></div>
  <button class="btn btn-brass" data-bs-toggle="modal" data-bs-target="#modalBuku" onclick="isiForm(null)"><i class="bi bi-plus-lg me-1"></i>Tambah buku</button>
</div>
<?php show_flash(); ?>

<form class="d-flex gap-2 mb-3" method="get" style="max-width:420px">
  <input class="form-control" name="q" placeholder="Cari judul atau penulis" value="<?= e($q) ?>">
  <button class="btn btn-forest"><i class="bi bi-search"></i></button>
</form>

<div class="panel">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Judul</th><th>Penulis</th><th>Penerbit</th><th>Tahun</th><th>Kategori</th><th>Stok</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php if ($buku->num_rows === 0): ?>
        <tr><td colspan="7" class="text-center text-secondary py-4">Buku tidak ditemukan. Klik "Tambah buku" untuk menambah.</td></tr>
      <?php endif; ?>
      <?php while ($b = $buku->fetch_assoc()): ?>
        <tr>
          <td class="fw-semibold"><?= e($b['judul']) ?></td>
          <td><?= e($b['penulis']) ?></td>
          <td><?= e($b['penerbit']) ?></td>
          <td><?= e($b['tahun']) ?></td>
          <td><?= e($b['kategori']) ?></td>
          <td><span class="badge-status <?= $b['stok'] > 0 ? 'st-tersedia' : 'st-habis' ?>"><?= (int)$b['stok'] ?></span></td>
          <td class="text-end text-nowrap">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalBuku"
                    data-row="<?= e(json_encode($b)) ?>" onclick="isiForm(this)" title="Ubah"><i class="bi bi-pencil"></i></button>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus buku ini?')">
              <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $b['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary" title="Hapus"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalBuku" tabindex="-1">
  <div class="modal-dialog"><form method="post" class="modal-content">
    <div class="modal-header"><h2 class="modal-title h5" id="judulModal">Tambah buku</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" id="f_id">
      <label class="form-label" for="f_judul">Judul</label><input class="form-control mb-3" name="judul" id="f_judul" required>
      <label class="form-label" for="f_penulis">Penulis</label><input class="form-control mb-3" name="penulis" id="f_penulis" required>
      <label class="form-label" for="f_penerbit">Penerbit</label><input class="form-control mb-3" name="penerbit" id="f_penerbit">
      <div class="row g-3">
        <div class="col-4"><label class="form-label" for="f_tahun">Tahun</label><input type="number" class="form-control" name="tahun" id="f_tahun"></div>
        <div class="col-5"><label class="form-label" for="f_kategori">Kategori</label><input class="form-control" name="kategori" id="f_kategori"></div>
        <div class="col-3"><label class="form-label" for="f_stok">Stok</label><input type="number" min="0" class="form-control" name="stok" id="f_stok" value="1" required></div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-forest">Simpan buku</button></div>
  </form></div>
</div>
<script>
function isiForm(btn) {
  const d = btn ? JSON.parse(btn.dataset.row) : {};
  document.getElementById('judulModal').textContent = btn ? 'Ubah buku' : 'Tambah buku';
  document.getElementById('f_id').value       = d.id ?? '';
  document.getElementById('f_judul').value    = d.judul ?? '';
  document.getElementById('f_penulis').value  = d.penulis ?? '';
  document.getElementById('f_penerbit').value = d.penerbit ?? '';
  document.getElementById('f_tahun').value    = d.tahun ?? '';
  document.getElementById('f_kategori').value = d.kategori ?? '';
  document.getElementById('f_stok').value     = d.stok ?? 1;
}
</script>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
