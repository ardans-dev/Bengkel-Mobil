<?php
include 'cek_login.php';
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mekanik - Bengkel Budi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Daftar Mekanik</h2>
        <a href="tambah_mekanik.php" class="btn btn-primary mb-3">+ Tambah Mekanik Baru</a>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama Mekanik</th>
                    <th>Alamat</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($koneksi, "SELECT * FROM mekanik ORDER BY id_mekanik DESC");
                $no = 1;
                while($data = mysqli_fetch_array($query)){
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $data['nama_mekanik']; ?></td>
                    <td><?php echo $data['alamat']; ?></td>
                    <td>
                        <span class="badge <?php echo ($data['status'] == 'Aktif') ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $data['status']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_mekanik.php?id=<?php echo $data['id_mekanik']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="hapus_mekanik.php?id=<?php echo $data['id_mekanik']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?');">Hapus</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>