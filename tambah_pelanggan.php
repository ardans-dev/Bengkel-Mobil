<?php include 'cek_login.php';
// Memanggil file koneksi database
include 'koneksi.php';

// Memeriksa apakah tombol simpan sudah diklik atau belum
if (isset($_POST['simpan'])) {
    // Mengambil data dari form input
    $nama       = $_POST['nama'];
    $no_telepon = $_POST['no_telepon'];
    $alamat     = $_POST['alamat'];

    // Query untuk memasukkan data ke tabel pelanggan
    $query = "INSERT INTO pelanggan (nama, no_telepon, alamat) VALUES ('$nama', '$no_telepon', '$alamat')";
    $insert = mysqli_query($koneksi, $query);

    // Memeriksa apakah proses insert berhasil
    if ($insert) {
        // Jika berhasil, alihkan halaman kembali ke data_pelanggan.php
        header("Location: data_pelanggan.php");
        exit;
    } else {
        // Jika gagal, tampilkan pesan error
        echo "<script>alert('Gagal menambahkan data pelanggan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pelanggan - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'cek_login.php'; include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Form Tambah Pelanggan Baru</h4>
                    </div>
                    <div class="card-body">
                        <form action="tambah_pelanggan.php" method="POST">
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama" name="nama" required placeholder="Masukkan nama pelanggan">
                            </div>
                            <div class="mb-3">
                                <label for="no_telepon" class="form-label">No. Telepon</label>
                                <input type="text" class="form-control" id="no_telepon" name="no_telepon" placeholder="Contoh: 08123456789">
                            </div>
                            <div class="mb-3">
                                <label for="alamat" class="form-label">Alamat</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3" placeholder="Masukkan alamat lengkap"></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="data_pelanggan.php" class="btn btn-secondary">Kembali</a>
                                <button type="submit" name="simpan" class="btn btn-success">Simpan Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
