<?php include 'cek_login.php';
include 'koneksi.php';
$id = $_GET['id'];
$query_data = mysqli_query($koneksi, "SELECT * FROM mekanik WHERE id_mekanik='$id'");
$data_lama = mysqli_fetch_array($query_data);

if (isset($_POST['update'])) {
    $nama_mekanik = $_POST['nama_mekanik'];
    $status       = $_POST['status'];
    $alamat       = $_POST['alamat']; // Ambil input baru

    // Update query menyertakan alamat
    mysqli_query($koneksi, "UPDATE mekanik SET nama_mekanik='$nama_mekanik', status='$status', alamat='$alamat' WHERE id_mekanik='$id'");
    header("Location: data_mekanik.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Mekanik - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'cek_login.php'; include 'navbar.php'; ?>
    <div class="container mt-4">
        <div class="card col-md-6 mx-auto">
            <div class="card-header bg-warning">Edit Data Mekanik</div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label>Nama Mekanik</label>
                        <input type="text" class="form-control" name="nama_mekanik" value="<?php include 'cek_login.php'; echo $data_lama['nama_mekanik']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Alamat</label>
                        <textarea class="form-control" name="alamat" rows="2"><?php include 'cek_login.php'; echo $data_lama['alamat']; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select class="form-select" name="status">
                            <option value="Aktif" <?php include 'cek_login.php'; if($data_lama['status']=='Aktif') echo 'selected'; ?>>Aktif</option>
                            <option value="Cuti" <?php include 'cek_login.php'; if($data_lama['status']=='Cuti') echo 'selected'; ?>>Cuti</option>
                        </select>
                    </div>
                    <button type="submit" name="update" class="btn btn-warning">Simpan Perubahan</button>
                    <a href="data_mekanik.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
