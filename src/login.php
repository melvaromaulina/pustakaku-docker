<?php
require __DIR__ . '/includes/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $stmt = $db->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'nama' => $u['nama'], 'email' => $u['email'], 'role' => $u['role']];
        header('Location: ' . ($u['role'] === 'admin' ? '/admin/dashboard.php' : '/anggota/katalog.php'));
        exit;
    }
    $error = 'Email atau password salah. Periksa lagi lalu coba masuk.';
}
$judul = 'Masuk';
require __DIR__ . '/includes/header.php';
?>
<div class="auth">
  <div class="auth-side">
    <h2>Selamat datang kembali.</h2>
    <p class="mt-3 opacity-75">Masuk untuk melihat katalog, mengajukan peminjaman, dan memantau jatuh tempo.</p>
  </div>
  <div class="auth-form">
    <form method="post">
      <h1 class="h3 mb-4">Masuk</h1>
      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
      <label class="form-label" for="email">Email</label>
      <input class="form-control mb-3" id="email" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
      <label class="form-label" for="password">Password</label>
      <input class="form-control mb-4" id="password" type="password" name="password" required>
      <button class="btn btn-forest w-100 py-2">Masuk</button>
      <p class="mt-3 mb-0 text-center">Belum punya akun? <a href="/register.php">Daftar anggota</a></p>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
