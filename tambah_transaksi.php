<?php include 'cek_login.php';
include 'koneksi.php';

// Jika tombol simpan diklik
if (isset($_POST['simpan'])) {
    $id_kendaraan = $_POST['id_kendaraan'];
    $id_mekanik   = $_POST['id_mekanik'];
    $keluhan      = $_POST['keluhan'];

    // Query INSERT. Tanggal otomatis terisi oleh MySQL, status awal default 'Menunggu', total_biaya default 0
    $query = "INSERT INTO transaksi (id_kendaraan, id_mekanik, keluhan) VALUES ('$id_kendaraan', '$id_mekanik', '$keluhan')";
    $insert = mysqli_query($koneksi, $query);

    if ($insert) {
        header("Location: data_transaksi.php");
        exit;
    } else {
        echo "<script>alert('Gagal menambahkan data transaksi!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pendaftaran Servis - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Bengkel Budi</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="data_pelanggan.php">Data Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link" href="data_kendaraan.php">Data Kendaraan</a></li>
                    <li class="nav-item"><a class="nav-link" href="data_sparepart.php">Data Sparepart</a></li>
                    <li class="nav-item"><a class="nav-link" href="data_mekanik.php">Data Mekanik</a></li>
                    <li class="nav-item"><a class="nav-link" href="data_layanan.php">Data Layanan</a></li>
                    <li class="nav-item"><a class="nav-link active" href="data_transaksi.php">Transaksi</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Form Pendaftaran Servis Masuk</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            
                            <!-- Pilih Kendaraan (Menampilkan Plat dan Nama Pemilik) -->
                            <div class="mb-3">
                                <label class="form-label">Pilih Kendaraan Pelanggan</label>
                                <select class="form-select" name="id_kendaraan" required>
                                    <option value="">-- Cari Plat Nomor / Kendaraan --</option>
                                    <?php include 'cek_login.php';
                                    // Menggabungkan tabel kendaraan dan pelanggan agar informasinya lengkap
                                    $q_kendaraan = mysqli_query($koneksi, "
                                        SELECT k.id_kendaraan, k.plat_nomor, k.merek, p.nama 
                                        FROM kendaraan k 
                                        JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan 
                                        ORDER BY p.nama ASC
                                    ");
                                    while($row = mysqli_fetch_array($q_kendaraan)){
                                        echo "<option value='".$row['id_kendaraan']."'>".$row['plat_nomor']." - ".$row['merek']." (Milik: ".$row['nama'].")</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Pilih Mekanik (Hanya yang berstatus Aktif) -->
                            <div class="mb-3">
                                <label class="form-label">Mekanik yang Menangani</label>
                                <select class="form-select" name="id_mekanik" required>
                                    <option value="">-- Pilih Mekanik --</option>
                                    <?php include 'cek_login.php';
                                    // Memfilter hanya mekanik yang berstatus 'Aktif'
                                    $q_mekanik = mysqli_query($koneksi, "SELECT id_mekanik, nama_mekanik FROM mekanik WHERE status='Aktif' ORDER BY nama_mekanik ASC");
                                    while($row = mysqli_fetch_array($q_mekanik)){
                                        echo "<option value='".$row['id_mekanik']."'>".$row['nama_mekanik']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Keluhan Pelanggan -->
                            <div class="mb-3">
                                <label class="form-label">Keluhan / Catatan Awal</label>
                                <textarea class="form-control" name="keluhan" rows="3" required placeholder="Contoh: Rem blong, ganti oli rutin, mesin bunyi kletek-kletek"></textarea>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="data_transaksi.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" name="simpan" class="btn btn-success px-4">Daftarkan Servis</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
