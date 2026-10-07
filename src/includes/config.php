<?php
// File ini dipanggil di SEMUA halaman: koneksi database + fungsi bantu.
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Host database = nama service di docker-compose ("db"), BUKAN localhost
$db = new mysqli(
    getenv('DB_HOST') ?: 'db',
    getenv('MYSQL_USER'),
    getenv('MYSQL_PASSWORD'),
    getenv('MYSQL_DATABASE')
);
$db->set_charset('utf8mb4');

define('DENDA_PER_HARI', 1000);
define('LAMA_PINJAM', 7);

function e($teks) { return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8'); }
function rupiah($angka) { return 'Rp' . number_format((int)$angka, 0, ',', '.'); }

function require_login() {
    if (empty($_SESSION['user'])) { header('Location: /login.php'); exit; }
}
function require_role($role) {
    require_login();
    if ($_SESSION['user']['role'] !== $role) { http_response_code(403); die('Akses ditolak.'); }
}
function set_flash($pesan, $tipe = 'success') { $_SESSION['flash'] = [$pesan, $tipe]; }
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        [$pesan, $tipe] = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($tipe) . ' alert-dismissible fade show" role="alert">'
           . e($pesan) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}
