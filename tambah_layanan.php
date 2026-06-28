<?php include 'cek_login.php';
include 'koneksi.php';

if (isset($_POST['simpan'])) {
    $nama_layanan = $_POST['nama_layanan'];
    $biaya_jasa   = str_replace(['.', ','], '', $_POST['biaya_jasa']); 

    $query = "INSERT INTO layanan (nama_layanan, biaya_jasa) VALUES ('$nama_layanan', '$biaya_jasa')";
    $insert = mysqli_query($koneksi, $query);

    if ($insert) {
        header("Location: data_layanan.php");
        exit;
    } else {
        echo "<script>alert('Gagal!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Layanan - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'cek_login.php'; include 'navbar.php'; ?>
    <div class="container mt-4">
        <div class="card col-md-6 mx-auto">
            <div class="card-header bg-primary text-white">Tambah Layanan Jasa</div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label>Nama Layanan</label>
                        <input type="text" class="form-control" name="nama_layanan" required placeholder="Contoh: Ganti Oli, Tune Up">
                    </div>
                    <div class="mb-3">
                        <label>Biaya Jasa (Rp)</label>
                        <input type="number" class="form-control" name="biaya_jasa" required placeholder="Contoh: 50000">
                    </div>
                    <button type="submit" name="simpan" class="btn btn-success">Simpan Data</button>
                    <a href="data_layanan.php" class="btn btn-secondary">Kembali</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
