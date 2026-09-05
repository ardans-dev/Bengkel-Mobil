<?php
/**
 * =====================================================================
 * FILE: includes/auth.php
 * DESKRIPSI: Guard / Satpam Autentikasi Session Pengguna
 * =====================================================================
 * 
 * CARA KERJA:
 * File ini cukup di-include di baris paling atas setiap halaman admin/kasir.
 * Sistem akan memeriksa apakah pengguna sudah memiliki tiket login yang sah di session.
 * Jika tidak ada atau sudah kedaluwarsa, pengguna langsung ditendang ke halaman login.php.
 */

// Pastikan konfigurasi database dan session sudah aktif
require_once __DIR__ . '/../config/database.php';

// Validasi tiket login pada Session
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    set_flash('danger', 'Sesi Anda telah berakhir. Silakan login kembali.');
    redirect('login.php');
}

/**
 * Mendapatkan data pengguna yang sedang login saat ini.
 *
 * @return array Berisi id_admin, nama_lengkap, username, role
 */
function current_user(): array {
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'nama'     => $_SESSION['user_nama'] ?? 'Pengguna',
        'username' => $_SESSION['user_username'] ?? '',
        'role'     => $_SESSION['user_role'] ?? 'kasir'
    ];
}
