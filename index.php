<?php
/**
 * =====================================================================
 * FILE: index.php
 * DESKRIPSI: Dashboard Utama Sistem Bengkel Mobil & Kasir POS
 * =====================================================================
 * 
 * FUNGSI:
 * 1. Menampilkan ringkasan metrik finansial (total omset pendapatan bengkel).
 * 2. Memantau antrean pengerjaan mobil (Menunggu vs Sedang Dikerjakan).
 * 3. Memberikan peringatan dini suku cadang kritis (stok menipis yang wajib direstock).
 * 4. Menyajikan daftar 5 transaksi servis terbaru untuk akses cepat kasir.
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Dashboard";

// =====================================================================
// 1. QUERY ANALITIK METRIK UTAMA DASHBOARD
// =====================================================================

// A. Total Pendapatan Lunas
$pendapatan_total = (float)(db()->query("
    SELECT SUM(total_biaya) AS total 
    FROM transaksi 
    WHERE status_pembayaran = 'Lunas'
")->fetch()['total'] ?? 0);

// B. Mobil yang Sedang Aktif Dikerjakan di Bengkel
$mobil_dikerjakan = (int)(db()->query("
    SELECT COUNT(*) AS total 
    FROM transaksi 
    WHERE status_servis = 'Dikerjakan'
")->fetch()['total'] ?? 0);

// C. Mobil yang Masuk Antrean Menunggu
$antrean_menunggu = (int)(db()->query("
    SELECT COUNT(*) AS total 
    FROM transaksi 
    WHERE status_servis = 'Menunggu'
")->fetch()['total'] ?? 0);

// D. Jumlah Suku Cadang yang Stoknya Menipis / Kritis (stok <= stok_minimum)
$stok_kritis_count = (int)(db()->query("
    SELECT COUNT(*) AS total 
    FROM sparepart 
    WHERE stok <= stok_minimum
")->fetch()['total'] ?? 0);

// E. 5 Transaksi Servis Terbaru
$transaksi_terbaru = db()->query("
    SELECT t.*, k.plat_nomor, k.merek, p.nama AS nama_pelanggan, m.nama_mekanik
    FROM transaksi t
    JOIN kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan
    JOIN mekanik m ON t.id_mekanik = m.id_mekanik
    ORDER BY t.id_transaksi DESC
    LIMIT 5
")->fetchAll();

// F. Daftar Barang Kritis untuk Widget Pengingat Restock
$daftar_kritis = db()->query("
    SELECT kode_sparepart, nama_sparepart, stok, satuan, stok_minimum 
    FROM sparepart 
    WHERE stok <= stok_minimum 
    ORDER BY stok ASC 
    LIMIT 4
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Banner Sambutan & Aksi Cepat -->
<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7">
        <h3 class="fw-bold mb-1">
            Halo, <?= e($user_login['nama']) ?>! <span class="text-primary">👋</span>
        </h3>
        <p class="text-muted small mb-0">
            Berikut ringkasan operasional dan kondisi kasir Bengkel Mobil Ardans hari ini.
        </p>
    </div>
    <div class="col-md-5 text-md-end">
        <a href="kasir.php" class="btn btn-warning btn-lg fw-bold shadow-sm text-dark px-4">
            <i class="bi bi-cart-check-fill me-1"></i> Buka Servis Baru (Kasir POS)
        </a>
    </div>
</div>

<!-- =====================================================================
     4 KARTU METRIK UTAMA (FINANSIAL & OPERASIONAL)
     ===================================================================== -->
<div class="row g-3 mb-4">
    <!-- 1. Total Omset Pendapatan -->
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100 p-3 bg-white border-start border-primary border-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Total Pendapatan</span>
                <span class="badge bg-primary-subtle text-primary p-2 rounded-circle"><i class="bi bi-wallet2 fs-6"></i></span>
            </div>
            <div class="fs-4 fw-bold text-dark font-mono"><?= rupiah($pendapatan_total) ?></div>
            <small class="text-success mt-1"><i class="bi bi-check-circle me-1"></i>Dari seluruh transaksi lunas</small>
        </div>
    </div>

    <!-- 2. Sedang Dikerjakan -->
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100 p-3 bg-white border-start border-warning border-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Sedang Dikerjakan</span>
                <span class="badge bg-warning-subtle text-warning p-2 rounded-circle"><i class="bi bi-wrench-adjustable fs-6"></i></span>
            </div>
            <div class="fs-4 fw-bold text-dark font-mono"><?= $mobil_dikerjakan ?> Unit</div>
            <small class="text-warning-emphasis mt-1"><i class="bi bi-gear-wide me-1"></i>Montir sedang aktif servis</small>
        </div>
    </div>

    <!-- 3. Antrean Menunggu -->
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100 p-3 bg-white border-start border-info border-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Antrean Masuk</span>
                <span class="badge bg-info-subtle text-info p-2 rounded-circle"><i class="bi bi-clock-history fs-6"></i></span>
            </div>
            <div class="fs-4 fw-bold text-dark font-mono"><?= $antrean_menunggu ?> Unit</div>
            <small class="text-muted mt-1"><i class="bi bi-hourglass me-1"></i>Menunggu giliran servis</small>
        </div>
    </div>

    <!-- 4. Peringatan Stok Kritis -->
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100 p-3 bg-white border-start border-danger border-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">Stok Kritis</span>
                <span class="badge bg-danger-subtle text-danger p-2 rounded-circle"><i class="bi bi-exclamation-triangle fs-6"></i></span>
            </div>
            <div class="fs-4 fw-bold text-danger font-mono"><?= $stok_kritis_count ?> Item</div>
            <small class="text-danger mt-1"><i class="bi bi-bell me-1"></i>Perlu segera restock gudang</small>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- =================================================================
         TABEL 5 SERVIS TERBARU
         ================================================================= -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>5 Servis Masuk Terbaru</h6>
                <a href="transaksi.php" class="small text-decoration-none fw-semibold">Lihat Seluruh Riwayat →</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-3">Invoice</th>
                                <th>Mobil</th>
                                <th>Pelanggan</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaksi_terbaru)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">Belum ada aktivitas servis.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksi_terbaru as $t): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <a href="detail_transaksi.php?id=<?= $t['id_transaksi'] ?>" class="font-mono fw-bold text-decoration-none text-primary small">
                                                <?= e($t['kode_transaksi']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark font-mono"><?= e($t['plat_nomor']) ?></span>
                                            <small class="text-muted d-block"><?= e($t['merek']) ?></small>
                                        </td>
                                        <td class="small fw-medium text-dark"><?= e($t['nama_pelanggan']) ?></td>
                                        <td>
                                            <?php if ($t['status_servis'] === 'Selesai'): ?>
                                                <span class="badge bg-success-subtle text-success border">Selesai</span>
                                            <?php elseif ($t['status_servis'] === 'Dikerjakan'): ?>
                                                <span class="badge bg-warning-subtle text-warning border">Dikerjakan</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border">Menunggu</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3 font-mono fw-bold small text-dark">
                                            <?= rupiah($t['total_biaya']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================
         WIDGET STOK MENIPIS & SHORTCUT CEPAT
         ================================================================= -->
    <div class="col-lg-4">
        <!-- Widget Stok Menipis -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-exclamation-octagon me-2"></i>Peringatan Stok Menipis</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($daftar_kritis)): ?>
                    <div class="text-center py-3 text-muted small">
                        <i class="bi bi-shield-check fs-2 text-success d-block mb-1"></i>
                        Semua stok suku cadang aman dan tercukupi.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($daftar_kritis as $dk): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <div>
                                    <div class="fw-semibold text-dark"><?= e($dk['nama_sparepart']) ?></div>
                                    <span class="badge bg-light text-secondary font-mono border"><?= e($dk['kode_sparepart']) ?></span>
                                </div>
                                <span class="badge bg-danger">
                                    Sisa <?= (int)$dk['stok'] ?> <?= e($dk['satuan']) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="text-center mt-3 pt-2 border-top">
                        <a href="sparepart.php" class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-box-seam me-1"></i> Kelola Inventaris Suku Cadang
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Widget Pintasan Akses Cepat -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-3 small text-secondary text-uppercase">Pintasan Cepat</h6>
                <div class="d-grid gap-2">
                    <a href="pelanggan.php" class="btn btn-light text-start border btn-sm">
                        <i class="bi bi-person-plus text-primary me-2"></i>Tambah Pelanggan Baru
                    </a>
                    <a href="kendaraan.php" class="btn btn-light text-start border btn-sm">
                        <i class="bi bi-car-front text-primary me-2"></i>Daftarkan Mobil Pasien
                    </a>
                    <a href="sparepart.php" class="btn btn-light text-start border btn-sm">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Restock Barang Gudang
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
