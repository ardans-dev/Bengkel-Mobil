<?php
// Cek apakah session belum berjalan. Jika belum, baru jalankan session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika tidak ada tiket login, tendang kembali ke halaman login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}
?>