<?php
/**
 * =====================================================================
 * FILE: pelanggan.php
 * DESKRIPSI: Modul Master Data Pelanggan (CRUD Lengkap Terpusat)
 * =====================================================================
 * 
 * ARSITEKTUR KODE:
 * File tunggal terpusat untuk kelola kontak pelanggan, WhatsApp direct link,
 * dan jumlah unit mobil yang dimiliki pelanggan di bengkel.
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Data Pelanggan";

// =====================================================================
// 1. PROSES POST (TAMBAH & EDIT PELANGGAN)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $nama       = trim($_POST['nama'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $alamat     = trim($_POST['alamat'] ?? '');

    // Validasi sederhana
    if (empty($nama) || empty($no_telepon)) {
        set_flash('danger', 'Nama dan Nomor Telepon wajib diisi!');
        redirect('pelanggan.php');
    }

    if ($action === 'tambah') {
        $stmt = db()->prepare("INSERT INTO pelanggan (nama, no_telepon, alamat) VALUES (?, ?, ?)");
        $stmt->execute([$nama, $no_telepon, $alamat]);

        set_flash('success', "Pelanggan baru <b>" . e($nama) . "</b> berhasil ditambahkan!");
        redirect('pelanggan.php');

    } elseif ($action === 'edit') {
        $id_pelanggan = (int)($_POST['id_pelanggan'] ?? 0);

        if ($id_pelanggan <= 0) {
            set_flash('danger', 'ID Pelanggan tidak valid.');
            redirect('pelanggan.php');
        }

        $stmt = db()->prepare("UPDATE pelanggan SET nama = ?, no_telepon = ?, alamat = ? WHERE id_pelanggan = ?");
        $stmt->execute([$nama, $no_telepon, $alamat, $id_pelanggan]);

        set_flash('success', "Data pelanggan <b>" . e($nama) . "</b> berhasil diperbarui!");
        redirect('pelanggan.php');
    }
}

// =====================================================================
// 2. PROSES GET (HAPUS PELANGGAN)
// =====================================================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id_hapus = (int)($_GET['id'] ?? 0);

    if ($id_hapus > 0) {
        try {
            $stmt = db()->prepare("DELETE FROM pelanggan WHERE id_pelanggan = ?");
            $stmt->execute([$id_hapus]);

            set_flash('success', 'Data pelanggan dan unit kendaraan terkait berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal menghapus: Pelanggan ini memiliki riwayat nota transaksi servis aktif.');
        }
    }
    redirect('pelanggan.php');
}

// =====================================================================
// 3. AMBIL DATA PELANGGAN
// =====================================================================
$query = "
    SELECT p.*, COUNT(k.id_kendaraan) AS jumlah_mobil
    FROM pelanggan p
    LEFT JOIN kendaraan k ON p.id_pelanggan = k.id_pelanggan
    GROUP BY p.id_pelanggan
    ORDER BY p.id_pelanggan DESC
";
$pelanggan_list = db()->query($query)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Header Standar Terpadu -->
<div class="page-header-box">
    <div>
        <ul class="breadcrumb-custom">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li>/</li>
            <li><span>Data Master</span></li>
            <li>/</li>
            <li class="active">Pelanggan</li>
        </ul>
        <h1 class="page-title mb-1">Data Pelanggan</h1>
        <p class="page-subtitle mb-0">Kelola buku kontak pelanggan tetap, riwayat kepemilikan mobil, dan nomor WhatsApp.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-person-plus-fill me-1"></i> + Tambah Pelanggan
        </button>
    </div>
</div>

<!-- Kartu Tabel Data Pelanggan -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-people-fill text-primary"></i>
            <span class="card-title mb-0">Daftar Pelanggan Terdaftar (<?= count($pelanggan_list) ?> Orang)</span>
        </div>
        <input type="text" id="filterInput" class="form-control form-control-sm table-search-input" placeholder="Cari nama atau telepon..." onkeyup="filterTabel()">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle" id="tablePelanggan">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>Nama Pelanggan</th>
                        <th>Kontak WhatsApp / HP</th>
                        <th>Alamat Domisili</th>
                        <th class="text-center">Mobil Terdaftar</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pelanggan_list)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada data pelanggan yang terdaftar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($pelanggan_list as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($row['nama']) ?></div>
                                    <small class="text-muted">ID: #<?= (int)$row['id_pelanggan'] ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $wa_num = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $row['no_telepon']));
                                    ?>
                                    <a href="https://wa.me/<?= e($wa_num) ?>" target="_blank" class="text-decoration-none text-success fw-medium small">
                                        <i class="bi bi-whatsapp me-1"></i><?= e($row['no_telepon']) ?>
                                    </a>
                                </td>
                                <td class="text-muted small"><?= e($row['alamat'] ?: '-') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-car-front-fill text-primary me-1"></i><?= (int)$row['jumlah_mobil'] ?> Unit
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                onclick="editPelanggan(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)" 
                                                title="Edit Data">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="pelanggan.php?action=hapus&id=<?= (int)$row['id_pelanggan'] ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('Peringatan: Menghapus pelanggan ini juga akan menghapus data unit kendaraannya. Lanjutkan?');" 
                                           title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH PELANGGAN -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="pelanggan.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="tambah">
            <div class="modal-header">
                <h5 class="modal-title card-title mb-0"><i class="bi bi-person-plus text-primary me-2"></i>Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                    <input type="text" name="no_telepon" class="form-control" placeholder="Contoh: 081234567890" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat Domisili</label>
                    <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat lengkap pelanggan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Pelanggan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT PELANGGAN -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form action="pelanggan.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_pelanggan" id="edit_id_pelanggan">
            <div class="modal-header">
                <h5 class="modal-title card-title mb-0"><i class="bi bi-pencil-square text-secondary me-2"></i>Edit Data Pelanggan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                    <input type="text" name="no_telepon" id="edit_no_telepon" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat Domisili</label>
                    <textarea name="alamat" id="edit_alamat" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPelanggan(data) {
    document.getElementById('edit_id_pelanggan').value = data.id_pelanggan;
    document.getElementById('edit_nama').value = data.nama;
    document.getElementById('edit_no_telepon').value = data.no_telepon;
    document.getElementById('edit_alamat').value = data.alamat || '';
    
    var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}

function filterTabel() {
    const filter = document.getElementById("filterInput").value.toUpperCase();
    const rows = document.querySelectorAll("#tablePelanggan tbody tr");

    rows.forEach(row => {
        const text = row.textContent || row.innerText;
        if (text.toUpperCase().indexOf(filter) > -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
