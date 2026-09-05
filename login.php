<?php
/**
 * =====================================================================
 * FILE: login.php
 * DESKRIPSI: Halaman Autentikasi Pengguna & Pemrosesan Login Aman
 * =====================================================================
 * 
 * FITUR KEAMANAN:
 * 1. Prepared Statements PDO: Mencegah eksploitasi SQL Injection 100%.
 * 2. Bcrypt Password Verify: Mengecek kecocokan hash password dengan aman.
 * 3. session_regenerate_id: Mencegah serangan Session Fixation.
 */

require_once __DIR__ . '/config/database.php';

// Jika pengguna sudah memiliki sesi login aktif, langsung arahkan ke Dashboard
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    redirect('dashboard.php');
}

$error = null;

// =====================================================================
// PEMROSESAN FORM LOGIN (METODE POST)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil input dari form dan hapus spasi berlebih di awal/akhir string
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validasi input sederhana: Pastikan username dan password tidak kosong
    if (empty($username) || empty($password)) {
        $error = "Username dan Password wajib diisi!";
    } else {
        // AMAN DARI SQL INJECTION:
        // Kita menggunakan placeholder tanda tanya (?) alih-alih menggabungkan variabel langsung.
        $stmt = db()->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Cek apakah akun ditemukan DAN password sesuai dengan hash Bcrypt di database
        if ($user && password_verify($password, $user['password'])) {
            // KEAMANAN TINGKAT LANJUT:
            // Regenerasi ID Session setiap kali berhasil login untuk mencegah Session Hijacking/Fixation
            session_regenerate_id(true);

            // Simpan data identitas pengguna ke dalam Session server
            $_SESSION['is_logged_in']   = true;
            $_SESSION['user_id']        = (int)$user['id_admin'];
            $_SESSION['user_nama']      = $user['nama_lengkap'];
            $_SESSION['user_username']  = $user['username'];
            $_SESSION['user_role']      = $user['role'];

            // Tampilkan pesan selamat datang di Dashboard
            set_flash('success', "Selamat datang kembali, <b>" . e($user['nama_lengkap']) . "</b>!");
            redirect('dashboard.php');
        } else {
            // Berikan pesan kegagalan yang netral agar hacker tidak tahu apakah username atau password yang salah
            $error = "Username atau Password yang Anda masukkan salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bengkel Mobil Ardans</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.3), 0 8px 10px -6px rgb(0 0 0 / 0.3);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .login-header {
            background: #0284c7;
            padding: 30px 24px;
            text-align: center;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Header Kartu Login -->
        <div class="login-header">
            <div class="display-6 mb-2"><i class="bi bi-gear-wide-connected"></i></div>
            <h4 class="fw-bold mb-1">Bengkel Mobil Ardans</h4>
            <p class="small mb-0 text-white-50">Sistem Manajemen Operasional & Kasir POS</p>
        </div>

        <!-- Badan Form Login -->
        <div class="p-4">
            <!-- Tampilkan Alert Error jika login gagal -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Form Pengisian Kredensial -->
            <form action="login.php" method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Username Pegawai</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Contoh: admin / kasir" required autofocus value="<?= isset($_POST['username']) ? e($_POST['username']) : '' ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Password Keamanan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password akun" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
                </button>
            </form>

            <!-- Bantuan Akun Demo untuk Pengujian -->
            <div class="mt-4 p-3 bg-light rounded-3 small text-secondary border">
                <div class="fw-bold mb-1 text-dark"><i class="bi bi-info-circle me-1"></i> Akun Pengujian Demo:</div>
                <div>👑 <b>Admin:</b> <code>admin</code> / <code>admin123</code></div>
                <div>💼 <b>Kasir:</b> <code>kasir</code> / <code>kasir123</code></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
