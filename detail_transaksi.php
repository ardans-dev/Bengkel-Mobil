<?php
/**
 * =====================================================================
 * FILE: detail_transaksi.php
 * DESKRIPSI: Pusat Manajemen Work Order, Input Suku Cadang, Jasa Montir & Kasir
 * =====================================================================
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Detail Servis & Kasir";

$id_transaksi = (int)($_GET['id'] ?? 0);

if ($id_transaksi <= 0) {
    set_flash('danger', 'ID Transaksi tidak valid.');
    redirect('transaksi.php');
}

// =====================================================================
// 1. PROSES POST
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. Tambah Jasa
    if ($action === 'tambah_layanan') {
        $id_layanan = (int)($_POST['id_layanan'] ?? 0);

        if ($id_layanan <= 0) {
            set_flash('danger', 'Silakan pilih jasa servis dari katalog!');
            redirect("detail_transaksi.php?id={$id_transaksi}");
        }

        if ($id_layanan > 0) {
            db()->beginTransaction();
            try {
                $stmt_l = db()->prepare("SELECT biaya_jasa FROM layanan WHERE id_layanan = ?");
                $stmt_l->execute([$id_layanan]);
                $layanan = $stmt_l->fetch();

                if ($layanan) {
                    $biaya = (float)$layanan['biaya_jasa'];

                    $stmt_ins = db()->prepare("
                        INSERT INTO detail_transaksi_layanan (id_transaksi, id_layanan, biaya_jasa, subtotal)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt_ins->execute([$id_transaksi, $id_layanan, $biaya, $biaya]);

                    $stmt_upd = db()->prepare("
                        UPDATE transaksi 
                        SET total_jasa = total_jasa + ?, total_biaya = total_biaya + ?
                        WHERE id_transaksi = ?
                    ");
                    $stmt_upd->execute([$biaya, $biaya, $id_transaksi]);

                    db()->commit();
                    set_flash('success', 'Jasa servis berhasil ditambahkan.');
                }
            } catch (Exception $e) {
                db()->rollBack();
                set_flash('danger', 'Gagal menambahkan jasa: ' . $e->getMessage());
            }
        }
        redirect("detail_transaksi.php?id={$id_transaksi}");

    // B. Tambah Sparepart (Auto-Deduct Stock via ACID)
    } elseif ($action === 'tambah_sparepart') {
        $id_sparepart = (int)($_POST['id_sparepart'] ?? 0);
        $jumlah       = (int)($_POST['jumlah'] ?? 1);

        if ($id_sparepart <= 0 || $jumlah <= 0) {
            set_flash('danger', 'Silakan pilih sparepart dan kuantitas minimal 1 unit!');
            redirect("detail_transaksi.php?id={$id_transaksi}");
        }

        if ($id_sparepart > 0 && $jumlah > 0) {
            db()->beginTransaction();
            try {
                $stmt_sp = db()->prepare("SELECT nama_sparepart, stok, harga_jual FROM sparepart WHERE id_sparepart = ? FOR UPDATE");
                $stmt_sp->execute([$id_sparepart]);
                $part = $stmt_sp->fetch();

                if (!$part) {
                    throw new Exception("Suku cadang tidak ditemukan.");
                }

                if ($part['stok'] < $jumlah) {
                    throw new Exception("Stok tidak mencukupi! Sisa stok '{$part['nama_sparepart']}' hanya {$part['stok']} unit.");
                }

                $harga_satuan = (float)$part['harga_jual'];
                $subtotal     = $harga_satuan * $jumlah;

                $stmt_ins = db()->prepare("
                    INSERT INTO detail_transaksi_sparepart (id_transaksi, id_sparepart, harga_satuan, jumlah, subtotal)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_ins->execute([$id_transaksi, $id_sparepart, $harga_satuan, $jumlah, $subtotal]);

                $stmt_stok = db()->prepare("UPDATE sparepart SET stok = stok - ? WHERE id_sparepart = ?");
                $stmt_stok->execute([$jumlah, $id_sparepart]);

                $stmt_trx = db()->prepare("
                    UPDATE transaksi 
                    SET total_sparepart = total_sparepart + ?, total_biaya = total_biaya + ?
                    WHERE id_transaksi = ?
                ");
                $stmt_trx->execute([$subtotal, $subtotal, $id_transaksi]);

                db()->commit();
                set_flash('success', "Sparepart <b>" . e($part['nama_sparepart']) . "</b> ({$jumlah} unit) berhasil dipasang!");

            } catch (Exception $e) {
                db()->rollBack();
                set_flash('danger', $e->getMessage());
            }
        }
        redirect("detail_transaksi.php?id={$id_transaksi}");

    // C. Update Status Servis & Kasir Pembayaran
    } elseif ($action === 'update_servis_bayar') {
        $status_servis     = in_array($_POST['status_servis'] ?? '', ['Menunggu', 'Dikerjakan', 'Selesai', 'Dibatalkan']) ? $_POST['status_servis'] : 'Menunggu';
        $metode_pembayaran = in_array($_POST['metode_pembayaran'] ?? '', ['Tunai', 'Transfer Bank', 'QRIS']) ? $_POST['metode_pembayaran'] : 'Tunai';
        $jumlah_bayar      = max(0, (float)($_POST['jumlah_bayar'] ?? 0));
        $catatan_mekanik   = trim($_POST['catatan_mekanik'] ?? '');

        $stmt_total = db()->prepare("SELECT total_biaya FROM transaksi WHERE id_transaksi = ?");
        $stmt_total->execute([$id_transaksi]);
        $total_biaya = (float)$stmt_total->fetch()['total_biaya'];

        $kembalian = max(0, $jumlah_bayar - $total_biaya);
        $status_pembayaran = (($total_biaya == 0 && $status_servis === 'Selesai') || ($total_biaya > 0 && $jumlah_bayar >= $total_biaya)) ? 'Lunas' : 'Belum Lunas';

        try {
            $stmt_upd = db()->prepare("
                UPDATE transaksi 
                SET status_servis = ?, metode_pembayaran = ?, jumlah_bayar = ?, 
                    kembalian = ?, status_pembayaran = ?, catatan_mekanik = ?
                WHERE id_transaksi = ?
            ");
            $stmt_upd->execute([
                $status_servis, $metode_pembayaran, $jumlah_bayar, 
                $kembalian, $status_pembayaran, $catatan_mekanik, $id_transaksi
            ]);

            set_flash('success', 'Data servis dan pembayaran berhasil diperbarui!');
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal memperbarui kasir: ' . $e->getMessage());
        }
        redirect("detail_transaksi.php?id={$id_transaksi}");
    }
}

// =====================================================================
// 2. PROSES GET: HAPUS JASA / SPAREPART (PULIHKAN STOK)
// =====================================================================
if (isset($_GET['action'])) {
    $hapus_action = $_GET['action'];

    if ($hapus_action === 'hapus_layanan') {
        $id_item = (int)($_GET['item_id'] ?? 0);
        if ($id_item > 0) {
            db()->beginTransaction();
            try {
                $stmt_cek = db()->prepare("SELECT subtotal FROM detail_transaksi_layanan WHERE id_detail_lyn = ? AND id_transaksi = ?");
                $stmt_cek->execute([$id_item, $id_transaksi]);
                $item = $stmt_cek->fetch();

                if ($item) {
                    $subtotal = (float)$item['subtotal'];
                    db()->prepare("DELETE FROM detail_transaksi_layanan WHERE id_detail_lyn = ?")->execute([$id_item]);
                    db()->prepare("UPDATE transaksi SET total_jasa = GREATEST(0, total_jasa - ?), total_biaya = GREATEST(0, total_biaya - ?) WHERE id_transaksi = ?")->execute([$subtotal, $subtotal, $id_transaksi]);

                    db()->commit();
                    set_flash('success', 'Jasa berhasil dihapus dari nota.');
                }
            } catch (Exception $e) {
                db()->rollBack();
                set_flash('danger', 'Gagal membatalkan jasa: ' . $e->getMessage());
            }
        }
        redirect("detail_transaksi.php?id={$id_transaksi}");

    } elseif ($hapus_action === 'hapus_sparepart') {
        $id_item = (int)($_GET['item_id'] ?? 0);
        if ($id_item > 0) {
            db()->beginTransaction();
            try {
                $stmt_cek = db()->prepare("SELECT id_sparepart, jumlah, subtotal FROM detail_transaksi_sparepart WHERE id_detail_sp = ? AND id_transaksi = ?");
                $stmt_cek->execute([$id_item, $id_transaksi]);
                $item = $stmt_cek->fetch();

                if ($item) {
                    $id_sp    = (int)$item['id_sparepart'];
                    $qty      = (int)$item['jumlah'];
                    $subtotal = (float)$item['subtotal'];

                    // Kembalikan stok fisik ke gudang
                    db()->prepare("UPDATE sparepart SET stok = stok + ? WHERE id_sparepart = ?")->execute([$qty, $id_sp]);
                    db()->prepare("DELETE FROM detail_transaksi_sparepart WHERE id_detail_sp = ?")->execute([$id_item]);
                    db()->prepare("UPDATE transaksi SET total_sparepart = GREATEST(0, total_sparepart - ?), total_biaya = GREATEST(0, total_biaya - ?) WHERE id_transaksi = ?")->execute([$subtotal, $subtotal, $id_transaksi]);

                    db()->commit();
                    set_flash('success', "Suku cadang dibatalkan. Stok {$qty} unit dikembalikan ke gudang.");
                }
            } catch (Exception $e) {
                db()->rollBack();
                set_flash('danger', 'Gagal membatalkan sparepart: ' . $e->getMessage());
            }
        }
        redirect("detail_transaksi.php?id={$id_transaksi}");
    }
}

// =====================================================================
// 3. AMBIL DATA LENGKAP
// =====================================================================
$stmt_trx = db()->prepare("
    SELECT t.*, 
           k.plat_nomor, k.merek, k.tipe, k.warna,
           p.nama AS nama_pelanggan, p.no_telepon, p.alamat AS alamat_pelanggan,
           m.nama_mekanik, m.keahlian,
           a.nama_lengkap AS nama_kasir
    FROM transaksi t
    JOIN kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan
    JOIN mekanik m ON t.id_mekanik = m.id_mekanik
    LEFT JOIN admin a ON t.id_admin = a.id_admin
    WHERE t.id_transaksi = ?
");
$stmt_trx->execute([$id_transaksi]);
$trx = $stmt_trx->fetch();

if (!$trx) {
    set_flash('danger', 'Transaksi tidak ditemukan.');
    redirect('transaksi.php');
}

$stmt_items_lyn = db()->prepare("
    SELECT d.*, l.nama_layanan, l.kategori 
    FROM detail_transaksi_layanan d
    JOIN layanan l ON d.id_layanan = l.id_layanan
    WHERE d.id_transaksi = ?
    ORDER BY d.id_detail_lyn ASC
");
$stmt_items_lyn->execute([$id_transaksi]);
$list_jasa = $stmt_items_lyn->fetchAll();

$stmt_items_sp = db()->prepare("
    SELECT d.*, s.kode_sparepart, s.nama_sparepart, s.satuan 
    FROM detail_transaksi_sparepart d
    JOIN sparepart s ON d.id_sparepart = s.id_sparepart
    WHERE d.id_transaksi = ?
    ORDER BY d.id_detail_sp ASC
");
$stmt_items_sp->execute([$id_transaksi]);
$list_part = $stmt_items_sp->fetchAll();

$katalog_layanan   = db()->query("SELECT * FROM layanan ORDER BY nama_layanan ASC")->fetchAll();
$katalog_sparepart = db()->query("SELECT * FROM sparepart WHERE stok > 0 ORDER BY nama_sparepart ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Header Standar Terpadu -->
<div class="page-header-box">
    <div>
        <ul class="breadcrumb-custom">
            <li><a href="index.php">Dashboard</a></li>
            <li>/</li>
            <li><a href="transaksi.php">Antrean & Servis</a></li>
            <li>/</li>
            <li class="active"><?= e($trx['kode_transaksi']) ?></li>
        </ul>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h1 class="page-title mb-0">Rincian Servis & Kasir</h1>
            <span class="badge-plat"><?= e($trx['plat_nomor']) ?></span>
            <span class="badge bg-light text-dark border font-mono"><?= e($trx['kode_transaksi']) ?></span>
            <?php if ($trx['status_pembayaran'] === 'Lunas'): ?>
                <span class="badge badge-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>Lunas</span>
            <?php else: ?>
                <span class="badge badge-danger-subtle"><i class="bi bi-hourglass-split me-1"></i>Belum Lunas</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="transaksi.php" class="btn btn-light border btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Antrean
        </a>
        <a href="cetak_nota.php?id=<?= $id_transaksi ?>" target="_blank" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Cetak Invoice Nota
        </a>
    </div>
</div>

<!-- Kartu Ringkasan Pasien & Unit -->
<div class="card mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-3 border-end">
                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Kendaraan Pasien</small>
                <div class="fw-bold fs-6 font-mono text-primary"><?= e($trx['plat_nomor']) ?></div>
                <div class="small text-dark"><?= e($trx['merek']) ?> <?= e($trx['tipe']) ?></div>
                <div class="small text-muted"><i class="bi bi-speedometer2 me-1"></i><?= number_format($trx['kilometer'], 0, ',', '.') ?> KM</div>
            </div>
            <div class="col-md-3 border-end">
                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Pemilik Mobil</small>
                <div class="fw-semibold text-dark"><?= e($trx['nama_pelanggan']) ?></div>
                <div class="small text-success"><i class="bi bi-whatsapp me-1"></i><?= e($trx['no_telepon']) ?></div>
                <div class="small text-muted text-truncate"><?= e($trx['alamat_pelanggan'] ?: '-') ?></div>
            </div>
            <div class="col-md-3 border-end">
                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Petugas Bengkel</small>
                <div class="small"><b>Mekanik:</b> <?= e($trx['nama_mekanik']) ?></div>
                <div class="small"><b>Kasir:</b> <?= e($trx['nama_kasir'] ?: 'Admin') ?></div>
                <div class="small text-muted"><i class="bi bi-calendar3 me-1"></i><?= format_tanggal($trx['tanggal']) ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Keluhan Pelanggan</small>
                <div class="small text-secondary bg-light p-2 rounded border" style="max-height: 65px; overflow-y: auto;">
                    <i>"<?= e($trx['keluhan']) ?>"</i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Tabel Jasa & Sparepart -->
    <div class="col-lg-8">
        
        <!-- Tabel Jasa -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark fw-bold"><i class="bi bi-wrench me-2 text-primary"></i>Rincian Jasa Servis</h6>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalInputJasa">
                    <i class="bi bi-plus me-1"></i> Tambah Jasa
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Nama Layanan</th>
                                <th>Kategori</th>
                                <th class="text-end">Biaya Jasa</th>
                                <th class="text-center pe-4" style="width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_jasa)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted small">Belum ada jasa yang dipasang.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($list_jasa as $j): ?>
                                    <tr>
                                        <td class="ps-4 fw-medium text-dark"><?= e($j['nama_layanan']) ?></td>
                                        <td><span class="badge bg-light text-secondary border"><?= e($j['kategori']) ?></span></td>
                                        <td class="text-end fw-semibold font-mono"><?= rupiah($j['subtotal']) ?></td>
                                        <td class="text-center pe-4">
                                            <a href="detail_transaksi.php?id=<?= $id_transaksi ?>&action=hapus_layanan&item_id=<?= $j['id_detail_lyn'] ?>" 
                                               class="btn btn-outline-danger btn-sm p-1" onclick="return confirm('Hapus jasa ini?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="ps-4 fw-bold text-secondary">Subtotal Jasa Servis</td>
                                <td class="text-end fw-bold font-mono text-primary"><?= rupiah($trx['total_jasa']) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabel Sparepart -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark fw-bold"><i class="bi bi-tools me-2 text-primary"></i>Rincian Suku Cadang (Sparepart)</h6>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalInputPart">
                    <i class="bi bi-plus me-1"></i> Pasang Sparepart
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Nama Suku Cadang</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center pe-4" style="width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_part)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted small">Belum ada suku cadang yang dipasang.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($list_part as $p): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-secondary font-mono small me-1"><?= e($p['kode_sparepart']) ?></span>
                                            <span class="fw-medium text-dark"><?= e($p['nama_sparepart']) ?></span>
                                        </td>
                                        <td class="text-center"><?= (int)$p['jumlah'] ?> <?= e($p['satuan']) ?></td>
                                        <td class="text-end text-muted small font-mono"><?= rupiah($p['harga_satuan']) ?></td>
                                        <td class="text-end fw-semibold font-mono"><?= rupiah($p['subtotal']) ?></td>
                                        <td class="text-center pe-4">
                                            <a href="detail_transaksi.php?id=<?= $id_transaksi ?>&action=hapus_sparepart&item_id=<?= $p['id_detail_sp'] ?>" 
                                               class="btn btn-outline-danger btn-sm p-1" onclick="return confirm('Batalkan sparepart ini? Stok akan dikembalikan ke gudang.');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="ps-4 fw-bold text-secondary">Subtotal Suku Cadang</td>
                                <td class="text-end fw-bold font-mono text-primary"><?= rupiah($trx['total_sparepart']) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Kolom Kanan: Kasir POS Card -->
    <div class="col-lg-4">
        <div class="card sticky-top" style="top: 80px;">
            <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="bi bi-calculator me-2 text-warning"></i>Kasir Pembayaran</span>
                <span class="badge bg-secondary text-uppercase" style="font-size: 0.65rem;">POS Billing</span>
            </div>
            <div class="card-body p-3">
                
                <!-- Rangkuman Tagihan -->
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Total Jasa:</span>
                        <span class="font-mono"><?= rupiah($trx['total_jasa']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>Total Sparepart:</span>
                        <span class="font-mono"><?= rupiah($trx['total_sparepart']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="fw-bold text-dark">TOTAL TAGIHAN:</span>
                        <span class="fs-4 fw-bold text-primary font-mono"><?= rupiah($trx['total_biaya']) ?></span>
                    </div>
                </div>

                <form action="detail_transaksi.php?id=<?= $id_transaksi ?>" method="POST">
                    <input type="hidden" name="action" value="update_servis_bayar">

                    <div class="mb-3">
                        <label class="form-label">Status Pengerjaan Servis</label>
                        <select name="status_servis" class="form-select">
                            <option value="Menunggu" <?= $trx['status_servis'] == 'Menunggu' ? 'selected' : '' ?>>⏳ Menunggu Antrean</option>
                            <option value="Dikerjakan" <?= $trx['status_servis'] == 'Dikerjakan' ? 'selected' : '' ?>>🔧 Sedang Dikerjakan</option>
                            <option value="Selesai" <?= $trx['status_servis'] == 'Selesai' ? 'selected' : '' ?>>✅ Selesai Dikerjakan</option>
                            <option value="Dibatalkan" <?= $trx['status_servis'] == 'Dibatalkan' ? 'selected' : '' ?>>❌ Dibatalkan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-select">
                            <option value="Tunai" <?= $trx['metode_pembayaran'] == 'Tunai' ? 'selected' : '' ?>>💵 Tunai (Cash)</option>
                            <option value="Transfer Bank" <?= $trx['metode_pembayaran'] == 'Transfer Bank' ? 'selected' : '' ?>>🏦 Transfer Bank</option>
                            <option value="QRIS" <?= $trx['metode_pembayaran'] == 'QRIS' ? 'selected' : '' ?>>📱 QRIS / E-Wallet</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Uang Diterima Kasir (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="number" name="jumlah_bayar" id="input_bayar" class="form-control font-mono fs-5 text-end fw-bold" 
                                   value="<?= (int)$trx['jumlah_bayar'] ?>" min="0" step="1000" oninput="hitungKembalian()">
                        </div>
                    </div>

                    <div class="p-2 mb-3 bg-light rounded border text-center">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">UANG KEMBALIAN:</small>
                        <div class="fs-5 fw-bold text-success font-mono" id="display_kembalian">
                            <?= rupiah($trx['kembalian']) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan / Diagnosa Mekanik</label>
                        <textarea name="catatan_mekanik" class="form-control small" rows="2" placeholder="Catatan part atau saran servis berkala..."><?= e($trx['catatan_mekanik']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Pembayaran
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- Modal Jasa -->
<div class="modal fade" id="modalInputJasa" tabindex="-1">
    <div class="modal-dialog">
        <form action="detail_transaksi.php?id=<?= $id_transaksi ?>" method="POST" class="modal-content">
            <input type="hidden" name="action" value="tambah_layanan">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-wrench text-primary me-2"></i>Tambah Jasa ke Nota</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Pilih Katalog Jasa</label>
                    <select name="id_layanan" class="form-select" required>
                        <option value="">-- Pilih Jasa Servis --</option>
                        <?php foreach ($katalog_layanan as $l): ?>
                            <option value="<?= (int)$l['id_layanan'] ?>">
                                [<?= e($l['kode_layanan']) ?>] <?= e($l['nama_layanan']) ?> — <?= rupiah($l['biaya_jasa']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> Pasang Jasa</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sparepart -->
<div class="modal fade" id="modalInputPart" tabindex="-1">
    <div class="modal-dialog">
        <form action="detail_transaksi.php?id=<?= $id_transaksi ?>" method="POST" class="modal-content">
            <input type="hidden" name="action" value="tambah_sparepart">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-tools text-primary me-2"></i>Pasang Suku Cadang ke Nota</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Pilih Suku Cadang Gudang</label>
                    <select name="id_sparepart" class="form-select" required>
                        <option value="">-- Pilih Barang (Stok Tersedia) --</option>
                        <?php foreach ($katalog_sparepart as $s): ?>
                            <option value="<?= (int)$s['id_sparepart'] ?>">
                                <?= e($s['nama_sparepart']) ?> (Stok: <?= (int)$s['stok'] ?> <?= e($s['satuan']) ?>) — <?= rupiah($s['harga_jual']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Kuantitas (Qty)</label>
                    <input type="number" name="jumlah" class="form-control" value="1" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i> Pasang Barang</button>
            </div>
        </form>
    </div>
</div>

<script>
const totalBiaya = <?= (float)$trx['total_biaya'] ?>;
function hitungKembalian() {
    const bayar = parseFloat(document.getElementById('input_bayar').value) || 0;
    const kembalian = Math.max(0, bayar - totalBiaya);
    document.getElementById('display_kembalian').innerText = 'Rp ' + kembalian.toLocaleString('id-ID');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
