<?php
require_once __DIR__ . '/../includes/config.php';
require_role('anggota');

const MAKS_PINJAM = 3;   // batas pinjaman aktif per anggota
$uid = (int)$_SESSION['user']['id'];
$bid = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$s = $db->prepare("SELECT * FROM books WHERE id = ?");
$s->bind_param('i', $bid);
$s->execute();
$buku = $s->get_result()->fetch_assoc();
if (!$buku) {
    set_flash('Buku tidak ditemukan.', 'danger');
    header('Location: /anggota/katalog.php');
    exit;
}

// Hitung pinjaman aktif milik anggota ini
$s = $db->prepare("SELECT COUNT(*) FROM loans WHERE user_id = ? AND status IN ('Diajukan','Dipinjam')");
$s->bind_param('i', $uid);
$s->execute();
$aktif = (int)$s->get_result()->fetch_row()[0];

// Apakah buku yang sama sedang diajukan / dipinjam?
$s = $db->prepare("SELECT COUNT(*) FROM loans WHERE user_id = ? AND book_id = ? AND status IN ('Diajukan','Dipinjam')");
$s->bind_param('ii', $uid, $bid);
$s->execute();
$sama = (int)$s->get_result()->fetch_row()[0];

$masalah = '';
if ($buku['stok'] < 1)            $masalah = 'Buku ini sedang habis. Coba lagi nanti.';
elseif ($sama > 0)                $masalah = 'Kamu sudah mengajukan atau sedang meminjam buku ini.';
elseif ($aktif >= MAKS_PINJAM)    $masalah = 'Batas pinjaman aktif adalah ' . MAKS_PINJAM . ' buku. Kembalikan salah satu dulu.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $masalah === '') {
    $s = $db->prepare("INSERT INTO loans (user_id, book_id, tgl_ajuan, status) VALUES (?, ?, CURDATE(), 'Diajukan')");
    $s->bind_param('ii', $uid, $bid);
    $s->execute();
    set_flash('Pengajuan terkirim. Tunggu persetujuan petugas.');
    header('Location: /anggota/riwayat.php');
    exit;
}

$s = $db->prepare("SELECT nama, email, no_hp, alamat FROM users WHERE id = ?");
$s->bind_param('i', $uid);
$s->execute();
$me = $s->get_result()->fetch_assoc();

$judul = 'Ajukan peminjaman';
require __DIR__ . '/../includes/header.php';
?>
<div class="container mt-4" style="max-width:760px">
  <a href="/anggota/katalog.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke katalog</a>
  <h1 class="h2 mt-2 mb-4">Ajukan peminjaman</h1>

  <?php if ($masalah): ?><div class="alert alert-danger"><?= e($masalah) ?></div><?php endif; ?>

  <div class="panel mb-3">
    <div class="text-secondary small">Buku yang dipilih</div>
    <h2 class="h4 mb-1"><?= e($buku['judul']) ?></h2>
    <div><?= e($buku['penulis']) ?> &middot; <?= e($buku['penerbit']) ?>, <?= e($buku['tahun']) ?></div>
    <div class="mt-2"><span class="badge-status <?= $buku['stok'] > 0 ? 'st-tersedia' : 'st-habis' ?>">
      <?= $buku['stok'] > 0 ? 'Tersedia (' . (int)$buku['stok'] . ')' : 'Habis' ?></span></div>
  </div>

  <div class="panel mb-3">
    <div class="d-flex justify-content-between align-items-start">
      <div class="text-secondary small mb-2">Data peminjam</div>
      <a href="/anggota/profil.php" class="small">Ubah data</a>
    </div>
    <div class="row g-2">
      <div class="col-sm-6"><div class="small text-secondary">Nama</div><?= e($me['nama']) ?></div>
      <div class="col-sm-6"><div class="small text-secondary">Email</div><?= e($me['email']) ?></div>
      <div class="col-sm-6"><div class="small text-secondary">No. HP</div><?= e($me['no_hp'] ?: '-') ?></div>
      <div class="col-sm-6"><div class="small text-secondary">Alamat</div><?= e($me['alamat'] ?: '-') ?></div>
    </div>
  </div>

  <div class="panel">
    <p class="mb-2">Setelah disetujui, batas pengembalian adalah <strong><?= LAMA_PINJAM ?> hari</strong>.
       Keterlambatan dikenai denda <strong><?= rupiah(DENDA_PER_HARI) ?> per hari</strong>.</p>
    <p class="text-secondary small">Pinjaman aktif kamu: <?= $aktif ?> dari <?= MAKS_PINJAM ?>.</p>
    <form method="post">
      <input type="hidden" name="id" value="<?= $bid ?>">
      <button class="btn btn-brass px-4" <?= $masalah ? 'disabled' : '' ?>>Kirim pengajuan</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
