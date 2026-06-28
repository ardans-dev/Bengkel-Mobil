<?php include 'cek_login.php';
include 'koneksi.php';

if (isset($_POST['simpan'])) {
    $id_pelanggan = $_POST['id_pelanggan'];
    $plat_nomor   = strtoupper($_POST['plat_nomor']); // Otomatis huruf kapital
    $merek        = $_POST['merek'];
    $tipe         = $_POST['tipe'];

    $query = "INSERT INTO kendaraan (id_pelanggan, plat_nomor, merek, tipe) VALUES ('$id_pelanggan', '$plat_nomor', '$merek', '$tipe')";
    $insert = mysqli_query($koneksi, $query);

    if ($insert) {
        header("Location: data_kendaraan.php");
        exit;
    } else {
        echo "<script>alert('Gagal menambahkan data kendaraan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kendaraan - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'cek_login.php'; include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Form Tambah Kendaraan</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Pilih Pemilik (Pelanggan)</label>
                                <select class="form-select" name="id_pelanggan" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    <?php include 'cek_login.php';
                                    // Mengambil data pelanggan untuk dropdown
                                    $q_pelanggan = mysqli_query($koneksi, "SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama ASC");
                                    while($row = mysqli_fetch_array($q_pelanggan)){
                                        echo "<option value='".$row['id_pelanggan']."'>".$row['nama']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Plat Nomor</label>
                                <input type="text" class="form-control" name="plat_nomor" required placeholder="Contoh: AD 1234 XY">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Merek Mobil</label>
                                <input type="text" class="form-control" name="merek" required placeholder="Contoh: Toyota, Honda">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipe Mobil</label>
                                <input type="text" class="form-control" name="tipe" placeholder="Contoh: Avanza, Brio">
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="data_kendaraan.php" class="btn btn-secondary">Kembali</a>
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
