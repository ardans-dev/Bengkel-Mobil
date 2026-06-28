<?php
include 'cek_login.php';
include 'koneksi.php';

// Menangkap ID Transaksi dari URL
$id_trx = $_GET['id'];

// ==========================================
// 1. PROSES UPDATE STATUS SERVIS
// ==========================================
if (isset($_POST['update_status'])) {
    $status_baru = $_POST['status_servis'];
    mysqli_query($koneksi, "UPDATE transaksi SET status_servis='$status_baru' WHERE id_transaksi='$id_trx'");
    header("Location: detail_transaksi.php?id=$id_trx");
    exit;
}

// ==========================================
// 2. PROSES TAMBAH JASA LAYANAN
// ==========================================
if (isset($_POST['tambah_layanan'])) {
    $id_layanan = $_POST['id_layanan'];
    
    // Ambil harga jasa dari tabel layanan
    $q_harga = mysqli_query($koneksi, "SELECT biaya_jasa FROM layanan WHERE id_layanan='$id_layanan'");
    $dt_harga = mysqli_fetch_assoc($q_harga);
    $subtotal = $dt_harga['biaya_jasa'];

    // Simpan ke detail_transaksi_layanan
    mysqli_query($koneksi, "INSERT INTO detail_transaksi_layanan (id_transaksi, id_layanan, subtotal) VALUES ('$id_trx', '$id_layanan', '$subtotal')");
    
    // Update total biaya di tabel transaksi utama
    mysqli_query($koneksi, "UPDATE transaksi SET total_biaya = total_biaya + $subtotal WHERE id_transaksi='$id_trx'");
    
    header("Location: detail_transaksi.php?id=$id_trx");
    exit;
}

// ==========================================
// 3. PROSES TAMBAH SPAREPART (SUKU CADANG)
// ==========================================
if (isset($_POST['tambah_sparepart'])) {
    $id_sparepart = $_POST['id_sparepart'];
    $jumlah       = $_POST['jumlah'];
    
    // Ambil harga dan stok dari tabel sparepart
    $q_sp = mysqli_query($koneksi, "SELECT harga, stok FROM sparepart WHERE id_sparepart='$id_sparepart'");
    $dt_sp = mysqli_fetch_assoc($q_sp);
    
    // Cek apakah stok mencukupi
    if ($dt_sp['stok'] >= $jumlah) {
        $subtotal = $dt_sp['harga'] * $jumlah;
        
        // Simpan ke detail_transaksi_sparepart
        mysqli_query($koneksi, "INSERT INTO detail_transaksi_sparepart (id_transaksi, id_sparepart, jumlah, subtotal) VALUES ('$id_trx', '$id_sparepart', '$jumlah', '$subtotal')");
        
        // Kurangi stok di tabel sparepart
        mysqli_query($koneksi, "UPDATE sparepart SET stok = stok - $jumlah WHERE id_sparepart='$id_sparepart'");
        
        // Update total biaya di tabel transaksi utama
        mysqli_query($koneksi, "UPDATE transaksi SET total_biaya = total_biaya + $subtotal WHERE id_transaksi='$id_trx'");
        
        header("Location: detail_transaksi.php?id=$id_trx");
        exit;
    } else {
        echo "<script>alert('Gagal! Stok sparepart tidak mencukupi.');</script>";
    }
}

