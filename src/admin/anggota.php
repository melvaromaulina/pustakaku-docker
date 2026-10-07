<?php
require_once __DIR__ . '/../includes/config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    try {
        if ($aksi === 'simpan') {
            $id     = (int)($_POST['id'] ?? 0);
            $nama   = trim($_POST['nama'] ?? '');
            $email  = trim($_POST['email'] ?? '');
            $no_hp  = trim($_POST['no_hp'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');
            $pass   = $_POST['password'] ?? '';
            if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                set_flash('Isi nama dan email yang valid.', 'danger');
            } elseif ($id === 0 && strlen($pass) < 6) {
                set_flash('Password anggota baru minimal 6 karakter.', 'danger');
            } elseif ($id > 0) {
                if ($pass !== '') {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $s = $db->prepare("UPDATE users SET nama=?, email=?, no_hp=?, alamat=?, password=? WHERE id=? AND role='anggota'");
                    $s->bind_param('sssssi', $nama, $email, $no_hp, $alamat, $hash, $id);
                } else {
                    $s = $db->prepare("UPDATE users SET nama=?, email=?, no_hp=?, alamat=? WHERE id=? AND role='anggota'");
                    $s->bind_param('ssssi', $nama, $email, $no_hp, $alamat, $id);
                }
                $s->execute();
                set_flash('Data anggota diperbarui.');
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $s = $db->prepare("INSERT INTO users (nama,email,password,no_hp,alamat,role) VALUES (?,?,?,?,?,'anggota')");
                $s->bind_param('sssss', $nama, $email, $hash, $no_hp, $alamat);
                $s->execute();
                set_flash('Anggota baru ditambahkan.');
            }
        } elseif ($aksi === 'hapus') {
            $id = (int)($_POST['id'] ?? 0);
            $c = $db->prepare("SELECT COUNT(*) FROM loans WHERE user_id=? AND status IN ('Diajukan','Dipinjam')");
            $c->bind_param('i', $id);
            $c->execute();
            if ($c->get_result()->fetch_row()[0] > 0) {
                set_flash('Anggota tidak bisa dihapus: masih punya peminjaman aktif.', 'danger');
            } else {
                $s = $db->prepare("DELETE FROM users WHERE id=? AND role='anggota'");
                $s->bind_param('i', $id);
                $s->execute();
                set_flash('Anggota dihapus.', 'secondary');
            }
        }
    } catch (mysqli_sql_exception $ex) {
        set_flash($ex->getCode() === 1062 ? 'Email sudah dipakai anggota lain.' : 'Terjadi kesalahan database.', 'danger');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';
$st = $db->prepare("SELECT id, nama, email, no_hp, alamat, created_at FROM users
                    WHERE role='anggota' AND (nama LIKE ? OR email LIKE ?) ORDER BY nama");
$st->bind_param('ss', $like, $like);
$st->execute();
$anggota = $st->get_result();

$judul = 'Kelola anggota';
$halaman = 'anggota';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
  <div><h1 class="h2 mb-1">Kelola anggota</h1><p class="text-secondary mb-0">Data anggota perpustakaan.</p></div>
  <button class="btn btn-brass" data-bs-toggle="modal" data-bs-target="#modalAnggota" onclick="isiForm(null)"><i class="bi bi-plus-lg me-1"></i>Tambah anggota</button>
</div>
<?php show_flash(); ?>

<form class="d-flex gap-2 mb-3" method="get" style="max-width:420px">
  <input class="form-control" name="q" placeholder="Cari nama atau email" value="<?= e($q) ?>">
  <button class="btn btn-forest"><i class="bi bi-search"></i></button>
</form>

<div class="panel">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Nama</th><th>Email</th><th>No. HP</th><th>Alamat</th><th>Bergabung</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php if ($anggota->num_rows === 0): ?>
        <tr><td colspan="6" class="text-center text-secondary py-4">Anggota tidak ditemukan.</td></tr>
      <?php endif; ?>
      <?php while ($a = $anggota->fetch_assoc()): ?>
        <tr>
          <td class="fw-semibold"><?= e($a['nama']) ?></td>
          <td><?= e($a['email']) ?></td>
          <td><?= e($a['no_hp']) ?></td>
          <td><?= e($a['alamat']) ?></td>
          <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
          <td class="text-end text-nowrap">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalAnggota"
                    data-row="<?= e(json_encode($a)) ?>" onclick="isiForm(this)" title="Ubah"><i class="bi bi-pencil"></i></button>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus anggota ini beserta riwayat peminjamannya?')">
              <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $a['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary" title="Hapus"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalAnggota" tabindex="-1">
  <div class="modal-dialog"><form method="post" class="modal-content">
    <div class="modal-header"><h2 class="modal-title h5" id="judulModal">Tambah anggota</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" id="f_id">
      <label class="form-label" for="f_nama">Nama lengkap</label><input class="form-control mb-3" name="nama" id="f_nama" required>
      <label class="form-label" for="f_email">Email</label><input type="email" class="form-control mb-3" name="email" id="f_email" required>
      <label class="form-label" for="f_no_hp">Nomor HP</label><input class="form-control mb-3" name="no_hp" id="f_no_hp">
      <label class="form-label" for="f_alamat">Alamat</label><textarea class="form-control mb-3" name="alamat" id="f_alamat" rows="2"></textarea>
      <label class="form-label" for="f_password">Password</label>
      <input type="password" class="form-control" name="password" id="f_password" minlength="6">
      <div class="form-text" id="infoPass">Wajib diisi untuk anggota baru.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-forest">Simpan anggota</button></div>
  </form></div>
</div>
<script>
function isiForm(btn) {
  const d = btn ? JSON.parse(btn.dataset.row) : {};
  document.getElementById('judulModal').textContent = btn ? 'Ubah anggota' : 'Tambah anggota';
  document.getElementById('f_id').value     = d.id ?? '';
  document.getElementById('f_nama').value   = d.nama ?? '';
  document.getElementById('f_email').value  = d.email ?? '';
  document.getElementById('f_no_hp').value  = d.no_hp ?? '';
  document.getElementById('f_alamat').value = d.alamat ?? '';
  document.getElementById('f_password').value = '';
  document.getElementById('f_password').required = !btn;
  document.getElementById('infoPass').textContent = btn ? 'Kosongkan jika password tidak diubah.' : 'Wajib diisi untuk anggota baru.';
}
</script>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
