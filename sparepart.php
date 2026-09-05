<?php
/**
 * =====================================================================
 * FILE: sparepart.php
 * DESKRIPSI: Modul Master Data Inventaris Sparepart & Kontrol Stok Gudang
 * =====================================================================
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
            set_flash('danger', 'Gagal menghapus: Barang ini sudah tercatat dalam riwayat nota servis masa lalu.');
        }
    }
    redirect('sparepart.php');
}

// =====================================================================
// 3. AMBIL DATA SPAREPART
// =====================================================================
$sparepart_list = db()->query("SELECT * FROM sparepart ORDER BY nama_sparepart ASC")->fetchAll();

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
            <li class="active">Stok Sparepart</li>
        </ul>
        <h1 class="page-title mb-1">Inventaris Suku Cadang (Sparepart)</h1>
        <p class="page-subtitle mb-0">Manajemen stok fisik suku cadang gudang bengkel, harga beli, harga jual, dan margin keuntungan.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-box-seam-fill me-1"></i> + Tambah Sparepart
        </button>
    </div>
</div>

<!-- Kartu Tabel Data Sparepart -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-boxes text-primary"></i>
            <span class="card-title mb-0">Katalog Sparepart (<?= count($sparepart_list) ?> Jenis Barang)</span>
        </div>
        <input type="text" id="filterInput" class="form-control form-control-sm table-search-input" placeholder="Cari kode atau nama barang..." onkeyup="filterTabel()">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle" id="tableSparepart">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>Kode & Nama Barang</th>
                        <th>Kategori</th>
                        <th class="text-center">Sisa Stok</th>
                        <th class="text-end">Harga Beli (Modal)</th>
                        <th class="text-end">Harga Jual (Kasir)</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sparepart_list)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada suku cadang yang didaftarkan ke gudang.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($sparepart_list as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($row['nama_sparepart']) ?></div>
                                    <span class="badge bg-light text-secondary font-mono border">
                                        <?= e($row['kode_sparepart']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border"><?= e($row['kategori']) ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['stok'] <= $row['stok_minimum']): ?>
                                        <span class="badge badge-danger-subtle font-mono">
                                            <i class="bi bi-exclamation-circle me-1"></i><?= (int)$row['stok'] ?> <?= e($row['satuan']) ?>
                                        </span>
                                        <div class="text-danger small" style="font-size: 0.7rem;">Kritis (Min: <?= (int)$row['stok_minimum'] ?>)</div>
                                    <?php else: ?>
                                        <span class="badge badge-success-subtle font-mono">
                                            <?= (int)$row['stok'] ?> <?= e($row['satuan']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-mono text-muted small">
                                    <?= rupiah($row['harga_beli']) ?>
                                </td>
                                <td class="text-end font-mono fw-bold text-dark">
                                    <?= rupiah($row['harga_jual']) ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                onclick="editSparepart(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)" 
                                                title="Edit Barang">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="sparepart.php?action=hapus&id=<?= (int)$row['id_sparepart'] ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus suku cadang ini?');" 
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

<!-- MODAL TAMBAH SPAREPART -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="sparepart.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="tambah">
            <div class="modal-header">
                <h5 class="modal-title card-title mb-0"><i class="bi bi-box-seam text-primary me-2"></i>Tambah Sparepart Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <label class="form-label">Kode Barang <span class="text-danger">*</span></label>
                        <input type="text" name="kode_sparepart" class="form-control font-mono text-uppercase" placeholder="Contoh: OLI-001" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Nama Suku Cadang <span class="text-danger">*</span></label>
                        <input type="text" name="nama_sparepart" class="form-control" placeholder="Contoh: Oli Shell Helix 10W-40" required>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select">
                            <option value="Pelumas & Fluida">Pelumas & Fluida</option>
                            <option value="Sistem Pengereman">Sistem Pengereman</option>
                            <option value="Filter & Saringan">Filter & Saringan</option>
                            <option value="Kelistrikan & Pengapian">Kelistrikan & Pengapian</option>
                            <option value="Kaki-kaki & Suspensi">Kaki-kaki & Suspensi</option>
                            <option value="Umum" selected>Umum</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stok Fisik</label>
                        <input type="number" name="stok" class="form-control font-mono" value="10" min="0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Satuan</label>
                        <input type="text" name="satuan" class="form-control" value="Pcs" placeholder="Liter, Pcs, Set">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Harga Beli Modal (Rp)</label>
                        <input type="number" name="harga_beli" class="form-control font-mono" placeholder="Contoh: 75000" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga Jual Kasir (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="harga_jual" class="form-control font-mono" placeholder="Contoh: 95000" min="0" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Batas Peringatan Stok Minimum</label>
                    <input type="number" name="stok_minimum" class="form-control font-mono" value="5" min="1">
                    <small class="text-muted">Sistem akan memberi peringatan di Dashboard saat stok mencapai angka ini.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Sparepart</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT SPAREPART -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form action="sparepart.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_sparepart" id="edit_id_sparepart">
            <div class="modal-header">
                <h5 class="modal-title card-title mb-0"><i class="bi bi-pencil-square text-secondary me-2"></i>Edit Data Suku Cadang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <label class="form-label">Kode Barang <span class="text-danger">*</span></label>
                        <input type="text" name="kode_sparepart" id="edit_kode_sparepart" class="form-control font-mono text-uppercase" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Nama Suku Cadang <span class="text-danger">*</span></label>
                        <input type="text" name="nama_sparepart" id="edit_nama_sparepart" class="form-control" required>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" id="edit_kategori" class="form-select">
                            <option value="Pelumas & Fluida">Pelumas & Fluida</option>
                            <option value="Sistem Pengereman">Sistem Pengereman</option>
                            <option value="Filter & Saringan">Filter & Saringan</option>
                            <option value="Kelistrikan & Pengapian">Kelistrikan & Pengapian</option>
                            <option value="Kaki-kaki & Suspensi">Kaki-kaki & Suspensi</option>
                            <option value="Umum">Umum</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stok Fisik</label>
                        <input type="number" name="stok" id="edit_stok" class="form-control font-mono" min="0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Satuan</label>
                        <input type="text" name="satuan" id="edit_satuan" class="form-control">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Harga Beli Modal (Rp)</label>
                        <input type="number" name="harga_beli" id="edit_harga_beli" class="form-control font-mono" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga Jual Kasir (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="harga_jual" id="edit_harga_jual" class="form-control font-mono" min="0" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Batas Peringatan Stok Minimum</label>
                    <input type="number" name="stok_minimum" id="edit_stok_minimum" class="form-control font-mono" min="1">
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
function editSparepart(data) {
    document.getElementById('edit_id_sparepart').value = data.id_sparepart;
    document.getElementById('edit_kode_sparepart').value = data.kode_sparepart;
    document.getElementById('edit_nama_sparepart').value = data.nama_sparepart;
    document.getElementById('edit_kategori').value = data.kategori || 'Umum';
    document.getElementById('edit_stok').value = data.stok;
    document.getElementById('edit_satuan').value = data.satuan || 'Pcs';
    document.getElementById('edit_harga_beli').value = data.harga_beli;
    document.getElementById('edit_harga_jual').value = data.harga_jual;
    document.getElementById('edit_stok_minimum').value = data.stok_minimum || 5;
    
    var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}

function filterTabel() {
    const filter = document.getElementById("filterInput").value.toUpperCase();
    const rows = document.querySelectorAll("#tableSparepart tbody tr");

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
