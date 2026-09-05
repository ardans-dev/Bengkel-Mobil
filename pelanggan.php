<?php
/**
 * =====================================================================
 * FILE: pelanggan.php
 * DESKRIPSI: Modul Tunggal Manajemen Data Pelanggan (CRUD Lengkap)
 * =====================================================================
 * 
 * ARSITEKTUR KODE:
 * File ini menggantikan 4 file lama (data_pelanggan, tambah_pelanggan, 
 * edit_pelanggan, dan hapus_pelanggan) menjadi 1 file terpusat.
 * 
 * ALUR PROSES:
 * 1. Jika ada aksi POST -> Lakukan INSERT (tambah) atau UPDATE (edit) via Prepared Statement.
 * 2. Jika ada aksi GET hapus -> Lakukan DELETE via Prepared Statement.
 * 3. Di bagian bawah -> Render tabel data pelanggan dan modal form interaktif.
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
        // Query INSERT aman dengan Prepared Statement
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

        // Query UPDATE aman dengan Prepared Statement
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
            // Karena di tabel kendaraan ada foreign key ON DELETE CASCADE,
            // saat pelanggan dihapus, mobil-mobil miliknya otomatis ikut terhapus bersih.
            $stmt = db()->prepare("DELETE FROM pelanggan WHERE id_pelanggan = ?");
            $stmt->execute([$id_hapus]);

            set_flash('success', 'Data pelanggan dan kendaraan terkait berhasil dihapus.');
        } catch (PDOException $e) {
            // Tangkap jika pelanggan masih terkait dengan riwayat transaksi bengkel
            set_flash('danger', 'Gagal menghapus: Pelanggan ini memiliki riwayat servis yang terdaftar.');
        }
    }
    redirect('pelanggan.php');
}

// =====================================================================
// 3. AMBIL DATA PELANGGAN UNTUK DITAMPILKAN DI TABEL
// =====================================================================
// Query ini juga menghitung berapa jumlah mobil yang dimiliki masing-masing pelanggan (LEFT JOIN)
$query = "
    SELECT p.*, COUNT(k.id_kendaraan) AS jumlah_mobil
    FROM pelanggan p
    LEFT JOIN kendaraan k ON p.id_pelanggan = k.id_pelanggan
    GROUP BY p.id_pelanggan
    ORDER BY p.id_pelanggan DESC
";
$pelanggan_list = db()->query($query)->fetchAll();

// Jika ada permintaan edit data via modal/query parameter
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $stmt_edit = db()->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ?");
    $stmt_edit->execute([(int)$_GET['edit_id']]);
    $edit_data = $stmt_edit->fetch();
}

include __DIR__ . '/includes/header.php';
?>

<!-- Header Halaman & Tombol Aksi -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-people text-primary me-2"></i>Data Pelanggan</h3>
        <p class="text-muted small mb-0">Manajemen direktori pelanggan tetap dan kontak servis.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-person-plus me-1"></i> Tambah Pelanggan
    </button>
</div>

<!-- =====================================================================
     TABEL DATA PELANGGAN
     ===================================================================== -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 60px;">#</th>
                        <th>Nama Pelanggan</th>
                        <th>No. Telepon / WhatsApp</th>
                        <th>Alamat Domisili</th>
                        <th class="text-center">Kendaraan</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pelanggan_list)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada data pelanggan yang terdaftar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($pelanggan_list as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td class="fw-semibold text-dark">
                                    <i class="bi bi-person-circle text-primary me-2"></i><?= e($row['nama']) ?>
                                </td>
                                <td>
                                    <?php 
                                    // Bersihkan nomor telepon untuk link WhatsApp direct chat
                                    $wa_num = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $row['no_telepon']));
                                    ?>
                                    <a href="https://wa.me/<?= e($wa_num) ?>" target="_blank" class="text-decoration-none text-success small fw-medium">
                                        <i class="bi bi-whatsapp me-1"></i><?= e($row['no_telepon']) ?>
                                    </a>
                                </td>
                                <td class="text-muted small"><?= e($row['alamat'] ?: '-') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-car-front text-primary me-1"></i><?= (int)$row['jumlah_mobil'] ?> Unit
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <!-- Tombol Edit (Membuka Modal) -->
                                    <button class="btn btn-outline-secondary btn-sm me-1" 
                                            onclick="editPelanggan(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <!-- Tombol Hapus dengan Konfirmasi -->
                                    <a href="pelanggan.php?action=hapus&id=<?= (int)$row['id_pelanggan'] ?>" 
                                       class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('Peringatan: Menghapus pelanggan ini juga akan menghapus data kendaraan miliknya. Lanjutkan?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- =====================================================================
     MODAL TAMBAH PELANGGAN BARU
     ===================================================================== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="pelanggan.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="tambah">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                    <input type="text" name="no_telepon" class="form-control" placeholder="Contoh: 081234567890" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat Domisili</label>
                    <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat lengkap pelanggan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-save me-1"></i> Simpan Pelanggan</button>
            </div>
        </form>
    </div>
</div>

<!-- =====================================================================
     MODAL EDIT PELANGGAN
     ===================================================================== -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form action="pelanggan.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_pelanggan" id="edit_id_pelanggan">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-secondary me-2"></i>Edit Data Pelanggan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                    <input type="text" name="no_telepon" id="edit_no_telepon" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat Domisili</label>
                    <textarea name="alamat" id="edit_alamat" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-check2-circle me-1"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Fungsi JavaScript Vanilla untuk mengisi form modal edit secara instan
function editPelanggan(data) {
    document.getElementById('edit_id_pelanggan').value = data.id_pelanggan;
    document.getElementById('edit_nama').value = data.nama;
    document.getElementById('edit_no_telepon').value = data.no_telepon;
    document.getElementById('edit_alamat').value = data.alamat || '';
    
    // Tampilkan modal Bootstrap
    var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
