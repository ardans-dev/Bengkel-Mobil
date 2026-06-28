<?php
include 'cek_login.php';
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelanggan - Bengkel Web</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Daftar Pelanggan</h2>
        <a href="tambah_pelanggan.php" class="btn btn-primary mb-3">+ Tambah Pelanggan Baru</a>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama Pelanggan</th>
                    <th>No. Telepon</th>
                    <th>Alamat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query untuk mengambil semua data dari tabel pelanggan
                $query = mysqli_query($koneksi, "SELECT * FROM pelanggan ORDER BY id_pelanggan DESC");
                $no = 1;
                
                // Melakukan perulangan (looping) untuk setiap baris data
                while($data = mysqli_fetch_array($query)){
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $data['nama']; ?></td>
                    <td><?php echo $data['no_telepon']; ?></td>
                    <td><?php echo $data['alamat']; ?></td>
                    <td>
                        <a href="edit_pelanggan.php?id=<?php echo $data['id_pelanggan']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="hapus_pelanggan.php?id=<?php echo $data['id_pelanggan']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah kamu yakin ingin menghapus data pelanggan ini?');">Hapus</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
