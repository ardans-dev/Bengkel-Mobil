<?php
/**
 * =====================================================================
 * FILE: cetak_nota.php
 * DESKRIPSI: Template Cetak Nota / Invoice Resmi Servis Bengkel Mobil
 * =====================================================================
 * 
 * FITUR:
 * 1. Desain invoice bersih siap cetak ke printer kasir / thermal / kertas A4/A5.
 * 2. Menggunakan CSS Print Media (@media print) sehingga tombol navigasi
 *    otomatis hilang saat dicetak ke printer atau diexport ke PDF.
 */

require_once __DIR__ . '/includes/auth.php';

$id_transaksi = (int)($_GET['id'] ?? 0);

if ($id_transaksi <= 0) {
    die("ID Transaksi tidak valid.");
}

// Ambil data transaksi lengkap
$stmt = db()->prepare("
    SELECT t.*, 
           k.plat_nomor, k.merek, k.tipe, k.warna,
           p.nama AS nama_pelanggan, p.no_telepon, p.alamat AS alamat_pelanggan,
           m.nama_mekanik,
           a.nama_lengkap AS nama_kasir
    FROM transaksi t
    JOIN kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan
    JOIN mekanik m ON t.id_mekanik = m.id_mekanik
    LEFT JOIN admin a ON t.id_admin = a.id_admin
    WHERE t.id_transaksi = ?
");
$stmt->execute([$id_transaksi]);
$trx = $stmt->fetch();

if (!$trx) {
    die("Data transaksi tidak ditemukan.");
}

// Ambil data jasa
$stmt_jasa = db()->prepare("
    SELECT d.*, l.nama_layanan 
    FROM detail_transaksi_layanan d
    JOIN layanan l ON d.id_layanan = l.id_layanan
    WHERE d.id_transaksi = ?
");
$stmt_jasa->execute([$id_transaksi]);
$list_jasa = $stmt_jasa->fetchAll();

// Ambil data sparepart
$stmt_part = db()->prepare("
    SELECT d.*, s.nama_sparepart, s.satuan 
    FROM detail_transaksi_sparepart d
    JOIN sparepart s ON d.id_sparepart = s.id_sparepart
    WHERE d.id_transaksi = ?
");
$stmt_part->execute([$id_transaksi]);
$list_part = $stmt_part->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Nota - <?= e($trx['kode_transaksi']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            padding: 30px 10px;
        }
        .invoice-box {
            max-width: 750px;
            margin: auto;
            background: #ffffff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            border: 1px solid #e2e8f0;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .invoice-box {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Tombol Aksi Layar (Hilang saat diprint) -->
    <div class="max-w-md mx-auto mb-4 text-center no-print">
        <a href="detail_transaksi.php?id=<?= $id_transaksi ?>" class="btn btn-outline-secondary btn-sm me-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold">
            <i class="bi bi-printer me-1"></i> Cetak Dokumen / Simpan PDF
        </button>
    </div>

    <div class="invoice-box">
        <!-- Header Nota -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
            <div>
                <h3 class="fw-bold text-primary mb-1"><i class="bi bi-gear-wide-connected me-2"></i>BENGKEL MOBIL ARDANS</h3>
                <p class="small text-muted mb-0">Spesialis Perawatan Mesin, Kelistrikan, Kaki-kaki & AC Mobil</p>
                <p class="small text-muted mb-0">Jl. Sukajadi No. 128, Bandung • Telp/WA: 0812-3456-7890</p>
            </div>
            <div class="text-end">
                <span class="badge bg-dark font-mono fs-6 mb-1"><?= e($trx['kode_transaksi']) ?></span>
                <div class="small text-muted"><?= format_tanggal($trx['tanggal']) ?></div>
                <div class="small"><b>Kasir:</b> <?= e($trx['nama_kasir'] ?: 'Admin') ?></div>
            </div>
        </div>

        <!-- Info Pasien & Kendaraan -->
        <div class="row g-3 mb-4 small">
            <div class="col-6">
                <div class="text-muted fw-semibold">PELANGGAN:</div>
                <div class="fw-bold fs-6 text-dark"><?= e($trx['nama_pelanggan']) ?></div>
                <div><?= e($trx['no_telepon']) ?></div>
                <div class="text-muted"><?= e($trx['alamat_pelanggan'] ?: '-') ?></div>
            </div>
            <div class="col-6 text-end">
                <div class="text-muted fw-semibold">KENDARAAN:</div>
                <div class="fw-bold fs-6 font-mono text-primary"><?= e($trx['plat_nomor']) ?></div>
                <div><?= e($trx['merek']) ?> <?= e($trx['tipe']) ?> (<?= e($trx['warna'] ?: 'Standar') ?>)</div>
                <div><b>Odometer:</b> <?= number_format($trx['kilometer'], 0, ',', '.') ?> KM</div>
                <div><b>Mekanik:</b> <?= e($trx['nama_mekanik']) ?></div>
            </div>
        </div>

        <!-- Tabel Rincian Nota -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-2">Item Pekerjaan & Suku Cadang</th>
                        <th class="text-center" style="width: 80px;">Qty</th>
                        <th class="text-end" style="width: 140px;">Harga Satuan</th>
                        <th class="text-end pe-2" style="width: 140px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <!-- Jasa Servis -->
                    <?php if (!empty($list_jasa)): ?>
                        <tr class="table-light">
                            <td colspan="4" class="fw-bold ps-2 text-secondary">A. ONGKOS JASA & PERBAIKAN</td>
                        </tr>
                        <?php foreach ($list_jasa as $j): ?>
                            <tr>
                                <td class="ps-3"><?= e($j['nama_layanan']) ?></td>
                                <td class="text-center">1 Paket</td>
                                <td class="text-end"><?= rupiah($j['biaya_jasa']) ?></td>
                                <td class="text-end pe-2 fw-semibold"><?= rupiah($j['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Suku Cadang -->
                    <?php if (!empty($list_part)): ?>
                        <tr class="table-light">
                            <td colspan="4" class="fw-bold ps-2 text-secondary">B. SUKU CADANG / SPAREPART</td>
                        </tr>
                        <?php foreach ($list_part as $p): ?>
                            <tr>
                                <td class="ps-3"><?= e($p['nama_sparepart']) ?></td>
                                <td class="text-center"><?= (int)$p['jumlah'] ?> <?= e($p['satuan']) ?></td>
                                <td class="text-end"><?= rupiah($p['harga_satuan']) ?></td>
                                <td class="text-end pe-2 fw-semibold"><?= rupiah($p['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="small">
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Subtotal Jasa Servis:</td>
                        <td class="text-end pe-2"><?= rupiah($trx['total_jasa']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Subtotal Suku Cadang:</td>
                        <td class="text-end pe-2"><?= rupiah($trx['total_sparepart']) ?></td>
                    </tr>
                    <tr class="table-primary fw-bold fs-6">
                        <td colspan="3" class="text-end">TOTAL TAGIHAN:</td>
                        <td class="text-end pe-2 font-mono"><?= rupiah($trx['total_biaya']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end">Dibayar (<?= e($trx['metode_pembayaran']) ?>):</td>
                        <td class="text-end pe-2"><?= rupiah($trx['jumlah_bayar']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Kembalian:</td>
                        <td class="text-end pe-2 fw-bold text-success"><?= rupiah($trx['kembalian']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Status Pembayaran Lunas / Belum -->
        <div class="row align-items-center mb-4 small">
            <div class="col-8">
                <?php if ($trx['catatan_mekanik']): ?>
                    <div class="p-2 bg-light rounded border">
                        <b>Saran & Diagnosa Teknisi:</b><br>
                        <i><?= e($trx['catatan_mekanik']) ?></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-4 text-end">
                <?php if ($trx['status_pembayaran'] === 'Lunas'): ?>
                    <div class="border border-success text-success fw-bold p-2 text-center rounded fs-6">
                        ✓ LUNAS
                    </div>
                <?php else: ?>
                    <div class="border border-danger text-danger fw-bold p-2 text-center rounded fs-6">
                        BELUM LUNAS
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tanda Tangan & Ucapan Terima Kasih -->
        <div class="row pt-4 text-center small text-secondary">
            <div class="col-6">
                <div>Hormat Kami,</div>
                <div style="height: 60px;"></div>
                <div class="fw-bold text-dark">( <?= e($trx['nama_kasir'] ?: 'Petugas Kasir') ?> )</div>
            </div>
            <div class="col-6">
                <div>Pelanggan / Pemilik,</div>
                <div style="height: 60px;"></div>
                <div class="fw-bold text-dark">( <?= e($trx['nama_pelanggan']) ?> )</div>
            </div>
        </div>

        <div class="text-center text-muted small mt-4 pt-3 border-top" style="font-size: 0.75rem;">
            <i>Terima kasih atas kepercayaan Anda mempercayakan perawatan mobil Anda pada Bengkel Ardans!</i>
        </div>
    </div>

</body>
</html>
