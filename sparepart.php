<?php
/**
 * =====================================================================
 * FILE: sparepart.php
 * DESKRIPSI: Modul Tunggal Manajemen Inventaris Suku Cadang & Kontrol Stok
 * =====================================================================
 * 
 * ARSITEKTUR KODE:
 * Menggabungkan data_sparepart, tambah_sparepart, edit_sparepart,
 * dan hapus_sparepart menjadi 1 file terpusat.
 * 
 * FITUR BISNIS:
 * 1. Peringatan Stok Menipis / Kritis (jika stok <= stok_minimum).
 * 2. Perhitungan margin keuntungan kotor (harga_jual - harga_beli).
 * 3. Validasi angka desimal untuk harga rupiah dan integer untuk stok fisik.
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Stok Sparepart";

// =====================================================================
// 1. PROSES POST (TAMBAH & EDIT SUKU CADANG)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action         = $_POST['action'] ?? '';
    $kode_sparepart = strtoupper(trim($_POST['kode_sparepart'] ?? ''));
    $nama_sparepart = trim($_POST['nama_sparepart'] ?? '');
    $kategori       = trim($_POST['kategori'] ?? 'Umum');
    $stok           = (int)($_POST['stok'] ?? 0);
    $satuan         = trim($_POST['satuan'] ?? 'Pcs');
    $harga_beli     = (float)($_POST['harga_beli'] ?? 0);
    $harga_jual     = (float)($_POST['harga_jual'] ?? 0);
    $stok_minimum   = (int)($_POST['stok_minimum'] ?? 5);

    if (empty($kode_sparepart) || empty($nama_sparepart) || $harga_jual <= 0) {
        set_flash('danger', 'Kode, Nama Barang, dan Harga Jual wajib diisi dengan benar!');
        redirect('sparepart.php');
    }

    if ($action === 'tambah') {
        try {
            $stmt = db()->prepare("
                INSERT INTO sparepart (kode_sparepart, nama_sparepart, kategori, stok, satuan, harga_beli, harga_jual, stok_minimum)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$kode_sparepart, $nama_sparepart, $kategori, $stok, $satuan, $harga_beli, $harga_jual, $stok_minimum]);

            set_flash('success', "Suku cadang <b>" . e($nama_sparepart) . "</b> berhasil ditambahkan ke inventaris!");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash('danger', "Kode sparepart <b>" . e($kode_sparepart) . "</b> sudah digunakan barang lain!");
            } else {
                set_flash('danger', 'Gagal menyimpan sparepart: ' . $e->getMessage());
            }
        }
        redirect('sparepart.php');

    } elseif ($action === 'edit') {
        $id_sparepart = (int)($_POST['id_sparepart'] ?? 0);

        if ($id_sparepart <= 0) {
            set_flash('danger', 'ID Sparepart tidak valid.');
            redirect('sparepart.php');
        }

        try {
            $stmt = db()->prepare("
                UPDATE sparepart 
                SET kode_sparepart = ?, nama_sparepart = ?, kategori = ?, stok = ?, satuan = ?, harga_beli = ?, harga_jual = ?, stok_minimum = ?
                WHERE id_sparepart = ?
            ");
            $stmt->execute([$kode_sparepart, $nama_sparepart, $kategori, $stok, $satuan, $harga_beli, $harga_jual, $stok_minimum, $id_sparepart]);

            set_flash('success', "Data suku cadang <b>" . e($nama_sparepart) . "</b> berhasil diperbarui!");
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal memperbarui sparepart: ' . $e->getMessage());
        }
        redirect('sparepart.php');
    }
}

// =====================================================================
// 2. PROSES GET (HAPUS SPAREPART)
// =====================================================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id_hapus = (int)($_GET['id'] ?? 0);

    if ($id_hapus > 0) {
        try {
            $stmt = db()->prepare("DELETE FROM sparepart WHERE id_sparepart = ?");
            $stmt->execute([$id_hapus]);
            set_flash('success', 'Barang berhasil dihapus dari inventaris.');
        } catch (PDOException $e) {
            // Jika suku cadang pernah dipakai di nota transaksi masa lalu, MySQL akan menolak hapus (RESTRICT)
            set_flash('danger', 'Gagal menghapus: Barang ini sudah tercatat dalam riwayat nota servis masa lalu.');
        }
    }
    redirect('sparepart.php');
}

// =====================================================================
// 3. AMBIL DATA SUKU CADANG DARI DATABASE
// =====================================================================
$sparepart_list = db()->query("SELECT * FROM sparepart ORDER BY nama_sparepart ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-tools text-primary me-2"></i>Inventaris Sparepart</h3>
        <p class="text-muted small mb-0">Manajemen katalog suku cadang, kontrol stok fisik gudang, dan harga jual.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-circle me-1"></i> Tambah Barang
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 60px;">#</th>
                        <th>Kode & Nama Suku Cadang</th>
                        <th>Kategori</th>
                        <th class="text-center">Sisa Stok</th>
                        <th>Harga Modal</th>
                        <th>Harga Jual</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sparepart_list)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada suku cadang di inventaris gudang.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($sparepart_list as $row): ?>
                            <?php 
                            // Deteksi apakah stok masuk kategori kritis (menipis)
                            $is_kritis = ($row['stok'] <= $row['stok_minimum']);
                            ?>
                            <tr class="<?= $is_kritis ? 'table-warning' : '' ?>">
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <span class="badge bg-secondary font-mono small me-1"><?= e($row['kode_sparepart']) ?></span>
                                    <span class="fw-semibold text-dark"><?= e($row['nama_sparepart']) ?></span>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($row['kategori']) ?></span></td>
                                <td class="text-center">
                                    <?php if ($is_kritis): ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-exclamation-triangle me-1"></i><?= (int)$row['stok'] ?> <?= e($row['satuan']) ?> (Kritis)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <?= (int)$row['stok'] ?> <?= e($row['satuan']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= rupiah($row['harga_beli']) ?></td>
                                <td class="fw-bold text-primary"><?= rupiah($row['harga_jual']) ?></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-outline-secondary btn-sm me-1" 
                                            onclick="editSparepart(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="sparepart.php?action=hapus&id=<?= (int)$row['id_sparepart'] ?>" 
                                       class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus suku cadang ini?');">
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
     MODAL TAMBAH SPAREPART
     ===================================================================== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="sparepart.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="tambah">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools text-primary me-2"></i>Tambah Suku Cadang Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kode Barang (SKU) <span class="text-danger">*</span></label>
                        <input type="text" name="kode_sparepart" class="form-control font-mono text-uppercase" placeholder="OLI-SHL-01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kategori</label>
                        <input type="text" name="kategori" class="form-control" placeholder="Oli / Filter / Rem" value="Umum">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Suku Cadang <span class="text-danger">*</span></label>
                    <input type="text" name="nama_sparepart" class="form-control" placeholder="Contoh: Shell Helix HX7 10W-40 4L" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Stok Fisik</label>
                        <input type="number" name="stok" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Satuan</label>
                        <input type="text" name="satuan" class="form-control" value="Pcs" placeholder="Pcs / Botol / Set">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Batas Kritis</label>
                        <input type="number" name="stok_minimum" class="form-control" value="5" min="1" required>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Harga Modal Beli (Rp)</label>
                        <input type="number" name="harga_beli" class="form-control" placeholder="0" min="0" step="500">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Harga Jual Resmi (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="harga_jual" class="form-control" placeholder="0" min="0" step="500" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-save me-1"></i> Simpan Suku Cadang</button>
            </div>
        </form>
    </div>
</div>

<!-- =====================================================================
     MODAL EDIT SPAREPART
     ===================================================================== -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form action="sparepart.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_sparepart" id="edit_id_sparepart">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-secondary me-2"></i>Edit Suku Cadang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kode Barang (SKU) <span class="text-danger">*</span></label>
                        <input type="text" name="kode_sparepart" id="edit_kode_sparepart" class="form-control font-mono text-uppercase" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kategori</label>
                        <input type="text" name="kategori" id="edit_kategori" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Suku Cadang <span class="text-danger">*</span></label>
                    <input type="text" name="nama_sparepart" id="edit_nama_sparepart" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Stok Fisik</label>
                        <input type="number" name="stok" id="edit_stok" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Satuan</label>
                        <input type="text" name="satuan" id="edit_satuan" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Batas Kritis</label>
                        <input type="number" name="stok_minimum" id="edit_stok_minimum" class="form-control" min="1" required>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Harga Beli (Rp)</label>
                        <input type="number" name="harga_beli" id="edit_harga_beli" class="form-control" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Harga Jual (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="harga_jual" id="edit_harga_jual" class="form-control" min="0" required>
                    </div>
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
function editSparepart(data) {
    document.getElementById('edit_id_sparepart').value = data.id_sparepart;
    document.getElementById('edit_kode_sparepart').value = data.kode_sparepart;
    document.getElementById('edit_kategori').value = data.kategori || '';
    document.getElementById('edit_nama_sparepart').value = data.nama_sparepart;
    document.getElementById('edit_stok').value = data.stok;
    document.getElementById('edit_satuan').value = data.satuan || 'Pcs';
    document.getElementById('edit_stok_minimum').value = data.stok_minimum || 5;
    document.getElementById('edit_harga_beli').value = data.harga_beli;
    document.getElementById('edit_harga_jual').value = data.harga_jual;

    var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
