<?php
/**
 * =====================================================================
 * FILE: config/database.php
 * DESKRIPSI: Konfigurasi Koneksi Database Menggunakan PDO (PHP Data Objects)
 * & Kumpulan Helper Fungsi Keamanan Sistem Bengkel Mobil.
 * =====================================================================
 * 
 * KENAPA MENGGUNAKAN PDO BUKAN MYSQLI BIASA?
 * 1. Mendukung "Real Prepared Statements" yang 100% kebal dari SQL Injection.
 * 2. Menggunakan "Exception Error Handling" sehingga jika terjadi error query,
 *    sistem tidak akan membocorkan struktur tabel ke layar pengguna.
 * 3. Menggunakan pola singleton sederhana agar koneksi tidak dibuat berulang kali.
 */

// Mulai session global jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------------
// 1. Parameter Koneksi Database MySQL
// ---------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Kosongkan untuk default Laragon / XAMPP
define('DB_NAME', 'db_bengkel_mobil');
define('DB_PORT', 3306);

/**
 * Mendapatkan instance koneksi database (PDO).
 * Fungsi ini menggunakan teknik static variable agar koneksi database
 * hanya dibuat satu kali dalam satu siklus HTTP request (hemat memori).
 *
 * @return PDO Objek koneksi database aktif
 */
function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $options = [
            // Munculkan exception jika query SQL error (mudah di-debug saat dev)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Format data yang diambil otomatis menjadi array asosiatif ['kolom' => 'nilai']
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // MATIKAN emulated prepared statements agar SQL dieksekusi secara native oleh MySQL
            // Ini adalah perlindungan nomor 1 terhadap SQL Injection!
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Catat error ke log server dan tampilkan pesan ramah (jangan bocorkan password ke user)
            die("<div style='font-family:sans-serif; padding:20px; background:#fee2e2; color:#991b1b; border-radius:8px;'>"
                . "<h3>⚠️ Gagal Terhubung ke Database MySQL</h3>"
                . "<p>Pastikan MySQL di Laragon / XAMPP sudah aktif dan database <b>" . DB_NAME . "</b> sudah di-import.</p>"
                . "<small>Pesan Error: " . htmlspecialchars($e->getMessage()) . "</small>"
                . "</div>");
        }
    }

    return $pdo;
}

// ---------------------------------------------------------------------
// 2. Kumpulan Helper Fungsi Keamanan & Utility
// ---------------------------------------------------------------------

/**
 * Helper Anti-XSS (Cross-Site Scripting).
 * Selalu bungkus data teks dari database dengan fungsi e() sebelum dicetak ke HTML.
 * Mengubah karakter berbahaya seperti <script> menjadi teks entitas yang aman (&lt;script&gt;).
 *
 * @param mixed $data Data string yang ingin dibersihkan
 * @return string Teks aman
 */
function e($data): string {
    return htmlspecialchars((string)($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka nominal ke format Rupiah Indonesia yang rapi.
 * Contoh: 150000 -> "Rp 150.000"
 *
 * @param float|int|string $angka Nominal angka
 * @return string Teks nominal rupiah
 */
function rupiah($angka): string {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format tanggal dari MySQL ke format Indonesia yang mudah dibaca.
 * Contoh: "2026-09-05 14:30:00" -> "05 Sep 2026, 14:30"
 *
 * @param string $datetime String tanggal dari MySQL
 * @return string Tanggal rapi
 */
function format_tanggal(string $datetime): string {
    if (empty($datetime)) return '-';
    $time = strtotime($datetime);
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    return date('d', $time) . ' ' . $bulan[(int)date('m', $time)] . ' ' . date('Y, H:i', $time);
}

/**
 * Menyimpan pesan flash (notifikasi sekali pakai) ke dalam Session.
 * Berguna untuk menampilkan pesan sukses/gagal setelah proses redirect form.
 *
 * @param string $type Jenis pesan: 'success', 'danger', 'warning', 'info'
 * @param string $message Isi pesan yang ingin ditampilkan
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Menampilkan pesan flash ke layar jika ada, lalu langsung menghapusnya dari session.
 */
function display_flash(): void {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        $type = e($flash['type']);
        $msg  = e($flash['message']);
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        unset($_SESSION['flash_message']);
    }
}

/**
 * Mengalihkan halaman (Redirect) dan menghentikan eksekusi script.
 *
 * @param string $url URL tujuan
 */
function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}
