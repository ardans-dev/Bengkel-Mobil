<?php
/**
 * =====================================================================
 * FILE: kasir.php
 * DESKRIPSI: Modul Kasir POS - Pendaftaran Servis Masuk (Work Order)
 * =====================================================================
 * 
 * ALUR PENGGUNA BARU (LANGKAH 1):
 * 1. Pelanggan datang membawa mobil ke bengkel.
 * 2. Kasir memilih plat nomor mobil pelanggan yang terdaftar.
 * 3. Kasir menugaskan montir/mekanik yang sedang stand by.
 * 4. Kasir mencatat keluhan kerusakan dan membuka nomor nota servis otomatis.
 * 5. Sistem langsung mengarahkan ke halaman rincian untuk input jasa & part.
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Kasir";

// =====================================================================
// 1. PROSES POST (PENDAFTARAN SERVIS BARU)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kendaraan = (int)($_POST['id_kendaraan'] ?? 0);
    $id_mekanik   = (int)($_POST['id_mekanik'] ?? 0);
    $kilometer    = (int)($_POST['kilometer'] ?? 0);
    $keluhan      = trim($_POST['keluhan'] ?? '');
    $id_admin     = (int)($_SESSION['user_id'] ?? 1);

    if ($id_kendaraan <= 0 || $id_mekanik <= 0 || empty($keluhan)) {
        set_flash('danger', 'Silakan lengkapi: Mobil pasien, montir mekanik, dan keluhan pelanggan!');
        redirect('kasir.php');
    }

    try {
        // Buat kode transaksi unik otomatis (Format: TRX-YYYYMMDD-001)
        $prefix = 'TRX-' . date('Ymd') . '-';
        $stmt_seq = db()->prepare("SELECT COUNT(*) AS total_today FROM transaksi WHERE kode_transaksi LIKE ?");
        $stmt_seq->execute([$prefix . '%']);
        $next_num = (int)$stmt_seq->fetch()['total_today'] + 1;
        $kode_transaksi = $prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);

        $stmt_insert = db()->prepare("
            INSERT INTO transaksi (
                kode_transaksi, id_kendaraan, id_mekanik, id_admin, kilometer, 
                keluhan, tanggal, status_servis, status_pembayaran
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Menunggu', 'Belum Lunas')
        ");
        $stmt_insert->execute([$kode_transaksi, $id_kendaraan, $id_mekanik, $id_admin, $kilometer, $keluhan]);

        $id_transaksi_baru = (int)db()->lastInsertId();

        set_flash('success', "Nota antrean baru <b>" . e($kode_transaksi) . "</b> berhasil dibuka! Silakan masukkan estimasi jasa & suku cadang.");
        redirect("detail_transaksi.php?id={$id_transaksi_baru}");

    } catch (PDOException $e) {
        set_flash('danger', 'Gagal membuat transaksi baru: ' . $e->getMessage());
        redirect('kasir.php');
    }
}

// Ambil daftar mobil beserta pemiliknya untuk dropdown
$kendaraan_options = db()->query("
    SELECT k.id_kendaraan, k.plat_nomor, k.merek, k.tipe, p.nama AS nama_pemilik, p.no_telepon 
    FROM kendaraan k 
    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan 
    ORDER BY k.plat_nomor ASC
")->fetchAll();

// Ambil daftar mekanik aktif
$mekanik_options = db()->query("
    SELECT id_mekanik, nama_mekanik, keahlian 
    FROM mekanik 
    WHERE status = 'Aktif' 
    ORDER BY nama_mekanik ASC
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Header Standar Terpadu -->
<div class="page-header-box">
    <div>
        <ul class="breadcrumb-custom">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li>/</li>
            <li class="active">Kasir</li>
        </ul>
        <h1 class="page-title mb-1">Kasir — Pendaftaran Servis Masuk</h1>
        <p class="page-subtitle mb-0">Catat mobil pasien yang baru datang, tunjuk montir bertugas, dan terbitkan nota servis baru.</p>
    </div>
    <div>
        <a href="transaksi.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history me-1"></i> Lihat Antrean Berjalan
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-plus text-primary"></i>
                    <span class="card-title mb-0">Formulir Pendaftaran Servis Pasien</span>
                </div>
                <span class="badge badge-info-subtle font-mono">
                    <i class="bi bi-calendar-event me-1"></i><?= date('d M Y') ?>
                </span>
            </div>
            
            <div class="card-body">
                <form action="kasir.php" method="POST">
                    
                    <!-- Langkah 1: Pilih Unit Mobil -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">1. PILIH KENDARAAN / MOBIL PASIEN <span class="text-danger">*</span></label>
                            <a href="kendaraan.php" class="small text-decoration-none fw-semibold">
                                <i class="bi bi-plus-circle me-1"></i>Mobil Belum Terdaftar? Klik Disini
                            </a>
                        </div>
                        <select name="id_kendaraan" class="form-select" required autofocus>
                            <option value="">-- Cari Plat Nomor Polisi atau Nama Pemilik --</option>
                            <?php foreach ($kendaraan_options as $k): ?>
                                <option value="<?= (int)$k['id_kendaraan'] ?>">
                                    [<?= e($k['plat_nomor']) ?>] <?= e($k['merek']) ?> <?= e($k['tipe']) ?> — Pemilik: <?= e($k['nama_pemilik']) ?> (<?= e($k['no_telepon']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Pilih data kendaraan yang sudah tersimpan di database bengkel.</small>
                    </div>

                    <!-- Langkah 2: Teknisi & Odometer -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">2. TEKNISI / MONTIR PENANGGUNG JAWAB <span class="text-danger">*</span></label>
                            <select name="id_mekanik" class="form-select" required>
                                <option value="">-- Pilih Montir Bertugas --</option>
                                <?php foreach ($mekanik_options as $m): ?>
                                    <option value="<?= (int)$m['id_mekanik'] ?>">
                                        <?= e($m['nama_mekanik']) ?> (<?= e($m['keahlian']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">3. KILOMETER ODOMETER (KM)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-speedometer2"></i></span>
                                <input type="number" name="kilometer" class="form-control font-mono" placeholder="Contoh: 45000" min="0">
                            </div>
                        </div>
                    </div>

                    <!-- Langkah 3: Keluhan Pasien -->
                    <div class="mb-4">
                        <label class="form-label">4. KELUHAN & MASALAH KENDARAAN <span class="text-danger">*</span></label>
                        <textarea name="keluhan" class="form-control" rows="3" placeholder="Tuliskan keluhan yang disampaikan oleh pelanggan (contoh: rem bunyi berdecit, ganti oli mesin 10W-40, AC tidak dingin)..." required></textarea>
                    </div>

                    <!-- Tombol Aksi Simpan -->
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="dashboard.php" class="btn btn-light border text-muted">
                            <i class="bi bi-arrow-left me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i> Buka Nota & Masukkan Biaya <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
