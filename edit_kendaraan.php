<?php
include 'cek_login.php';
include 'koneksi.php';

// Menangkap ID kendaraan dari URL
$id = $_GET['id'];

// Mengambil data kendaraan yang akan diedit
$query_kendaraan = mysqli_query($koneksi, "SELECT * FROM kendaraan WHERE id_kendaraan='$id'");
$data_lama = mysqli_fetch_array($query_kendaraan);

// Jika tombol simpan perubahan diklik
if (isset($_POST['update'])) {
    $id_pelanggan = $_POST['id_pelanggan'];
    $plat_nomor   = strtoupper($_POST['plat_nomor']); // Mengubah ke huruf kapital
    $merek        = $_POST['merek'];
    $tipe         = $_POST['tipe'];

    // Query untuk memperbarui data kendaraan
    $update = mysqli_query($koneksi, "UPDATE kendaraan SET id_pelanggan='$id_pelanggan', plat_nomor='$plat_nomor', merek='$merek', tipe='$tipe' WHERE id_kendaraan='$id'");

    if ($update) {
        header("Location: data_kendaraan.php");
        exit;
    } else {
        echo "<script>alert('Gagal mengubah data kendaraan!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kendaraan - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">Edit Data Kendaraan</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Pilih Pemilik (Pelanggan)</label>
                                <select class="form-select" name="id_pelanggan" required>
                                    <?php
                                    // Ambil semua daftar pelanggan untuk opsi pilihan
                                    $q_pelanggan = mysqli_query($koneksi, "SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama ASC");
                                    while($row = mysqli_fetch_array($q_pelanggan)){
                                        // Logika agar pemilik asli mobil ini otomatis langsung terpilih (selected)
                                        $selected = ($row['id_pelanggan'] == $data_lama['id_pelanggan']) ? 'selected' : '';
                                        echo "<option value='".$row['id_pelanggan']."' $selected>".$row['nama']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Plat Nomor</label>
                                <input type="text" class="form-control" name="plat_nomor" value="<?php echo $data_lama['plat_nomor']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Merek Mobil</label>
                                <input type="text" class="form-control" name="merek" value="<?php echo $data_lama['merek']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipe Mobil</label>
                                <input type="text" class="form-control" name="tipe" value="<?php echo $data_lama['tipe']; ?>">
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="data_kendaraan.php" class="btn btn-secondary">Batal</a>
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
