<?php
/**
 * =====================================================================
 * FILE: mekanik.php
 * DESKRIPSI: Modul Tunggal Manajemen Data Teknisi / Mekanik Bengkel
 * =====================================================================
 * 
 * ARSITEKTUR KODE:
 * Menggabungkan data_mekanik, tambah_mekanik, edit_mekanik,
 * dan hapus_mekanik menjadi 1 file terpusat.
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Data Mekanik";

// =====================================================================
// 1. PROSES POST (TAMBAH & EDIT MEKANIK)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['action'] ?? '';
    $nama_mekanik = trim($_POST['nama_mekanik'] ?? '');
    $no_telepon   = trim($_POST['no_telepon'] ?? '');
    $keahlian     = trim($_POST['keahlian'] ?? 'Umum');
    $status       = in_array($_POST['status'] ?? '', ['Aktif', 'Cuti', 'Nonaktif']) ? $_POST['status'] : 'Aktif';
    $alamat       = trim($_POST['alamat'] ?? '');

    if (empty($nama_mekanik)) {
        set_flash('danger', 'Nama Mekanik wajib diisi!');
        redirect('mekanik.php');
    }

    if ($action === 'tambah') {
        try {
            $stmt = db()->prepare("
                INSERT INTO mekanik (nama_mekanik, no_telepon, keahlian, status, alamat)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nama_mekanik, $no_telepon, $keahlian, $status, $alamat]);

            set_flash('success', "Mekanik <b>" . e($nama_mekanik) . "</b> berhasil didaftarkan!");
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal mendaftarkan mekanik: ' . $e->getMessage());
        }
        redirect('mekanik.php');

    } elseif ($action === 'edit') {
        $id_mekanik = (int)($_POST['id_mekanik'] ?? 0);

        if ($id_mekanik <= 0) {
            set_flash('danger', 'ID Mekanik tidak valid.');
            redirect('mekanik.php');
        }

        try {
            $stmt = db()->prepare("
                UPDATE mekanik 
                SET nama_mekanik = ?, no_telepon = ?, keahlian = ?, status = ?, alamat = ?
                WHERE id_mekanik = ?
            ");
            $stmt->execute([$nama_mekanik, $no_telepon, $keahlian, $status, $alamat, $id_mekanik]);

            set_flash('success', "Data mekanik <b>" . e($nama_mekanik) . "</b> berhasil diperbarui!");
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal memperbarui data mekanik: ' . $e->getMessage());
        }
        redirect('mekanik.php');
    }
}

// =====================================================================
// 2. PROSES GET (HAPUS MEKANIK)
// =====================================================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id_hapus = (int)($_GET['id'] ?? 0);

    if ($id_hapus > 0) {
        try {
            $stmt = db()->prepare("DELETE FROM mekanik WHERE id_mekanik = ?");
            $stmt->execute([$id_hapus]);
            set_flash('success', 'Data mekanik berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal menghapus: Mekanik ini tercatat menangani riwayat servis.');
        }
    }
    redirect('mekanik.php');
}

// =====================================================================
// 3. AMBIL DATA MEKANIK & HITUNG PEKERJAAN AKTIF
// =====================================================================
$query_mekanik = "
    SELECT m.*, 
           COUNT(CASE WHEN t.status_servis = 'Dikerjakan' THEN 1 END) AS servis_aktif
    FROM mekanik m
    LEFT JOIN transaksi t ON m.id_mekanik = t.id_mekanik
    GROUP BY m.id_mekanik
    ORDER BY m.nama_mekanik ASC
";
$mekanik_list = db()->query($query_mekanik)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-person-badge text-primary me-2"></i>Data Mekanik</h3>
        <p class="text-muted small mb-0">Manajemen montir bengkel, status kehadiran, dan beban kerja aktif.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-circle me-1"></i> Tambah Mekanik
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 60px;">#</th>
                        <th>Nama Mekanik</th>
                        <th>Keahlian Spesialis</th>
                        <th>No. Kontak</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Beban Kerja</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mekanik_list)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada mekanik yang terdaftar di bengkel.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($mekanik_list as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($row['nama_mekanik']) ?></div>
                                    <small class="text-muted"><?= e($row['alamat'] ?: 'Alamat belum diisi') ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($row['keahlian']) ?></span></td>
                                <td>
                                    <?php if ($row['no_telepon']): ?>
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($row['no_telepon']) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['status'] === 'Aktif'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif Bekerja</span>
                                    <?php elseif ($row['status'] === 'Cuti'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Sedang Cuti</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['servis_aktif'] > 0): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-cone-striped me-1"></i><?= (int)$row['servis_aktif'] ?> Mobil
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Standby (Kosong)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-outline-secondary btn-sm me-1" 
                                            onclick="editMekanik(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="mekanik.php?action=hapus&id=<?= (int)$row['id_mekanik'] ?>" 
                                       class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data mekanik ini?');">
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
     MODAL TAMBAH MEKANIK
     ===================================================================== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="mekanik.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="tambah">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Daftarkan Mekanik Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap Mekanik <span class="text-danger">*</span></label>
                    <input type="text" name="nama_mekanik" class="form-control" placeholder="Contoh: Maman Suparman" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Keahlian / Spesialisasi</label>
                        <input type="text" name="keahlian" class="form-control" placeholder="Mesin / Kelistrikan / AC" value="Umum">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nomor WhatsApp/HP</label>
                        <input type="text" name="no_telepon" class="form-control" placeholder="081234567890">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status Kesiapan</label>
                    <select name="status" class="form-select">
                        <option value="Aktif">Aktif Bekerja</option>
                        <option value="Cuti">Sedang Cuti</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat Domisili</label>
                    <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat tinggal..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-save me-1"></i> Simpan Mekanik</button>
            </div>
        </form>
    </div>
</div>

<!-- =====================================================================
     MODAL EDIT MEKANIK
     ===================================================================== -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form action="mekanik.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_mekanik" id="edit_id_mekanik">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-secondary me-2"></i>Edit Data Mekanik</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap Mekanik <span class="text-danger">*</span></label>
                    <input type="text" name="nama_mekanik" id="edit_nama_mekanik" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Keahlian / Spesialisasi</label>
                        <input type="text" name="keahlian" id="edit_keahlian" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nomor WhatsApp/HP</label>
                        <input type="text" name="no_telepon" id="edit_no_telepon" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status Kesiapan</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="Aktif">Aktif Bekerja</option>
                        <option value="Cuti">Sedang Cuti</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat Domisili</label>
                    <textarea name="alamat" id="edit_alamat" class="form-control" rows="2"></textarea>
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
function editMekanik(data) {
    document.getElementById('edit_id_mekanik').value = data.id_mekanik;
    document.getElementById('edit_nama_mekanik').value = data.nama_mekanik;
    document.getElementById('edit_keahlian').value = data.keahlian || '';
    document.getElementById('edit_no_telepon').value = data.no_telepon || '';
    document.getElementById('edit_status').value = data.status || 'Aktif';
    document.getElementById('edit_alamat').value = data.alamat || '';

    var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
