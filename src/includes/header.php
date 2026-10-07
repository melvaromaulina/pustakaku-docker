<?php
// Header untuk halaman publik & anggota (navbar atas).
require_once __DIR__ . '/config.php';
$judul = $judul ?? 'PustakaKu';
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul) ?> | PustakaKu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg site-nav">
  <div class="container">
    <a class="navbar-brand brand" href="/"><i class="bi bi-book-half"></i> PustakaKu</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><i class="bi bi-list text-white fs-3"></i></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <?php if ($user && $user['role'] === 'anggota'): ?>
          <li class="nav-item"><a class="nav-link" href="/anggota/katalog.php">Katalog</a></li>
          <li class="nav-item"><a class="nav-link" href="/anggota/riwayat.php">Peminjaman saya</a></li>
          <li class="nav-item"><a class="nav-link" href="/anggota/profil.php">Profil</a></li>
          <li class="nav-item"><a class="btn btn-brass btn-sm" href="/logout.php">Keluar</a></li>
        <?php elseif ($user): ?>
          <li class="nav-item"><a class="nav-link" href="/admin/dashboard.php">Dashboard admin</a></li>
          <li class="nav-item"><a class="btn btn-brass btn-sm" href="/logout.php">Keluar</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/login.php">Masuk</a></li>
          <li class="nav-item"><a class="btn btn-brass btn-sm" href="/register.php">Daftar anggota</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
