<?php
require_once __DIR__ . '/../includes/config.php';
require_role('anggota');
$uid = (int)$_SESSION['user']['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim($_POST['nama'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $no_hp  = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $pass   = $_POST['password'] ?? '';

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Isi nama dan email yang valid.';
    } elseif ($pass !== '' && strlen($pass) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } else {
        try {
            if ($pass !== '') {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $s = $db->prepare("UPDATE users SET nama=?, email=?, no_hp=?, alamat=?, password=? WHERE id=?");
                $s->bind_param('sssssi', $nama, $email, $no_hp, $alamat, $hash, $uid);
            } else {
                $s = $db->prepare("UPDATE users SET nama=?, email=?, no_hp=?, alamat=? WHERE id=?");
                $s->bind_param('ssssi', $nama, $email, $no_hp, $alamat, $uid);
            }
            $s->execute();
            $_SESSION['user']['nama'] = $nama;
            $_SESSION['user']['email'] = $email;
            set_flash('Profil berhasil disimpan.');
            header('Location: /anggota/profil.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            $error = $ex->getCode() === 1062 ? 'Email sudah dipakai anggota lain.' : 'Terjadi kesalahan database.';
        }
    }
}

$s = $db->prepare("SELECT nama, email, no_hp, alamat FROM users WHERE id = ?");
$s->bind_param('i', $uid);
$s->execute();
$me = $s->get_result()->fetch_assoc();
if ($error) $me = ['nama' => $nama, 'email' => $email, 'no_hp' => $no_hp, 'alamat' => $alamat];

$judul = 'Profil';
require __DIR__ . '/../includes/header.php';
?>
<div class="container mt-4" style="max-width:640px">
  <h1 class="h2 mb-1">Profil saya</h1>
  <p class="text-secondary mb-3">Data ini dipakai otomatis saat kamu mengajukan peminjaman.</p>
  <?php show_flash(); ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

  <form method="post" class="panel">
    <label class="form-label" for="nama">Nama lengkap</label>
    <input class="form-control mb-3" id="nama" name="nama" required value="<?= e($me['nama']) ?>">
    <label class="form-label" for="email">Email</label>
    <input type="email" class="form-control mb-3" id="email" name="email" required value="<?= e($me['email']) ?>">
    <label class="form-label" for="no_hp">Nomor HP</label>
    <input class="form-control mb-3" id="no_hp" name="no_hp" value="<?= e($me['no_hp']) ?>">
    <label class="form-label" for="alamat">Alamat</label>
    <textarea class="form-control mb-3" id="alamat" name="alamat" rows="2"><?= e($me['alamat']) ?></textarea>
    <label class="form-label" for="password">Password baru</label>
    <input type="password" class="form-control" id="password" name="password" minlength="6">
    <div class="form-text mb-4">Kosongkan jika tidak ingin mengganti password.</div>
    <button class="btn btn-forest px-4">Simpan profil</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
