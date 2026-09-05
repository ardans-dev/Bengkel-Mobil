<?php
/**
 * =====================================================================
 * FILE: dashboard.php
 * DESKRIPSI: Dashboard Utama Operasional & Monitoring Bengkel Mobil
 * =====================================================================
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Dashboard";

// =====================================================================
// 1. QUERY METRIK ANALITIK
// =====================================================================
// Total omzet dari transaksi yang sudah lunas
$pendapatan_total = (float)(db()->query("
    SELECT SUM(total_biaya) AS total 
    FROM transaksi 
    WHERE status_pembayaran = 'Lunas'
")->fetch()['total'] ?? 0);

// Jumlah mobil yang saat ini sedang dikerjakan oleh montir
$mobil_dikerjakan = (int)(db()->query("
    SELECT COUNT(*) AS total 
    FROM transaksi 
    WHERE status_servis = 'Dikerjakan'
")->fetch()['total'] ?? 0);

// Jumlah mobil yang masih menunggu giliran servis
$antrean_menunggu = (int)(db()->query("
    SELECT COUNT(*) AS total 
    FROM transaksi 
    WHERE status_servis = 'Menunggu'
")->fetch()['total'] ?? 0);

// Jumlah item suku cadang yang stoknya berada di bawah batas minimum
$stok_kritis_count = (int)(db()->query("
    SELECT COUNT(*) AS total 
    FROM sparepart 
    WHERE stok <= stok_minimum
")->fetch()['total'] ?? 0);

// Ambil 5 transaksi servis terbaru untuk tabel aktivitas
$transaksi_terbaru = db()->query("
    SELECT t.*, k.plat_nomor, k.merek, p.nama AS nama_pelanggan, m.nama_mekanik
    FROM transaksi t
    JOIN kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan
    JOIN mekanik m ON t.id_mekanik = m.id_mekanik
    ORDER BY t.id_transaksi DESC
    LIMIT 5
")->fetchAll();

// Ambil daftar barang suku cadang yang stoknya menipis
$daftar_kritis = db()->query("
    SELECT kode_sparepart, nama_sparepart, stok, satuan, stok_minimum 
    FROM sparepart 
    WHERE stok <= stok_minimum 
    ORDER BY stok ASC 
    LIMIT 4
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- =====================================================================
     BAGIAN 1: HEADER SAMBUTAN & 1 TOMBOL UTAMA + MOBIL MASUK
     ===================================================================== -->
<div class="page-header-box">
    <div>
        <h1 class="page-title mb-1">Selamat Datang, <?= e($user_login['nama']) ?> 👋</h1>
        <p class="page-subtitle mb-0">Pusat kendali operasional bengkel, monitoring antrean servis, dan kasir penjualan.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <!-- SATU-SATUNYA TOMBOL UTAMA BUKA TIKET BARU -->
        <a href="kasir.php" class="btn btn-warning text-dark fw-bold">
            <i class="bi bi-plus-circle-fill me-1"></i> + Mobil Masuk (Tiket Baru)
        </a>
    </div>
</div>

<!-- =====================================================================
     BAGIAN 2: 4 KARTU METRIK RINGKASAN
     ===================================================================== -->
<div class="row g-3 mb-4">
    <!-- 1. Total Pendapatan -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card border-start border-4 border-primary">
            <div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="stat-card-title">TOTAL PENDAPATAN</span>
                    <span class="badge badge-primary-subtle"><i class="bi bi-wallet2"></i></span>
                </div>
                <div class="stat-card-value text-primary"><?= rupiah($pendapatan_total) ?></div>
            </div>
            <div class="stat-card-footer text-success">
                <i class="bi bi-check-circle-fill"></i> Akumulasi pembayaran lunas
            </div>
        </div>
    </div>

    <!-- 2. Sedang Dikerjakan -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card border-start border-4 border-warning">
            <div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="stat-card-title">SEDANG DIKERJAKAN</span>
                    <span class="badge badge-warning-subtle"><i class="bi bi-gear-fill"></i></span>
                </div>
                <div class="stat-card-value text-warning"><?= $mobil_dikerjakan ?> Unit</div>
            </div>
            <div class="stat-card-footer text-warning">
                <i class="bi bi-wrench"></i> Montir aktif mengerjakan
            </div>
        </div>
    </div>

    <!-- 3. Antrean Menunggu -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card border-start border-4 border-info">
            <div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="stat-card-title">ANTREAN MENUNGGU</span>
                    <span class="badge badge-info-subtle"><i class="bi bi-hourglass-split"></i></span>
                </div>
                <div class="stat-card-value text-info"><?= $antrean_menunggu ?> Unit</div>
            </div>
            <div class="stat-card-footer text-muted">
                <i class="bi bi-clock"></i> Menunggu antrean bengkel
            </div>
        </div>
    </div>

    <!-- 4. Stok Kritis -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card border-start border-4 border-danger">
            <div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="stat-card-title">STOK MENIPIS</span>
                    <span class="badge badge-danger-subtle"><i class="bi bi-exclamation-triangle-fill"></i></span>
                </div>
                <div class="stat-card-value text-danger"><?= $stok_kritis_count ?> Item</div>
            </div>
            <div class="stat-card-footer text-danger">
                <i class="bi bi-bell-fill"></i> Segera lakukan restock
            </div>
        </div>
    </div>
</div>

<!-- =====================================================================
     BAGIAN 3: TABEL AKTIVITAS SERVIS & WIDGET STOK
     ===================================================================== -->
<div class="row g-4">
    <!-- Kolom Kiri: 5 Servis Terbaru -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-receipt-cutoff text-primary"></i>
                    <span class="card-title mb-0">Aktivitas Servis Terbaru</span>
                </div>
                <a href="transaksi.php" class="small text-decoration-none fw-semibold">Buka Semua Antrean →</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">No. Invoice</th>
                                <th>Mobil Pasien</th>
                                <th>Pemilik</th>
                                <th>Status Servis</th>
                                <th class="text-end pe-4">Total Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaksi_terbaru)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">
                                        <i class="bi bi-inbox fs-4 d-block mb-1 text-secondary"></i>
                                        Belum ada data servis masuk hari ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksi_terbaru as $t): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <a href="detail_transaksi.php?id=<?= (int)$t['id_transaksi'] ?>" class="font-mono fw-bold text-decoration-none text-info">
                                                <?= e($t['kode_transaksi']) ?>
                                            </a>
                                            <div class="text-muted" style="font-size: 0.725rem;"><?= date('d/m/Y H:i', strtotime($t['tanggal'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge-plat me-1"><?= e($t['plat_nomor']) ?></span>
                                            <span class="fw-semibold text-light"><?= e($t['merek']) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-light"><?= e($t['nama_pelanggan']) ?></div>
                                            <small class="text-muted">Mekanik: <?= e($t['nama_mekanik']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($t['status_servis'] === 'Menunggu'): ?>
                                                <span class="badge badge-warning-subtle"><i class="bi bi-clock me-1"></i>Menunggu</span>
                                            <?php elseif ($t['status_servis'] === 'Dikerjakan'): ?>
                                                <span class="badge badge-info-subtle"><i class="bi bi-gear me-1"></i>Dikerjakan</span>
                                            <?php else: ?>
                                                <span class="badge badge-success-subtle"><i class="bi bi-check-circle me-1"></i>Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4 font-mono fw-bold text-light">
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

    <!-- Kolom Kanan: Peringatan Stok Kritis -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-bell-fill text-danger"></i>
                    <span class="card-title mb-0">Peringatan Suku Cadang</span>
                </div>
                <a href="sparepart.php" class="small text-decoration-none fw-semibold">Kelola Stok →</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($daftar_kritis)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                        <p class="mb-0 small fw-medium">Semua stok suku cadang dalam kondisi aman dan mencukupi.</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($daftar_kritis as $sp): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <div class="fw-semibold text-light mb-0"><?= e($sp['nama_sparepart']) ?></div>
                                    <small class="font-mono text-muted"><?= e($sp['kode_sparepart']) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger text-white font-mono px-2 py-1">
                                        <?= (int)$sp['stok'] ?> <?= e($sp['satuan']) ?>
                                    </span>
                                    <div class="text-danger small" style="font-size: 0.725rem;">Min: <?= (int)$sp['stok_minimum'] ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
