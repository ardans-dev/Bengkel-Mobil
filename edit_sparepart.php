<?php include 'cek_login.php';
include 'koneksi.php';

$id = $_GET['id'];
$query_data = mysqli_query($koneksi, "SELECT * FROM sparepart WHERE id_sparepart='$id'");
$data_lama = mysqli_fetch_array($query_data);

if (isset($_POST['update'])) {
    $nama_sparepart = $_POST['nama_sparepart'];
    $stok           = $_POST['stok'];
    $harga          = str_replace(['.', ','], '', $_POST['harga']); 

    // Query UPDATE biasa, waktu akan otomatis diperbarui oleh MySQL
    $update = mysqli_query($koneksi, "UPDATE sparepart SET nama_sparepart='$nama_sparepart', stok='$stok', harga='$harga' WHERE id_sparepart='$id'");

    if ($update) {
        header("Location: data_sparepart.php");
        exit;
    } else {
        echo "<script>alert('Gagal mengubah data!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Sparepart - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'cek_login.php'; include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">Edit Data Sparepart</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Barang</label>
                                <input type="text" class="form-control" name="nama_sparepart" value="<?php echo $data_lama['nama_sparepart']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Stok</label>
                                <input type="number" class="form-control" name="stok" value="<?php echo $data_lama['stok']; ?>" required min="0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Harga Satuan (Rp)</label>
                                <input type="number" class="form-control" name="harga" value="<?php echo $data_lama['harga']; ?>" required min="0">
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="data_sparepart.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" name="update" class="btn btn-warning">Simpan Perubahan</button>
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
