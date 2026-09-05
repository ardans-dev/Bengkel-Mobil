<?php
/**
 * =====================================================================
 * FILE: kendaraan.php
 * DESKRIPSI: Modul Master Data Kendaraan / Mobil Pasien (CRUD Lengkap)
 * =====================================================================
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Data Kendaraan";

// =====================================================================
// 1. PROSES POST (TAMBAH & EDIT KENDARAAN)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['action'] ?? '';
    $id_pelanggan = (int)($_POST['id_pelanggan'] ?? 0);
    $plat_nomor   = strtoupper(trim($_POST['plat_nomor'] ?? ''));
    $merek        = trim($_POST['merek'] ?? '');
    $tipe         = trim($_POST['tipe'] ?? '');
    $tahun        = !empty($_POST['tahun']) ? (int)$_POST['tahun'] : null;
    $transmisi    = in_array($_POST['transmisi'] ?? '', ['Manual', 'Matic']) ? $_POST['transmisi'] : 'Manual';
    $warna        = trim($_POST['warna'] ?? '');

    if ($id_pelanggan <= 0 || empty($plat_nomor) || empty($merek)) {
        set_flash('danger', 'Pemilik mobil, Plat Nomor, dan Merek wajib diisi!');
        redirect('kendaraan.php');
    }

    if ($action === 'tambah') {
        try {
            $stmt = db()->prepare("
                INSERT INTO kendaraan (id_pelanggan, plat_nomor, merek, tipe, tahun, transmisi, warna) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_pelanggan, $plat_nomor, $merek, $tipe, $tahun, $transmisi, $warna]);

            set_flash('success', "Kendaraan dengan plat <b>" . e($plat_nomor) . "</b> berhasil didaftarkan!");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash('danger', "Plat nomor <b>" . e($plat_nomor) . "</b> sudah terdaftar di sistem!");
            } else {
                set_flash('danger', 'Gagal menyimpan kendaraan: ' . $e->getMessage());
            }
        }
        redirect('kendaraan.php');

    } elseif ($action === 'edit') {
        $id_kendaraan = (int)($_POST['id_kendaraan'] ?? 0);

        if ($id_kendaraan <= 0) {
            set_flash('danger', 'ID Kendaraan tidak valid.');
            redirect('kendaraan.php');
        }

        try {
            $stmt = db()->prepare("
                UPDATE kendaraan 
                SET id_pelanggan = ?, plat_nomor = ?, merek = ?, tipe = ?, tahun = ?, transmisi = ?, warna = ? 
                WHERE id_kendaraan = ?
            ");
            $stmt->execute([$id_pelanggan, $plat_nomor, $merek, $tipe, $tahun, $transmisi, $warna, $id_kendaraan]);

            set_flash('success', "Data mobil <b>" . e($plat_nomor) . "</b> berhasil diperbarui!");
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal memperbarui kendaraan: ' . $e->getMessage());
        }
        redirect('kendaraan.php');
    }
}

// =====================================================================
// 2. PROSES GET (HAPUS KENDARAAN)
// =====================================================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id_hapus = (int)($_GET['id'] ?? 0);

    if ($id_hapus > 0) {
        try {
            $stmt = db()->prepare("DELETE FROM kendaraan WHERE id_kendaraan = ?");
            $stmt->execute([$id_hapus]);
            set_flash('success', 'Data kendaraan berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal menghapus: Mobil ini sudah memiliki riwayat servis.');
        }
    }
    redirect('kendaraan.php');
}

// =====================================================================
// 3. AMBIL DATA KENDARAAN (JOIN DENGAN PEMILIK)
// =====================================================================
$query_kendaraan = "
    SELECT k.*, p.nama AS nama_pemilik, p.no_telepon 
    FROM kendaraan k 
    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan 
    ORDER BY k.id_kendaraan DESC
";
$kendaraan_list = db()->query($query_kendaraan)->fetchAll();

// Seluruh pelanggan untuk opsi dropdown
$pelanggan_options = db()->query("SELECT id_pelanggan, nama, no_telepon FROM pelanggan ORDER BY nama ASC")->fetchAll();

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
            <li class="active">Mobil Pasien</li>
        </ul>
        <h1 class="page-title mb-1">Data Kendaraan</h1>
        <p class="page-subtitle mb-0">Manajemen direktori unit mobil pelanggan, plat nomor polisi, transmisi, dan spesifikasi.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-car-front-fill me-1"></i> + Tambah Mobil
        </button>
    </div>
</div>

<!-- Kartu Tabel Data Kendaraan -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-car-front-fill text-primary"></i>
            <span class="card-title mb-0">Daftar Mobil Terdaftar (<?= count($kendaraan_list) ?> Unit)</span>
        </div>
        <input type="text" id="filterInput" class="form-control form-control-sm table-search-input" placeholder="Cari plat, merek, pemilik..." onkeyup="filterTabel()">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle" id="tableKendaraan">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>Plat Nomor Polisi</th>
                        <th>Merek & Tipe Kendaraan</th>
                        <th>Pemilik Terdaftar</th>
                        <th>Spesifikasi Mesin</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kendaraan_list)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-car-front fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada kendaraan yang didaftarkan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($kendaraan_list as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <span class="badge-plat"><?= e($row['plat_nomor']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($row['merek']) ?> <?= e($row['tipe']) ?></div>
                                    <small class="text-muted"><?= e($row['warna'] ?: 'Warna Standar') ?></small>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= e($row['nama_pemilik']) ?></div>
                                    <small class="text-muted"><i class="bi bi-whatsapp me-1 text-success"></i><?= e($row['no_telepon']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?= e($row['transmisi']) ?>
                                    </span>
                                    <?php if ($row['tahun']): ?>
                                        <span class="badge bg-light text-secondary border"><?= (int)$row['tahun'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                onclick="editKendaraan(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)" 
                                                title="Edit Mobil">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="kendaraan.php?action=hapus&id=<?= (int)$row['id_kendaraan'] ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data kendaraan ini?');" 
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

<!-- MODAL TAMBAH KENDARAAN -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="kendaraan.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="tambah">
            <div class="modal-header">
                <h5 class="modal-title card-title mb-0"><i class="bi bi-car-front text-primary me-2"></i>Daftarkan Mobil Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Pilih Pemilik Mobil <span class="text-danger">*</span></label>
                    <select name="id_pelanggan" class="form-select" required>
                        <option value="">-- Pilih Pelanggan Terdaftar --</option>
                        <?php foreach ($pelanggan_options as $pel): ?>
                            <option value="<?= (int)$pel['id_pelanggan'] ?>">
                                <?= e($pel['nama']) ?> (<?= e($pel['no_telepon']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Plat Nomor Polisi <span class="text-danger">*</span></label>
                        <input type="text" name="plat_nomor" class="form-control font-mono text-uppercase" placeholder="Contoh: D 1234 ABC" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Merek Mobil <span class="text-danger">*</span></label>
                        <input type="text" name="merek" class="form-control" placeholder="Contoh: Toyota" required>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Model / Tipe</label>
                        <input type="text" name="tipe" class="form-control" placeholder="Contoh: Avanza G 1.3">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tahun Pembuatan</label>
                        <input type="number" name="tahun" class="form-control font-mono" placeholder="2018" min="1980" max="2030">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Transmisi</label>
                        <select name="transmisi" class="form-select">
                            <option value="Manual">Manual</option>
                            <option value="Matic">Matic (Otomatis)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Warna Bodi</label>
                        <input type="text" name="warna" class="form-control" placeholder="Contoh: Hitam Metalik">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Daftarkan Mobil</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT KENDARAAN -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form action="kendaraan.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_kendaraan" id="edit_id_kendaraan">
            <div class="modal-header">
                <h5 class="modal-title card-title mb-0"><i class="bi bi-pencil-square text-secondary me-2"></i>Edit Data Kendaraan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Pilih Pemilik Mobil <span class="text-danger">*</span></label>
                    <select name="id_pelanggan" id="edit_id_pelanggan" class="form-select" required>
                        <?php foreach ($pelanggan_options as $pel): ?>
                            <option value="<?= (int)$pel['id_pelanggan'] ?>">
                                <?= e($pel['nama']) ?> (<?= e($pel['no_telepon']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Plat Nomor Polisi <span class="text-danger">*</span></label>
                        <input type="text" name="plat_nomor" id="edit_plat_nomor" class="form-control font-mono text-uppercase" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Merek Mobil <span class="text-danger">*</span></label>
                        <input type="text" name="merek" id="edit_merek" class="form-control" required>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Model / Tipe</label>
                        <input type="text" name="tipe" id="edit_tipe" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tahun Pembuatan</label>
                        <input type="number" name="tahun" id="edit_tahun" class="form-control font-mono">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Transmisi</label>
                        <select name="transmisi" id="edit_transmisi" class="form-select">
                            <option value="Manual">Manual</option>
                            <option value="Matic">Matic (Otomatis)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Warna Bodi</label>
                        <input type="text" name="warna" id="edit_warna" class="form-control">
                    </div>
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
function editKendaraan(data) {
    document.getElementById('edit_id_kendaraan').value = data.id_kendaraan;
    document.getElementById('edit_id_pelanggan').value = data.id_pelanggan;
    document.getElementById('edit_plat_nomor').value = data.plat_nomor;
    document.getElementById('edit_merek').value = data.merek;
    document.getElementById('edit_tipe').value = data.tipe || '';
    document.getElementById('edit_tahun').value = data.tahun || '';
    document.getElementById('edit_transmisi').value = data.transmisi;
    document.getElementById('edit_warna').value = data.warna || '';
    
    var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}

function filterTabel() {
    const filter = document.getElementById("filterInput").value.toUpperCase();
    const rows = document.querySelectorAll("#tableKendaraan tbody tr");

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
