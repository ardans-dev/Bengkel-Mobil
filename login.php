<?php
session_start();
include 'koneksi.php';

// Jika tombol login ditekan
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ambil data admin berdasarkan username
    $query = mysqli_query($koneksi, "SELECT * FROM admin WHERE username='$username'");
    $data = mysqli_fetch_assoc($query);
    
    // Cek kecocokan password dengan password_verify
    if ($data && password_verify($password, $data['password'])) {
        // Jika cocok, buat tiket session dan arahkan ke dashboard
        $_SESSION['status_login'] = true;
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-secondary d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow" style="width: 25rem;">
        <div class="card-header bg-dark text-white text-center py-3">
            <h4 class="mb-0">Login Pegawai</h4>
        </div>
        <div class="card-body p-4">
            <?php if(isset($error)) { echo "<div class='alert alert-danger'>$error</div>"; } ?>
            
            <form action="" method="POST">
                <div class="mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
                </div>
                <div class="mb-4">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100 fw-bold">MASUK</button>
            </form>
            <div class="text-center mt-3">
                <a href="index.php" class="text-decoration-none text-muted">< Kembali ke Halaman Utama</a>
            </div>
        </div>
    </div>
</body>
</html>