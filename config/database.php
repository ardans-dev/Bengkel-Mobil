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
 * 4. Dilengkapi "Smart Credential Fallback": otomatis mengenali lingkungan WSL2 Linux
 *    maupun Laragon/XAMPP di Windows tanpa perlu ubah-ubah kodingan!
 */

// Mulai session global jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set zona waktu default ke Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

// ---------------------------------------------------------------------
// 1. Parameter Koneksi Database MySQL
// ---------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_bengkel_mobil');
define('DB_PORT', 3306);

/**
 * Mendapatkan instance koneksi database (PDO).
 * Mendukung auto-detection kredensial lokal (WSL2 vs Laragon/XAMPP).
 *
 * @return PDO Objek koneksi database aktif
 */
function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Daftar kombinasi user/password lokal (otomatis mencoba yang cocok)
        $credentials = [
            ['user' => 'ardans',  'pass' => 'admin123'], // User aktif MySQL WSL2
            ['user' => 'dbeaver', 'pass' => 'admin123'], // User DBeaver WSL2
            ['user' => 'root',    'pass' => ''],         // Default Laragon / XAMPP Windows
            ['user' => 'root',    'pass' => 'admin123'], // Alternatif root
        ];

        $last_error = null;
        foreach ($credentials as $cred) {
            try {
                $pdo = new PDO($dsn, $cred['user'], $cred['pass'], $options);
                break; // Berhasil terhubung, hentikan loop
            } catch (PDOException $e) {
                $last_error = $e;
            }
        }

        if ($pdo === null) {
            die("<div style='font-family:sans-serif; padding:20px; background:#fee2e2; color:#991b1b; border-radius:8px;'>"
                . "<h3>⚠️ Gagal Terhubung ke Database MySQL</h3>"
                . "<p>Pastikan MySQL sudah aktif dan database <b>" . DB_NAME . "</b> sudah di-import.</p>"
                . "<small>Pesan Error: " . htmlspecialchars($last_error ? $last_error->getMessage() : '') . "</small>"
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
        $msg  = strip_tags($flash['message'], '<b><strong><i><code><br>');
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

/**
 * ---------------------------------------------------------------------
 * 3. Helper Pengaturan Bengkel & Jam Operasional Dinamis
 * ---------------------------------------------------------------------
 */

/**
 * Mendapatkan konfigurasi umum bengkel dari database.
 */
function get_pengaturan_bengkel(): array {
    static $config = null;
    if ($config === null) {
        try {
            $stmt = db()->query("SELECT * FROM pengaturan_bengkel ORDER BY id ASC LIMIT 1");
            $config = $stmt->fetch();
        } catch (Exception $e) {
            $config = false;
        }

        if (!$config) {
            $config = [
                'nama_bengkel'     => 'Bengkel Ardans',
                'slogan'           => 'Solusi Terpercaya Perawatan & Perbaikan Mobil Anda',
                'alamat'           => 'Jl. Soekarno Hatta No. 450, Bandung, Jawa Barat (Samping Pool Bus)',
                'no_telepon'       => '0812-3456-7890',
                'no_whatsapp'      => '6281234567890',
                'email'            => 'kontak@bengkelardans.id',
                'jam_buka'         => '08:00:00',
                'jam_tutup'        => '16:00:00',
                'hari_operasional' => 'Senin - Sabtu (Minggu & Tanggal Merah Libur)',
                'status_manual'    => 'Otomatis',
                'pesan_pengumuman' => null
            ];
        }
    }
    return $config;
}

/**
 * Daftar tanggal merah / hari libur nasional Indonesia (Y-m-d).
 */
function daftar_hari_libur_nasional(int $year = null): array {
    $year = $year ?? (int)date('Y');
    
    $holidays = [
        // 2025
        '2025-01-01' => 'Tahun Baru 2025 Masehi',
        '2025-01-27' => 'Isra Mi\'raj Nabi Muhammad SAW',
        '2025-01-29' => 'Tahun Baru Imlek 2576',
        '2025-03-29' => 'Hari Suci Nyepi',
        '2025-03-31' => 'Hari Raya Idul Fitri 1446 H',
        '2025-04-01' => 'Hari Raya Idul Fitri 1446 H',
        '2025-04-18' => 'Wafat Yesus Kristus',
        '2025-05-01' => 'Hari Buruh Internasional',
        '2025-05-12' => 'Hari Raya Waisak 2569',
        '2025-05-29' => 'Kenaikan Yesus Kristus',
        '2025-06-01' => 'Hari Lahir Pancasila',
        '2025-06-07' => 'Hari Raya Idul Adha 1446 H',
        '2025-06-27' => 'Tahun Baru Islam 1447 H',
        '2025-08-17' => 'Hari Proklamasi Kemerdekaan RI',
        '2025-09-05' => 'Maulid Nabi Muhammad SAW',
        '2025-12-25' => 'Hari Raya Natal',

        // 2026
        '2026-01-01' => 'Tahun Baru 2026 Masehi',
        '2026-01-16' => 'Isra Mi\'raj Nabi Muhammad SAW',
        '2026-02-17' => 'Tahun Baru Imlek 2577 Kongzili',
        '2026-03-19' => 'Hari Suci Nyepi',
        '2026-03-20' => 'Hari Raya Idul Fitri 1447 H',
        '2026-03-21' => 'Hari Raya Idul Fitri 1447 H',
        '2026-04-03' => 'Wafat Yesus Kristus',
        '2026-05-01' => 'Hari Buruh Internasional',
        '2026-05-14' => 'Kenaikan Yesus Kristus',
        '2026-05-31' => 'Hari Raya Waisak 2570',
        '2026-06-01' => 'Hari Lahir Pancasila',
        '2026-05-27' => 'Hari Raya Idul Adha 1447 H',
        '2026-06-16' => 'Tahun Baru Islam 1448 H',
        '2026-08-17' => 'Hari Kemerdekaan RI',
        '2026-08-25' => 'Maulid Nabi Muhammad SAW',
        '2026-12-25' => 'Hari Raya Natal'
    ];

    $fixed = [
        "{$year}-01-01" => 'Tahun Baru Masehi',
        "{$year}-05-01" => 'Hari Buruh Internasional',
        "{$year}-06-01" => 'Hari Lahir Pancasila',
        "{$year}-08-17" => 'Hari Kemerdekaan RI',
        "{$year}-12-25" => 'Hari Raya Natal'
    ];

    return array_merge($holidays, $fixed);
}

/**
 * Mengecek status operasional bengkel secara real-time.
 * Memperhitungkan jam buka/tutup, hari Minggu, tanggal merah, dan override manual admin.
 */
function cek_status_bengkel(): array {
    $cfg = get_pengaturan_bengkel();

    $jam_buka_str  = substr($cfg['jam_buka'], 0, 5);  // e.g. "08:00"
    $jam_tutup_str = substr($cfg['jam_tutup'], 0, 5); // e.g. "16:00"
    $status_manual = $cfg['status_manual'] ?? 'Otomatis';
    $pesan_custom  = !empty($cfg['pesan_pengumuman']) ? trim($cfg['pesan_pengumuman']) : null;

    // 1. Cek Override Manual Admin
    if ($status_manual === 'Buka_Paksa') {
        return [
            'is_buka'     => true,
            'status'      => 'buka',
            'label'       => 'Buka Sekarang',
            'sublabel'    => "Operasional Khusus: {$jam_buka_str} - {$jam_tutup_str} WIB",
            'badge_class' => 'bg-success',
            'badge_icon'  => 'bi-door-open-fill',
            'pesan'       => $pesan_custom ?: "Bengkel sedang buka dan siap melayani perbaikan mobil Anda.",
            'jam_buka'    => $jam_buka_str,
            'jam_tutup'   => $jam_tutup_str,
            'config'      => $cfg
        ];
    }

    if ($status_manual === 'Tutup_Sementara') {
        return [
            'is_buka'     => false,
            'status'      => 'tutup',
            'label'       => 'Tutup Sementara',
            'sublabel'    => "Sedang Istirahat / Tutup Sesaat",
            'badge_class' => 'bg-danger',
            'badge_icon'  => 'bi-door-closed-fill',
            'pesan'       => $pesan_custom ?: "Mohon maaf, bengkel saat ini sedang tutup sementara dan akan buka kembali segera.",
            'jam_buka'    => $jam_buka_str,
            'jam_tutup'   => $jam_tutup_str,
            'config'      => $cfg
        ];
    }

    if ($status_manual === 'Libur_Mendadak') {
        return [
            'is_buka'     => false,
            'status'      => 'libur',
            'label'       => 'Libur Mendadak',
            'sublabel'    => "Pemberitahuan Khusus Hari Ini",
            'badge_class' => 'bg-warning text-dark',
            'badge_icon'  => 'bi-exclamation-triangle-fill',
            'pesan'       => $pesan_custom ?: "Mohon maaf, hari ini bengkel libur mendadak. Silakan hubungi WhatsApp kami untuk informasi lebih lanjut.",
            'jam_buka'    => $jam_buka_str,
            'jam_tutup'   => $jam_tutup_str,
            'config'      => $cfg
        ];
    }

    // 2. Evaluasi Otomatis berdasarkan Kalender & Jam Real-Time WIB
    $today_date = date('Y-m-d');
    $day_of_week = (int)date('w'); // 0 = Minggu
    $currentTime = date('H:i:s');

    // a. Cek apakah Hari Minggu (Libur Rutin)
    if ($day_of_week === 0) {
        return [
            'is_buka'     => false,
            'status'      => 'libur',
            'label'       => 'Libur Hari Minggu',
            'sublabel'    => "Buka Kembali Senin Pukul {$jam_buka_str} WIB",
            'badge_class' => 'bg-secondary',
            'badge_icon'  => 'bi-calendar-x',
            'pesan'       => $pesan_custom ?: "Hari Minggu bengkel libur rutin. Kami siap melayani Anda kembali di hari Senin jam {$jam_buka_str} WIB.",
            'jam_buka'    => $jam_buka_str,
            'jam_tutup'   => $jam_tutup_str,
            'config'      => $cfg
        ];
    }

    // b. Cek apakah Tanggal Merah / Libur Nasional
    $libur_list = daftar_hari_libur_nasional((int)date('Y'));
    if (isset($libur_list[$today_date])) {
        $nama_libur = $libur_list[$today_date];
        return [
            'is_buka'     => false,
            'status'      => 'libur',
            'label'       => 'Libur Tanggal Merah',
            'sublabel'    => $nama_libur,
            'badge_class' => 'bg-warning text-dark',
            'badge_icon'  => 'bi-calendar-event',
            'pesan'       => $pesan_custom ?: "Hari ini bengkel libur nasional ({$nama_libur}). Buka kembali di hari kerja berikutnya.",
            'jam_buka'    => $jam_buka_str,
            'jam_tutup'   => $jam_tutup_str,
            'config'      => $cfg
        ];
    }

    // c. Cek Jam Operasional (Buka vs Tutup)
    if ($currentTime >= $cfg['jam_buka'] && $currentTime < $cfg['jam_tutup']) {
        return [
            'is_buka'     => true,
            'status'      => 'buka',
            'label'       => "Buka Sekarang ({$jam_buka_str} - {$jam_tutup_str} WIB)",
            'sublabel'    => "Siap Melayani Servis & Konsultasi",
            'badge_class' => 'bg-success',
            'badge_icon'  => 'bi-check-circle-fill',
            'pesan'       => $pesan_custom ?: "Bengkel sedang beroperasi normal. Silakan datang langsung ke bengkel kami.",
            'jam_buka'    => $jam_buka_str,
            'jam_tutup'   => $jam_tutup_str,
            'config'      => $cfg
        ];
    }

    // Di luar jam operasional
    $ket = ($currentTime < $cfg['jam_buka']) 
        ? "Buka Pagi Ini Pukul {$jam_buka_str} WIB"
        : "Buka Besok Pagi Pukul {$jam_buka_str} WIB";

    return [
        'is_buka'     => false,
        'status'      => 'tutup',
        'label'       => 'Sedang Tutup',
        'sublabel'    => $ket,
        'badge_class' => 'bg-danger',
        'badge_icon'  => 'bi-clock-history',
        'pesan'       => $pesan_custom ?: "Bengkel saat ini sedang tutup. Jam operasional kami setiap Senin - Sabtu pukul {$jam_buka_str} s/d {$jam_tutup_str} WIB.",
        'jam_buka'    => $jam_buka_str,
        'jam_tutup'   => $jam_tutup_str,
        'config'      => $cfg
    ];
}
