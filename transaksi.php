<?php
/**
 * =====================================================================
 * FILE: transaksi.php
 * DESKRIPSI: Modul Riwayat Transaksi & Pelacakan Status Seluruh Servis
 * =====================================================================
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Riwayat Servis";

// Filter status dari parameter URL (Default: Semua status)
$filter_status = $_GET['status'] ?? 'Semua';

// =====================================================================
// 1. PROSES GET: HAPUS NOTA TRANSAKSI
// =====================================================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id_hapus = (int)($_GET['id'] ?? 0);

    if ($id_hapus > 0) {
        db()->beginTransaction();
        try {
            // 1. Kembalikan semua stok sparepart yang pernah dipasang pada nota ini
            $stmt_parts = db()->prepare("SELECT id_sparepart, jumlah FROM detail_transaksi_sparepart WHERE id_transaksi = ?");
            $stmt_parts->execute([$id_hapus]);
            $used_parts = $stmt_parts->fetchAll();

            foreach ($used_parts as $up) {
                $stmt_rst = db()->prepare("UPDATE sparepart SET stok = stok + ? WHERE id_sparepart = ?");
                $stmt_rst->execute([(int)$up['jumlah'], (int)$up['id_sparepart']]);
            }

            // 2. Hapus transaksi induk (rincian detail otomatis terhapus via CASCADE)
            $stmt_del = db()->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
            $stmt_del->execute([$id_hapus]);

            db()->commit();
            set_flash('success', 'Nota transaksi berhasil dihapus dan stok sparepart telah dikembalikan ke gudang.');
        } catch (Exception $e) {
            db()->rollBack();
            set_flash('danger', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }
    redirect('transaksi.php');
}

// =====================================================================
// 2. QUERY DAFTAR TRANSAKSI DENGAN FILTER
// =====================================================================
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

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-receipt text-primary me-2"></i>Riwayat Transaksi Servis</h3>
        <p class="text-muted small mb-0">Daftar seluruh work order servis masuk, status pengerjaan, dan riwayat pelunasan kasir.</p>
    </div>
    <a href="kasir.php" class="btn btn-primary shadow-sm">
        <i class="bi bi-cart-plus me-1"></i> Buka Servis Baru (POS)
    </a>
</div>

<!-- Navigasi Filter Status Servis -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-2">
        <ul class="nav nav-pills gap-1">
            <?php 
            $statuses = ['Semua', 'Menunggu', 'Dikerjakan', 'Selesai', 'Dibatalkan'];
            foreach ($statuses as $st): 
            ?>
                <li class="nav-item">
                    <a class="nav-link btn-sm <?= $filter_status === $st ? 'active' : '' ?>" 
                       href="transaksi.php?status=<?= urlencode($st) ?>">
                        <?= e($st) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Tabel Riwayat Servis -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">No. Invoice</th>
                        <th>Waktu Masuk</th>
                        <th>Kendaraan Pasien</th>
                        <th>Pelanggan</th>
                        <th>Mekanik</th>
                        <th>Status Servis</th>
                        <th class="text-end">Total Biaya</th>
                        <th class="text-center">Pembayaran</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksi_list)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary"></i>
                                Tidak ada transaksi yang sesuai dengan filter ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transaksi_list as $row): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="detail_transaksi.php?id=<?= $row['id_transaksi'] ?>" class="font-mono fw-bold text-decoration-none text-primary">
                                        <?= e($row['kode_transaksi']) ?>
                                    </a>
                                </td>
                                <td class="small text-muted"><?= format_tanggal($row['tanggal']) ?></td>
                                <td>
                                    <span class="badge bg-dark font-mono me-1"><?= e($row['plat_nomor']) ?></span>
                                    <span class="small text-secondary"><?= e($row['merek']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= e($row['nama_pelanggan']) ?></div>
                                    <small class="text-muted"><i class="bi bi-whatsapp me-1 text-success"></i><?= e($row['no_telepon']) ?></small>
                                </td>
                                <td class="small text-dark"><?= e($row['nama_mekanik']) ?></td>
                                <td>
                                    <?php if ($row['status_servis'] === 'Menunggu'): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">⏳ Menunggu</span>
                                    <?php elseif ($row['status_servis'] === 'Dikerjakan'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">🔧 Dikerjakan</span>
                                    <?php elseif ($row['status_servis'] === 'Selesai'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">✅ Selesai</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border">❌ Batal</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold font-mono text-dark">
                                    <?= rupiah($row['total_biaya']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['status_pembayaran'] === 'Lunas'): ?>
                                        <span class="badge bg-success">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Belum Lunas</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <!-- Kelola Detail Nota -->
                                    <a href="detail_transaksi.php?id=<?= $row['id_transaksi'] ?>" class="btn btn-outline-primary btn-sm me-1" title="Lihat Detail & Kasir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <!-- Cetak Nota -->
                                    <a href="cetak_nota.php?id=<?= $row['id_transaksi'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm me-1" title="Cetak Nota">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <!-- Hapus Transaksi -->
                                    <a href="transaksi.php?action=hapus&id=<?= $row['id_transaksi'] ?>" class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('Hapus nota ini? Stok suku cadang yang terpasang akan otomatis dikembalikan ke gudang.');" title="Hapus Nota">
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

<?php include __DIR__ . '/includes/footer.php'; ?>
