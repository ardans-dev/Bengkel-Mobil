<?php
include 'cek_login.php';
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Layanan - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Daftar Layanan Jasa</h2>
        <a href="tambah_layanan.php" class="btn btn-primary mb-3">+ Tambah Layanan Baru</a>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama Layanan</th>
                    <th>Biaya Jasa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($koneksi, "SELECT * FROM layanan ORDER BY id_layanan DESC");
                $no = 1;
                while($data = mysqli_fetch_array($query)){
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $data['nama_layanan']; ?></td>
                    <td>Rp <?php echo number_format($data['biaya_jasa'], 0, ',', '.'); ?></td>
                    <td>
                        <a href="edit_layanan.php?id=<?php echo $data['id_layanan']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="hapus_layanan.php?id=<?php echo $data['id_layanan']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus layanan ini?');">Hapus</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>