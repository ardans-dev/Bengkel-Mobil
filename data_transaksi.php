<?php
include 'cek_login.php';
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Transaksi - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4 px-4">
        <h2>Daftar Transaksi Servis</h2>
        <a href="tambah_transaksi.php" class="btn btn-primary mb-3">+ Pendaftaran Servis Baru</a>

        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID / Nota</th>
                    <th>Tanggal Waktu</th>
                    <th>Pelanggan & Kendaraan</th>
                    <th>Mekanik</th>
                    <th>Keluhan</th>
                    <th>Status</th>
                    <th>Total Biaya</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Multi-Join SQL untuk menarik data dari 4 tabel sekaligus
                $query = mysqli_query($koneksi, "
                    SELECT t.*, k.plat_nomor, k.merek, p.nama AS nama_pelanggan, m.nama_mekanik 
                    FROM transaksi t
                    JOIN kendaraan k ON t.id_kendaraan = k.id_kendaraan
                    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan
                    JOIN mekanik m ON t.id_mekanik = m.id_mekanik
                    ORDER BY t.id_transaksi DESC
                ");

                while($data = mysqli_fetch_array($query)){
                ?>
                <tr>
                    <td><strong>TRX-<?php echo $data['id_transaksi']; ?></strong></td>
                    <td><?php echo date('d-M-Y H:i', strtotime($data['tanggal'])); ?></td>
                    <td>
                        <?php echo $data['nama_pelanggan']; ?><br>
                        <span class="badge bg-secondary"><?php echo $data['plat_nomor']; ?> (<?php echo $data['merek']; ?>)</span>
                    </td>
                    <td><?php echo $data['nama_mekanik']; ?></td>
                    <td><?php echo $data['keluhan']; ?></td>
                    <td>
                        <?php
                        // Logika warna status
                        if($data['status_servis'] == 'Menunggu') $warna = 'bg-danger';
                        elseif($data['status_servis'] == 'Proses') $warna = 'bg-warning text-dark';
                        else $warna = 'bg-success';
                        ?>
                        <span class="badge <?php echo $warna; ?>"><?php echo $data['status_servis']; ?></span>
                    </td>
                    <td><strong>Rp <?php echo number_format($data['total_biaya'], 0, ',', '.'); ?></strong></td>
                    <td>
                        <a href="detail_transaksi.php?id=<?php echo $data['id_transaksi']; ?>" class="btn btn-sm btn-info text-white">Detail & Bayar</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