// ==========================================
// AMBIL DATA UTAMA TRANSAKSI UNTUK DITAMPILKAN
// ==========================================
$query_trx = mysqli_query($koneksi, "
    SELECT t.*, k.plat_nomor, k.merek, p.nama AS nama_pelanggan, m.nama_mekanik 
    FROM transaksi t
    JOIN kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan
    JOIN mekanik m ON t.id_mekanik = m.id_mekanik
    WHERE t.id_transaksi = '$id_trx'
");
$trx = mysqli_fetch_assoc($query_trx);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Transaksi - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
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

    <div class="container">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Nota Servis: TRX-<?php echo $trx['id_transaksi']; ?></h5>
                <span class="badge bg-light text-dark fs-6"><?php echo date('d M Y, H:i', strtotime($trx['tanggal'])); ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Pelanggan:</strong> <?php echo $trx['nama_pelanggan']; ?></p>
                        <p class="mb-1"><strong>Kendaraan:</strong> <?php echo $trx['plat_nomor']; ?> (<?php echo $trx['merek']; ?>)</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Mekanik:</strong> <?php echo $trx['nama_mekanik']; ?></p>
                        <p class="mb-1"><strong>Keluhan:</strong> <?php echo $trx['keluhan']; ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <h4 class="text-danger fw-bold mb-2">Total: Rp <?php echo number_format($trx['total_biaya'], 0, ',', '.'); ?></h4>

                        <form action="" method="POST" class="d-flex justify-content-end align-items-center">
                            <select name="status_servis" class="form-select form-select-sm w-auto me-2">
                                <option value="Menunggu" <?php if($trx['status_servis']=='Menunggu') echo 'selected'; ?>>Menunggu</option>
                                <option value="Proses" <?php if($trx['status_servis']=='Proses') echo 'selected'; ?>>Proses</option>
                                <option value="Selesai" <?php if($trx['status_servis']=='Selesai') echo 'selected'; ?>>Selesai</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-sm btn-outline-primary">Update Status</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">Pekerjaan / Jasa Servis</h6>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" class="d-flex mb-3">
                            <select name="id_layanan" class="form-select me-2" required>
                                <option value="">-- Pilih Jasa --</option>
                                <?php
                                $q_lyn = mysqli_query($koneksi, "SELECT * FROM layanan ORDER BY nama_layanan ASC");
                                while($l = mysqli_fetch_assoc($q_lyn)){
                                    echo "<option value='".$l['id_layanan']."'>".$l['nama_layanan']." (Rp ".number_format($l['biaya_jasa'],0,',','.').")</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" name="tambah_layanan" class="btn btn-success">Tambah</button>
                        </form>

                        <table class="table table-sm table-bordered mt-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Jasa</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-center" style="width: 50px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q_dt_lyn = mysqli_query($koneksi, "
                                    SELECT dl.*, l.nama_layanan 
                                    FROM detail_transaksi_layanan dl
                                    JOIN layanan l ON dl.id_layanan = l.id_layanan
                                    WHERE dl.id_transaksi = '$id_trx'
                                ");
                                while($dtl = mysqli_fetch_assoc($q_dt_lyn)){
                                ?>
                                <tr>
                                    <td><?php echo $dtl['nama_layanan']; ?></td>
                                    <td class="text-end">Rp <?php echo number_format($dtl['subtotal'], 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <a href="hapus_detail_layanan.php?id_detail=<?php echo $dtl['id_detail_lyn']; ?>&id_trx=<?php echo $id_trx; ?>" class="btn btn-sm btn-danger py-0" onclick="return confirm('Hapus jasa ini?');">X</a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">Penggunaan Sparepart</h6>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" class="d-flex mb-3">
                            <select name="id_sparepart" class="form-select me-2" required>
                                <option value="">-- Pilih Barang --</option>
                                <?php
                                $q_sp_list = mysqli_query($koneksi, "SELECT * FROM sparepart WHERE stok > 0 ORDER BY nama_sparepart ASC");
                                while($sp = mysqli_fetch_assoc($q_sp_list)){
                                    echo "<option value='".$sp['id_sparepart']."'>".$sp['nama_sparepart']." (Stok: ".$sp['stok'].")</option>";
                                }
                                ?>
                            </select>
                            <input type="number" name="jumlah" class="form-control me-2" style="width: 80px;" value="1" min="1" required>
                            <button type="submit" name="tambah_sparepart" class="btn btn-warning">Tambah</button>
                        </form>

                        <table class="table table-sm table-bordered mt-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Qty</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-center" style="width: 50px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q_dt_sp = mysqli_query($koneksi, "
                                    SELECT ds.*, s.nama_sparepart 
                                    FROM detail_transaksi_sparepart ds
                                    JOIN sparepart s ON ds.id_sparepart = s.id_sparepart
                                    WHERE ds.id_transaksi = '$id_trx'
                                ");
                                while($dtsp = mysqli_fetch_assoc($q_dt_sp)){
                                ?>
                                <tr>
                                    <td><?php echo $dtsp['nama_sparepart']; ?></td>
                                    <td><?php echo $dtsp['jumlah']; ?></td>
                                    <td class="text-end">Rp <?php echo number_format($dtsp['subtotal'], 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <a href="hapus_detail_sparepart.php?id_detail=<?php echo $dtsp['id_detail_sp']; ?>&id_trx=<?php echo $id_trx; ?>" class="btn btn-sm btn-danger py-0" onclick="return confirm('Hapus barang ini? Stok akan dikembalikan.');">X</a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-5 d-flex justify-content-between align-items-center">
            <a href="data_transaksi.php" class="btn btn-secondary">< Kembali ke Daftar Transaksi</a>

            <a href="cetak_nota.php?id=<?php echo $id_trx; ?>" target="_blank" class="btn btn-dark px-4 fw-bold">
                🖨️ Cetak Nota Servis
            </a>
        </div>
    </div>
</body>
</html>
