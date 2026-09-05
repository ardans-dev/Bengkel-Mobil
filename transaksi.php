<?php
/**
 * =====================================================================
 * FILE: transaksi.php
 * DESKRIPSI: Modul Riwayat Servis & Pelacakan Status Antrean Seluruh Unit
 * =====================================================================
 * 
 * ALUR PENGGUNA (LANGKAH 2 & 3):
 * 1. Menampilkan seluruh antrean mobil yang masuk ke bengkel.
 * 2. Kasir/Mekanik dapat mengklik nomor nota untuk menginput jasa & part.
 * 3. Status pengerjaan diperbarui: Menunggu -> Dikerjakan -> Selesai.
 * 4. Pembayaran diselesaikan di kasir & cetak struk nota resmi.
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Antrean & Servis";

$filter_status = $_GET['status'] ?? 'Semua';

// Hapus Transaksi (Rollback stok jika ada part terpasang)
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id_hapus = (int)($_GET['id'] ?? 0);

    if ($id_hapus > 0) {
        db()->beginTransaction();
        try {
            $stmt_parts = db()->prepare("SELECT id_sparepart, jumlah FROM detail_transaksi_sparepart WHERE id_transaksi = ?");
            $stmt_parts->execute([$id_hapus]);
            $used_parts = $stmt_parts->fetchAll();

            foreach ($used_parts as $up) {
                $stmt_rst = db()->prepare("UPDATE sparepart SET stok = stok + ? WHERE id_sparepart = ?");
                $stmt_rst->execute([(int)$up['jumlah'], (int)$up['id_sparepart']]);
            }

            $stmt_del = db()->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
            $stmt_del->execute([$id_hapus]);

            db()->commit();
            set_flash('success', 'Nota servis berhasil dihapus dan stok sparepart terkait telah dikembalikan.');
        } catch (Exception $e) {
            db()->rollBack();
            set_flash('danger', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }
    redirect('transaksi.php');
}

$sql = "
    SELECT t.*, 
           k.plat_nomor, k.merek, k.tipe, 
           p.nama AS nama_pelanggan, p.no_telepon,
           m.nama_mekanik
    FROM transaksi t
    JOIN kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan
    JOIN mekanik m ON t.id_mekanik = m.id_mekanik
";

$params = [];
if ($filter_status !== 'Semua') {
    $sql .= " WHERE t.status_servis = ?";
    $params[] = $filter_status;
}
$sql .= " ORDER BY t.id_transaksi DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$transaksi_list = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Header Standar Terpadu -->
<div class="page-header-box">
    <div>
        <ul class="breadcrumb-custom">
            <li><a href="index.php">Dashboard</a></li>
            <li>/</li>
            <li class="active">Antrean & Servis</li>
        </ul>
        <h1 class="page-title mb-1">Antrean & Riwayat Servis</h1>
        <p class="page-subtitle mb-0">Pantau mobil yang sedang diservis montir, kelola pemakaian sparepart, dan lakukan penagihan kasir.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="kasir.php" class="btn btn-warning text-dark fw-bold">
            <i class="bi bi-plus-circle-fill me-1"></i> + Mobil Masuk Baru
        </a>
    </div>
</div>

<!-- Filter Tab Status -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex gap-1 overflow-x-auto">
                <?php 
                $statuses = ['Semua', 'Menunggu', 'Dikerjakan', 'Selesai', 'Dibatalkan'];
                foreach ($statuses as $st): 
                ?>
                    <a class="btn btn-sm <?= $filter_status === $st ? 'btn-primary' : 'btn-light border text-muted' ?>" 
                       href="transaksi.php?status=<?= urlencode($st) ?>">
                        <?php if ($st === 'Semua'): ?>
                            Semua Status
                        <?php elseif ($st === 'Menunggu'): ?>
                            ⏳ Menunggu
                        <?php elseif ($st === 'Dikerjakan'): ?>
                            🔧 Dikerjakan
                        <?php elseif ($st === 'Selesai'): ?>
                            ✓ Selesai
                        <?php else: ?>
                            ✕ Batal
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Instant Search Table -->
            <input type="text" id="filterInput" class="form-control form-control-sm table-search-input" placeholder="Cari invoice, plat, nama..." onkeyup="filterTabel()">
        </div>
    </div>
</div>

<!-- Tabel Daftar Servis -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle" id="tableAntrean">
                <thead>
                    <tr>
                        <th class="ps-4">No. Invoice</th>
                        <th>Waktu Masuk</th>
                        <th>Mobil Pasien</th>
                        <th>Pelanggan</th>
                        <th>Mekanik</th>
                        <th>Status Servis</th>
                        <th class="text-end">Total Tagihan</th>
                        <th class="text-center">Pembayaran</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksi_list)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada riwayat transaksi dengan filter status ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transaksi_list as $row): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="detail_transaksi.php?id=<?= (int)$row['id_transaksi'] ?>" class="font-mono fw-bold text-primary text-decoration-none">
                                        <?= e($row['kode_transaksi']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="text-dark small"><?= date('d/m/Y', strtotime($row['tanggal_masuk'])) ?></div>
                                    <small class="text-muted"><?= date('H:i', strtotime($row['tanggal_masuk'])) ?> WIB</small>
                                </td>
                                <td>
                                    <span class="badge-plat me-1"><?= e($row['plat_nomor']) ?></span>
                                    <div class="small fw-semibold text-dark mt-1"><?= e($row['merek']) ?> <?= e($row['tipe']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= e($row['nama_pelanggan']) ?></div>
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($row['no_telepon']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-person me-1"></i><?= e($row['nama_mekanik']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status_servis'] === 'Menunggu'): ?>
                                        <span class="badge badge-warning-subtle"><i class="bi bi-clock me-1"></i>Menunggu</span>
                                    <?php elseif ($row['status_servis'] === 'Dikerjakan'): ?>
                                        <span class="badge badge-info-subtle"><i class="bi bi-gear me-1"></i>Dikerjakan</span>
                                    <?php elseif ($row['status_servis'] === 'Selesai'): ?>
                                        <span class="badge badge-success-subtle"><i class="bi bi-check-circle me-1"></i>Selesai</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger-subtle"><i class="bi bi-x-circle me-1"></i>Batal</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-mono fw-bold text-dark">
                                    <?= rupiah($row['total_biaya']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['status_pembayaran'] === 'Lunas'): ?>
                                        <span class="badge badge-success-subtle"><i class="bi bi-check2-all me-1"></i>Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger-subtle"><i class="bi bi-hourglass me-1"></i>Belum Bayar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <a href="detail_transaksi.php?id=<?= (int)$row['id_transaksi'] ?>" 
                                           class="btn btn-primary btn-sm" title="Buka Kasir / Pengerjaan">
                                            <i class="bi bi-eye me-1"></i> Detail
                                        </a>
                                        <a href="cetak_nota.php?id=<?= (int)$row['id_transaksi'] ?>" 
                                           target="_blank" class="btn btn-outline-secondary btn-sm" title="Cetak Nota Resmi">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="transaksi.php?action=hapus&id=<?= (int)$row['id_transaksi'] ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('Peringatan: Menghapus tiket servis ini akan mengembalikan seluruh stok sparepart yang terpasang. Lanjutkan?');" 
                                           title="Hapus Tiket">
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

<script>
// Filter pencarian tabel instan (Vanilla JS)
function filterTabel() {
    const filter = document.getElementById("filterInput").value.toUpperCase();
    const rows = document.querySelectorAll("#tableAntrean tbody tr");

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
