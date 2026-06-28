<?php
include 'cek_login.php';
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Sparepart - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Daftar Sparepart (Suku Cadang)</h2>
        <a href="tambah_sparepart.php" class="btn btn-primary mb-3">+ Tambah Sparepart Baru</a>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Stok</th>
                    <th>Harga Satuan</th>
                    <th>Terakhir Diubah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($koneksi, "SELECT * FROM sparepart ORDER BY id_sparepart DESC");
                $no = 1;
                while($data = mysqli_fetch_array($query)){
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $data['nama_sparepart']; ?></td>
                    <td>
                        <span class="badge <?php echo ($data['stok'] < 5) ? 'bg-danger' : 'bg-success'; ?>">
                            <?php echo $data['stok']; ?>
                        </span>
                    </td>
                    <td>Rp <?php echo number_format($data['harga'], 0, ',', '.'); ?></td>
                    <td><?php echo date('d-M-Y H:i', strtotime($data['terakhir_diubah'])); ?></td>
                    <td>
                        <a href="edit_sparepart.php?id=<?php echo $data['id_sparepart']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="hapus_sparepart.php?id=<?php echo $data['id_sparepart']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus barang ini?');">Hapus</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
