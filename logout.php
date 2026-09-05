<?php
/**
 * =====================================================================
 * FILE: logout.php
 * DESKRIPSI: Pembersihan Sesi dan Keluar dari Sistem
 * =====================================================================
 */

require_once __DIR__ . '/config/database.php';

// Hapus semua variabel yang tersimpan di dalam array session
$_SESSION = [];

// Jika menggunakan cookie session, hapus cookie tersebut dari browser klien
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan session server secara total
session_destroy();

// Mulai sesi baru khusus untuk menampung pesan flash perpisahan
session_start();
set_flash('info', 'Anda telah berhasil keluar dari sistem.');
redirect('login.php');
