<?php
include 'cek_login.php';
include 'koneksi.php';

$id = $_GET['id'];
$query_data = mysqli_query($koneksi, "SELECT * FROM layanan WHERE id_layanan='$id'");
$data_lama = mysqli_fetch_array($query_data);

if (isset($_POST['update'])) {
    $nama_layanan = $_POST['nama_layanan'];
    $biaya_jasa   = str_replace(['.', ','], '', $_POST['biaya_jasa']); 

    mysqli_query($koneksi, "UPDATE layanan SET nama_layanan='$nama_layanan', biaya_jasa='$biaya_jasa' WHERE id_layanan='$id'");
    header("Location: data_layanan.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Layanan - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card col-md-6 mx-auto">
            <div class="card-header bg-warning">Edit Data Layanan</div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label>Nama Layanan</label>
                        <input type="text" class="form-control" name="nama_layanan" value="<?php echo $data_lama['nama_layanan']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Biaya Jasa (Rp)</label>
                        <input type="number" class="form-control" name="biaya_jasa" value="<?php echo $data_lama['biaya_jasa']; ?>" required>
                    </div>
                    <button type="submit" name="update" class="btn btn-warning">Simpan Perubahan</button>
                    <a href="data_layanan.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
