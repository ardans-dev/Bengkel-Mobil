<?php
include 'cek_login.php';
include 'koneksi.php';

// 1. Menghitung Total Pendapatan (Hanya dari servis yang 'Selesai')
$q_pendapatan = mysqli_query($koneksi, "SELECT SUM(total_biaya) AS total FROM transaksi WHERE status_servis = 'Selesai'");
$dt_pendapatan = mysqli_fetch_assoc($q_pendapatan);
$pendapatan = $dt_pendapatan['total'] ? $dt_pendapatan['total'] : 0;

// 2. Menghitung Kendaraan yang Sedang Diservis (Menunggu / Proses)
$q_servis = mysqli_query($koneksi, "SELECT COUNT(id_transaksi) AS jml_servis FROM transaksi WHERE status_servis != 'Selesai'");
$dt_servis = mysqli_fetch_assoc($q_servis);
$servis_berjalan = $dt_servis['jml_servis'];

// 3. Menghitung Sparepart dengan Stok Menipis (< 5)
$q_stok = mysqli_query($koneksi, "SELECT COUNT(id_sparepart) AS stok_kritis FROM sparepart WHERE stok < 5");
$dt_stok = mysqli_fetch_assoc($q_stok);
$stok_menipis = $dt_stok['stok_kritis'];

// 4. Menghitung Mekanik yang Aktif
$q_mekanik = mysqli_query($koneksi, "SELECT COUNT(id_mekanik) AS mekanik_aktif FROM mekanik WHERE status = 'Aktif'");
$dt_mekanik = mysqli_fetch_assoc($q_mekanik);
$mekanik_aktif = $dt_mekanik['mekanik_aktif'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bengkel Ardans</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Dashboard Bengkel Mobil</h2>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-success shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title">Total Pendapatan</h6>
                        <h3 class="mb-0">Rp <?php echo number_format($pendapatan, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card text-white bg-primary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title">Servis Berjalan</h6>
                        <h3 class="mb-0"><?php echo $servis_berjalan; ?> Kendaraan</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card text-white bg-danger shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title">Stok Menipis (< 5)</h6>
                        <h3 class="mb-0"><?php echo $stok_menipis; ?> Barang</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card text-dark bg-warning shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title">Mekanik Aktif</h6>
                        <h3 class="mb-0"><?php echo $mekanik_aktif; ?> Orang</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-2">
            <div class="card-header bg-white fw-bold">
                5 Transaksi Servis Terakhir
            </div>
            <div class="card-body">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nota</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q_recent = mysqli_query($koneksi, "
                            SELECT t.id_transaksi, t.tanggal, t.status_servis, t.total_biaya, p.nama 
                            FROM transaksi t
                            JOIN kendaraan k ON t.id_kendaraan = k.id_kendaraan
                            JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan
                            ORDER BY t.id_transaksi DESC LIMIT 5
                        ");
                        while($row = mysqli_fetch_assoc($q_recent)){
                        ?>
                        <tr>
                            <td><strong>TRX-<?php echo $row['id_transaksi']; ?></strong></td>
                            <td><?php echo date('d M Y, H:i', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo $row['nama']; ?></td>
                            <td>
                                <?php 
                                if($row['status_servis'] == 'Menunggu') $badge = 'bg-danger';
                                elseif($row['status_servis'] == 'Proses') $badge = 'bg-warning text-dark';
                                else $badge = 'bg-success';
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo $row['status_servis']; ?></span>
                            </td>
                            <td>Rp <?php echo number_format($row['total_biaya'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>