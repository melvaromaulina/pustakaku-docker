<?php
require __DIR__ . '/includes/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Isi nama dan email yang valid.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $cek = $db->prepare("SELECT id FROM users WHERE email = ?");
        $cek->bind_param('s', $email);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar. Gunakan email lain atau masuk.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO users (nama,email,password,no_hp,alamat,role) VALUES (?,?,?,?,?, 'anggota')");
            $ins->bind_param('sssss', $nama, $email, $hash, $no_hp, $alamat);
            $ins->execute();
            set_flash('Akun berhasil dibuat. Silakan masuk.');
            header('Location: /login.php');
            exit;
        }
    }
}
$judul = 'Daftar anggota';
require __DIR__ . '/includes/header.php';
?>
<div class="auth">
  <div class="auth-side">
    <h2>Jadi anggota dalam semenit.</h2>
    <p class="mt-3 opacity-75">Data diri disimpan sekali di profil, jadi mengajukan pinjaman tinggal pilih buku.</p>
  </div>
  <div class="auth-form">
    <form method="post">
      <h1 class="h3 mb-4">Daftar anggota</h1>
      <?php show_flash(); ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
      <label class="form-label" for="nama">Nama lengkap</label>
      <input class="form-control mb-3" id="nama" name="nama" required value="<?= e($_POST['nama'] ?? '') ?>">
      <label class="form-label" for="email">Email</label>
      <input class="form-control mb-3" id="email" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
      <label class="form-label" for="no_hp">Nomor HP</label>
      <input class="form-control mb-3" id="no_hp" name="no_hp" value="<?= e($_POST['no_hp'] ?? '') ?>">
      <label class="form-label" for="alamat">Alamat</label>
      <textarea class="form-control mb-3" id="alamat" name="alamat" rows="2"><?= e($_POST['alamat'] ?? '') ?></textarea>
      <label class="form-label" for="password">Password</label>
      <input class="form-control mb-4" id="password" type="password" name="password" minlength="6" required>
      <button class="btn btn-forest w-100 py-2">Buat akun</button>
      <p class="mt-3 mb-0 text-center">Sudah punya akun? <a href="/login.php">Masuk</a></p>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
