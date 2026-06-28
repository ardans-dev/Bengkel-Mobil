<?php include 'cek_login.php';
include 'koneksi.php';

if (isset($_POST['simpan'])) {
    $nama_mekanik = $_POST['nama_mekanik'];
    $status       = $_POST['status'];
    $alamat       = $_POST['alamat'];
    $query = "INSERT INTO mekanik (nama_mekanik, status, alamat) VALUES ('$nama_mekanik', '$status', '$alamat')";
    $insert = mysqli_query($koneksi, $query);

    if ($insert) {
        header("Location: data_mekanik.php");
        exit;
    } else {
        echo "<script>alert('Gagal menambahkan data mekanik!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Mekanik - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'cek_login.php'; include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Form Tambah Mekanik</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Teknisi / Mekanik</label>
                                <input type="text" class="form-control" name="nama_mekanik" required placeholder="Masukkan nama mekanik">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" name="alamat" rows="2" placeholder="Masukkan alamat mekanik"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Cuti">Cuti / Libur</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="data_mekanik.php" class="btn btn-secondary">Kembali</a>
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
