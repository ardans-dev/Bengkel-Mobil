<?php include 'cek_login.php';
include 'koneksi.php';

if (isset($_POST['simpan'])) {
    $nama_sparepart = $_POST['nama_sparepart'];
    $stok           = $_POST['stok'];
    // Menghapus titik atau koma jika user salah ketik format uang
    $harga          = str_replace(['.', ','], '', $_POST['harga']); 

    $query = "INSERT INTO sparepart (nama_sparepart, stok, harga) VALUES ('$nama_sparepart', '$stok', '$harga')";
    $insert = mysqli_query($koneksi, $query);

    if ($insert) {
        header("Location: data_sparepart.php");
        exit;
    } else {
        echo "<script>alert('Gagal menambahkan data sparepart!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Sparepart - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'cek_login.php'; include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Form Tambah Sparepart</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Barang / Suku Cadang</label>
                                <input type="text" class="form-control" name="nama_sparepart" required placeholder="Contoh: Kampas Rem Depan Avanza">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jumlah Stok Awal</label>
                                <input type="number" class="form-control" name="stok" required min="0" value="0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Harga Satuan (Rp)</label>
                                <input type="number" class="form-control" name="harga" required min="0" placeholder="Contoh: 150000">
                                <div class="form-text">Masukkan angka saja tanpa titik atau koma (contoh: 150000).</div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="data_sparepart.php" class="btn btn-secondary">Kembali</a>
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
