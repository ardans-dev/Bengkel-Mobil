<?php
include 'cek_login.php';
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kendaraan - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Daftar Kendaraan</h2>
        <a href="tambah_kendaraan.php" class="btn btn-primary mb-3">+ Tambah Kendaraan Baru</a>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama Pemilik</th>
                    <th>Plat Nomor</th>
                    <th>Merek</th>
                    <th>Tipe</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query JOIN untuk mengambil nama pelanggan berdasarkan id_pelanggan di tabel kendaraan
                $query = mysqli_query($koneksi, "
                    SELECT kendaraan.*, pelanggan.nama 
                    FROM kendaraan 
                    JOIN pelanggan ON kendaraan.id_pelanggan = pelanggan.id_pelanggan 
                    ORDER BY kendaraan.id_kendaraan DESC
                ");
                $no = 1;
                while($data = mysqli_fetch_array($query)){
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $data['nama']; ?></td>
                    <td><span class="badge bg-secondary"><?php echo $data['plat_nomor']; ?></span></td>
                    <td><?php echo $data['merek']; ?></td>
                    <td><?php echo $data['tipe']; ?></td>
                    <td>
                        <a href="edit_kendaraan.php?id=<?php echo $data['id_kendaraan']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="hapus_kendaraan.php?id=<?php echo $data['id_kendaraan']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kendaraan ini?');">Hapus</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>