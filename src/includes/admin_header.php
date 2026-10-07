<?php
// Header untuk halaman admin (sidebar kiri). Otomatis menolak non-admin.
require_once __DIR__ . '/config.php';
require_role('admin');
$judul = $judul ?? 'Admin';
$halaman = $halaman ?? '';
$menu = [
  'dashboard'  => ['/admin/dashboard.php',  'bi-speedometer2', 'Dashboard'],
  'buku'       => ['/admin/buku.php',       'bi-journal-bookmark', 'Kelola buku'],
  'anggota'    => ['/admin/anggota.php',    'bi-people', 'Kelola anggota'],
  'peminjaman' => ['/admin/peminjaman.php', 'bi-arrow-left-right', 'Peminjaman'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul) ?> | Admin PustakaKu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="admin-wrap">
  <aside class="sidebar">
    <a class="brand d-block mb-4" href="/"><i class="bi bi-book-half"></i> PustakaKu</a>
    <nav class="d-grid gap-1">
      <?php foreach ($menu as $kunci => [$url, $ikon, $label]): ?>
        <a href="<?= $url ?>" class="<?= $halaman === $kunci ? 'active' : '' ?>"><i class="bi <?= $ikon ?> me-2"></i><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-user">
      <div class="small opacity-75">Masuk sebagai</div>
      <div class="fw-semibold"><?= e($_SESSION['user']['nama']) ?></div>
      <a href="/logout.php" class="mt-2"><i class="bi bi-box-arrow-left me-2"></i>Keluar</a>
    </div>
  </aside>
  <main class="admin-main">
