<?php
/**
 * =====================================================================
 * FILE: layanan.php
 * DESKRIPSI: Modul Tunggal Katalog Jasa Servis & Ongkos Kerja Montir
 * =====================================================================
 * 
 * ARSITEKTUR KODE:
 * Menggabungkan data_layanan, tambah_layanan, edit_layanan,
 * dan hapus_layanan menjadi 1 file terpusat.
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Katalog Jasa Servis";

// =====================================================================
// 1. PROSES POST (TAMBAH & EDIT JASA SERVIS)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action         = $_POST['action'] ?? '';
    $kode_layanan   = strtoupper(trim($_POST['kode_layanan'] ?? ''));
    $nama_layanan   = trim($_POST['nama_layanan'] ?? '');
    $kategori       = trim($_POST['kategori'] ?? 'Umum');
    $biaya_jasa     = (float)($_POST['biaya_jasa'] ?? 0);
    $estimasi_waktu = trim($_POST['estimasi_waktu'] ?? '1 Jam');

    if (empty($kode_layanan) || empty($nama_layanan) || $biaya_jasa <= 0) {
        set_flash('danger', 'Kode Jasa, Nama Layanan, dan Biaya Jasa wajib diisi dengan benar!');
        redirect('layanan.php');
    }

    if ($action === 'tambah') {
        try {
            $stmt = db()->prepare("
                INSERT INTO layanan (kode_layanan, nama_layanan, kategori, biaya_jasa, estimasi_waktu)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$kode_layanan, $nama_layanan, $kategori, $biaya_jasa, $estimasi_waktu]);

            set_flash('success', "Jasa servis <b>" . e($nama_layanan) . "</b> berhasil ditambahkan ke katalog!");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash('danger', "Kode jasa <b>" . e($kode_layanan) . "</b> sudah digunakan layanan lain!");
            } else {
                set_flash('danger', 'Gagal menyimpan layanan: ' . $e->getMessage());
            }
        }
        redirect('layanan.php');

    } elseif ($action === 'edit') {
        $id_layanan = (int)($_POST['id_layanan'] ?? 0);

        if ($id_layanan <= 0) {
            set_flash('danger', 'ID Layanan tidak valid.');
            redirect('layanan.php');
        }

        try {
            $stmt = db()->prepare("
                UPDATE layanan 
                SET kode_layanan = ?, nama_layanan = ?, kategori = ?, biaya_jasa = ?, estimasi_waktu = ?
                WHERE id_layanan = ?
            ");
            $stmt->execute([$kode_layanan, $nama_layanan, $kategori, $biaya_jasa, $estimasi_waktu, $id_layanan]);

            set_flash('success', "Data jasa <b>" . e($nama_layanan) . "</b> berhasil diperbarui!");
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal memperbarui layanan: ' . $e->getMessage());
        }
        redirect('layanan.php');
    }
}

// =====================================================================
// 2. PROSES GET (HAPUS JASA SERVIS)
// =====================================================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id_hapus = (int)($_GET['id'] ?? 0);

    if ($id_hapus > 0) {
        try {
            $stmt = db()->prepare("DELETE FROM layanan WHERE id_layanan = ?");
            $stmt->execute([$id_hapus]);
            set_flash('success', 'Jasa servis berhasil dihapus dari katalog.');
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal menghapus: Jasa ini pernah tercatat dalam nota transaksi servis pelanggan.');
        }
    }
    redirect('layanan.php');
}

// =====================================================================
// 3. AMBIL DATA JASA SERVIS
// =====================================================================
$layanan_list = db()->query("SELECT * FROM layanan ORDER BY nama_layanan ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-wrench-adjustable text-primary me-2"></i>Katalog Jasa Servis</h3>
        <p class="text-muted small mb-0">Daftar ongkos kerja teknisi, paket perawatan mesin, dan estimasi waktu kerja.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-circle me-1"></i> Tambah Jasa
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 60px;">#</th>
                        <th>Kode & Nama Jasa Servis</th>
                        <th>Kategori</th>
                        <th>Estimasi Waktu</th>
                        <th>Ongkos Jasa</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($layanan_list)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-tools fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada jasa servis yang terdaftar di katalog.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($layanan_list as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <span class="badge bg-secondary font-mono small me-1"><?= e($row['kode_layanan']) ?></span>
                                    <span class="fw-semibold text-dark"><?= e($row['nama_layanan']) ?></span>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($row['kategori']) ?></span></td>
                                <td>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= e($row['estimasi_waktu']) ?></small>
                                </td>
                                <td class="fw-bold text-success"><?= rupiah($row['biaya_jasa']) ?></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-outline-secondary btn-sm me-1" 
                                            onclick="editLayanan(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="layanan.php?action=hapus&id=<?= (int)$row['id_layanan'] ?>" 
                                       class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus jasa ini?');">
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
     MODAL TAMBAH LAYANAN
     ===================================================================== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="layanan.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="tambah">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-wrench text-primary me-2"></i>Tambah Jasa Servis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kode Jasa <span class="text-danger">*</span></label>
                        <input type="text" name="kode_layanan" class="form-control font-mono text-uppercase" placeholder="SRV-001" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kategori Pekerjaan</label>
                        <input type="text" name="kategori" class="form-control" placeholder="Mesin / Rem / AC" value="Umum">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Layanan Jasa <span class="text-danger">*</span></label>
                    <input type="text" name="nama_layanan" class="form-control" placeholder="Contoh: Tune Up Injeksi Standar" required>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Ongkos Jasa (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="biaya_jasa" class="form-control" placeholder="0" min="0" step="1000" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Estimasi Waktu</label>
                        <input type="text" name="estimasi_waktu" class="form-control" placeholder="1 Jam / 45 Menit" value="1 Jam">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-save me-1"></i> Simpan Jasa</button>
            </div>
        </form>
    </div>
</div>

<!-- =====================================================================
     MODAL EDIT LAYANAN
     ===================================================================== -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form action="layanan.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_layanan" id="edit_id_layanan">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-secondary me-2"></i>Edit Jasa Servis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kode Jasa <span class="text-danger">*</span></label>
                        <input type="text" name="kode_layanan" id="edit_kode_layanan" class="form-control font-mono text-uppercase" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kategori Pekerjaan</label>
                        <input type="text" name="kategori" id="edit_kategori" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Layanan Jasa <span class="text-danger">*</span></label>
                    <input type="text" name="nama_layanan" id="edit_nama_layanan" class="form-control" required>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Ongkos Jasa (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="biaya_jasa" id="edit_biaya_jasa" class="form-control" min="0" step="1000" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Estimasi Waktu</label>
                        <input type="text" name="estimasi_waktu" id="edit_estimasi_waktu" class="form-control">
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
function editLayanan(data) {
    document.getElementById('edit_id_layanan').value = data.id_layanan;
    document.getElementById('edit_kode_layanan').value = data.kode_layanan;
    document.getElementById('edit_kategori').value = data.kategori || '';
    document.getElementById('edit_nama_layanan').value = data.nama_layanan;
    document.getElementById('edit_biaya_jasa').value = data.biaya_jasa;
    document.getElementById('edit_estimasi_waktu').value = data.estimasi_waktu || '1 Jam';

    var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
