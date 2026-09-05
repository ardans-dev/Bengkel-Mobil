<?php
/**
 * =====================================================================
 * FILE: kasir.php
 * DESKRIPSI: Modul Kasir POS - Pendaftaran Servis Masuk (Work Order)
 * =====================================================================
 * 
 * ALUR KASIR:
 * 1. Kasir memilih mobil pelanggan yang datang (lengkap dengan plat nomor & nama pemilik).
 * 2. Kasir memilih mekanik yang bertugas dan mencatat kilometer serta keluhan mobil.
 * 3. Sistem secara otomatis men-generate Nomor Invoice Unik (Format: TRX-YYYYMMDD-XXXX).
 * 4. Transaksi disimpan dengan status awal 'Menunggu', lalu kasir langsung diarahkan
 *    ke halaman detail_transaksi.php untuk mulai menginput suku cadang dan jasa montir.
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Kasir POS - Servis Masuk";

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
        set_flash('danger', 'Silakan pilih kendaraan, mekanik, dan isi keluhan pelanggan!');
        redirect('kasir.php');
    }

    try {
        // GENERATE KODE TRANSAKSI OTOMATIS:
        // Format: TRX-YYYYMMDD-0001
        $prefix = 'TRX-' . date('Ymd') . '-';
        $stmt_seq = db()->prepare("SELECT COUNT(*) AS total_today FROM transaksi WHERE kode_transaksi LIKE ?");
        $stmt_seq->execute([$prefix . '%']);
        $next_num = (int)$stmt_seq->fetch()['total_today'] + 1;
        $kode_transaksi = $prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);

        // Simpan nota servis induk ke database via Prepared Statement
        $stmt_insert = db()->prepare("
            INSERT INTO transaksi (
                kode_transaksi, id_kendaraan, id_mekanik, id_admin, kilometer, 
                keluhan, tanggal, status_servis, status_pembayaran
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Menunggu', 'Belum Lunas')
        ");
        $stmt_insert->execute([$kode_transaksi, $id_kendaraan, $id_mekanik, $id_admin, $kilometer, $keluhan]);

        // Dapatkan ID transaksi yang baru saja digenerate oleh database (Last Insert ID)
        $id_transaksi_baru = (int)db()->lastInsertId();

        set_flash('success', "Nota servis baru <b>" . e($kode_transaksi) . "</b> berhasil dibuka! Silakan masukkan jasa dan sparepart.");
        // Arahkan kasir langsung ke halaman pengisian rincian biaya
        redirect("detail_transaksi.php?id={$id_transaksi_baru}");

    } catch (PDOException $e) {
        set_flash('danger', 'Gagal membuat transaksi baru: ' . $e->getMessage());
        redirect('kasir.php');
    }
}

// =====================================================================
// 2. AMBIL DATA KENDARAAN & MEKANIK UNTUK DROPDOWN
// =====================================================================
// Gabungkan data kendaraan dan nama pemiliknya
$kendaraan_options = db()->query("
    SELECT k.id_kendaraan, k.plat_nomor, k.merek, k.tipe, p.nama AS nama_pemilik, p.no_telepon 
    FROM kendaraan k 
    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan 
    ORDER BY k.plat_nomor ASC
")->fetchAll();

// Ambil hanya mekanik yang berstatus Aktif
$mekanik_options = db()->query("
    SELECT id_mekanik, nama_mekanik, keahlian 
    FROM mekanik 
    WHERE status = 'Aktif' 
    ORDER BY nama_mekanik ASC
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Kartu Utama Form Kasir -->
        <div class="card shadow border-0 overflow-hidden">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-cart-check-fill me-2"></i>Pendaftaran Servis Baru (Work Order)</h5>
                    <span class="badge bg-white text-primary fw-semibold"><i class="bi bi-clock me-1"></i><?= date('d M Y') ?></span>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="kasir.php" method="POST">
                    
                    <!-- Pilihan Unit Kendaraan Pasien -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Pilih Kendaraan Pasien <span class="text-danger">*</span>
                        </label>
                        <select name="id_kendaraan" class="form-select form-select-lg" required autofocus>
                            <option value="">-- Cari Plat Nomor / Nama Pemilik Mobil --</option>
                            <?php foreach ($kendaraan_options as $k): ?>
                                <option value="<?= (int)$k['id_kendaraan'] ?>">
                                    [<?= e($k['plat_nomor']) ?>] <?= e($k['merek']) ?> <?= e($k['tipe']) ?> — Milik: <?= e($k['nama_pemilik']) ?> (<?= e($k['no_telepon']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Mobil belum terdaftar? <a href="kendaraan.php" class="text-decoration-none fw-semibold">Daftarkan mobil baru di sini</a>.
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Pilihan Teknisi Mekanik -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">
                                Mekanik Penanggung Jawab <span class="text-danger">*</span>
                            </label>
                            <select name="id_mekanik" class="form-select" required>
                                <option value="">-- Pilih Montir Aktif --</option>
                                <?php foreach ($mekanik_options as $m): ?>
                                    <option value="<?= (int)$m['id_mekanik'] ?>">
                                        <?= e($m['nama_mekanik']) ?> (<?= e($m['keahlian']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Odometer / Kilometer Mobil Masuk -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">
                                Odometer Spedometer Saat Masuk (KM)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-speedometer text-muted"></i></span>
                                <input type="number" name="kilometer" class="form-control font-mono" placeholder="Contoh: 45000" min="0">
                            </div>
                        </div>
                    </div>

                    <!-- Keluhan Pelanggan -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Keluhan Utama & Masalah Kendaraan <span class="text-danger">*</span>
                        </label>
                        <textarea name="keluhan" class="form-control" rows="3" placeholder="Deskripsikan masalah mobil menurut pengakuan pelanggan (misal: mesin brebet saat digas, AC tidak dingin, bunyi berdecit di rem roda depan)..." required></textarea>
                    </div>

                    <!-- Tombol Eksekusi -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> Buat Nota & Input Biaya <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
