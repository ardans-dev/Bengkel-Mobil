<?php include 'cek_login.php';
include 'koneksi.php';

// Menangkap ID dari URL
$id = $_GET['id'];

// Mengambil data pelanggan berdasarkan ID
$query_data = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE id_pelanggan='$id'");
$data_lama = mysqli_fetch_array($query_data);

// Jika tombol update diklik
if (isset($_POST['update'])) {
    $nama       = $_POST['nama'];
    $no_telepon = $_POST['no_telepon'];
    $alamat     = $_POST['alamat'];

    // Query untuk update data
    $update = mysqli_query($koneksi, "UPDATE pelanggan SET nama='$nama', no_telepon='$no_telepon', alamat='$alamat' WHERE id_pelanggan='$id'");

    if ($update) {
        header("Location: data_pelanggan.php");
        exit;
    } else {
        echo "<script>alert('Gagal mengubah data pelanggan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pelanggan - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'cek_login.php'; include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">Edit Data Pelanggan</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama" value="<?php include 'cek_login.php'; echo $data_lama['nama']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" class="form-control" name="no_telepon" value="<?php include 'cek_login.php'; echo $data_lama['no_telepon']; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" name="alamat" rows="3"><?php include 'cek_login.php'; echo $data_lama['alamat']; ?></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="data_pelanggan.php" class="btn btn-secondary">Batal</a>
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
