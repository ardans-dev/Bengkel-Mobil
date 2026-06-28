<?php include 'cek_login.php';
include 'koneksi.php';
$id_trx = $_GET['id'];

// Ambil data transaksi utama
$query_trx = mysqli_query($koneksi, "
    SELECT t.*, k.plat_nomor, k.merek, p.nama AS nama_pelanggan, m.nama_mekanik 
    FROM transaksi t
    JOIN kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN pelanggan p ON k.id_pelanggan = p.id_pelanggan
    JOIN mekanik m ON t.id_mekanik = m.id_mekanik
    WHERE t.id_transaksi = '$id_trx'
");
$trx = mysqli_fetch_assoc($query_trx);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Nota TRX-<?php include 'cek_login.php'; echo $trx['id_transaksi']; ?></title>
    <style>
        /* CSS Khusus untuk Print */
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 0; padding: 20px; color: #000; }
        .nota-container { max-width: 600px; margin: auto; border: 1px solid #ccc; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #000; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 24px; }
        .info-table { width: 100%; margin-bottom: 20px; font-size: 13px; }
        .info-table td { padding: 3px 0; }
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .item-table th, .item-table td { border-bottom: 1px solid #ddd; padding: 8px 4px; text-align: left; }
        .item-table th { background-color: #f9f9f9; }
        .text-right { text-align: right !important; }
        .total-row { font-weight: bold; font-size: 16px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; border-top: 2px dashed #000; padding-top: 10px;}
        
        /* Menyembunyikan elemen tak perlu saat print */
        @media print {
            body { padding: 0; }
            .nota-container { border: none; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="nota-container">
        <div class="header">
            <h2>BENGKEL MOBIL BUDI MUNDU</h2>
            <p style="margin: 5px 0;">Jl.Solo-Purwodadi Km 9.2<br>Telp: 0815-4832-9839</p>
        </div>
        
        <table class="info-table">
            <tr>
                <td width="20%"><strong>No. Nota</strong></td>
                <td width="30%">: TRX-<?php include 'cek_login.php'; echo $trx['id_transaksi']; ?></td>
                <td width="20%"><strong>Tanggal</strong></td>
                <td width="30%">: <?php include 'cek_login.php'; echo date('d-m-Y H:i', strtotime($trx['tanggal'])); ?></td>
            </tr>
            <tr>
                <td><strong>Pelanggan</strong></td>
                <td>: <?php include 'cek_login.php'; echo $trx['nama_pelanggan']; ?></td>
                <td><strong>Kendaraan</strong></td>
                <td>: <?php include 'cek_login.php'; echo $trx['plat_nomor']; ?> (<?php include 'cek_login.php'; echo $trx['merek']; ?>)</td>
            </tr>
            <tr>
                <td><strong>Mekanik</strong></td>
                <td>: <?php include 'cek_login.php'; echo $trx['nama_mekanik']; ?></td>
                <td><strong>Status</strong></td>
                <td>: <?php include 'cek_login.php'; echo $trx['status_servis']; ?></td>
            </tr>
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th>Deskripsi (Jasa & Sparepart)</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php include 'cek_login.php';
                // Query disesuaikan dengan nama kolom ID di databasemu
                $q_lyn = mysqli_query($koneksi, "
                    SELECT dl.subtotal, l.nama_layanan 
                    FROM detail_transaksi_layanan dl
                    JOIN layanan l ON dl.id_layanan = l.id_layanan
                    WHERE dl.id_transaksi = '$id_trx'
                ");
                while($lyn = mysqli_fetch_assoc($q_lyn)){
                ?>
                <tr>
                    <td>[Jasa] <?php include 'cek_login.php'; echo $lyn['nama_layanan']; ?></td>
                    <td class="text-right">1</td>
                    <td class="text-right">Rp <?php include 'cek_login.php'; echo number_format($lyn['subtotal'], 0, ',', '.'); ?></td>
                </tr>
                <?php include 'cek_login.php'; } ?>

                <?php include 'cek_login.php';
                // Query disesuaikan dengan nama kolom ID di databasemu
                $q_sp = mysqli_query($koneksi, "
                    SELECT ds.jumlah, ds.subtotal, s.nama_sparepart 
                    FROM detail_transaksi_sparepart ds
                    JOIN sparepart s ON ds.id_sparepart = s.id_sparepart
                    WHERE ds.id_transaksi = '$id_trx'
                ");
                while($sp = mysqli_fetch_assoc($q_sp)){
                ?>
                <tr>
                    <td>[Barang] <?php include 'cek_login.php'; echo $sp['nama_sparepart']; ?></td>
                    <td class="text-right"><?php include 'cek_login.php'; echo $sp['jumlah']; ?></td>
                    <td class="text-right">Rp <?php include 'cek_login.php'; echo number_format($sp['subtotal'], 0, ',', '.'); ?></td>
                </tr>
                <?php include 'cek_login.php'; } ?>
                
                <tr class="total-row">
                    <td colspan="2" class="text-right" style="padding-top: 15px;">TOTAL TAGIHAN :</td>
                    <td class="text-right" style="padding-top: 15px;">Rp <?php include 'cek_login.php'; echo number_format($trx['total_biaya'], 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p>Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.</p>
        </div>
    </div>
</body>
</html>
