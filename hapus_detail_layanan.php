<?php include 'cek_login.php';
include 'koneksi.php';

$id_detail = $_GET['id_detail'];
$id_trx    = $_GET['id_trx'];

// Menggunakan id_detail_lyn sesuai database
$q_detail = mysqli_query($koneksi, "SELECT subtotal FROM detail_transaksi_layanan WHERE id_detail_lyn='$id_detail'");
$dt = mysqli_fetch_assoc($q_detail);
$subtotal_dikurangi = $dt['subtotal'];

mysqli_query($koneksi, "UPDATE transaksi SET total_biaya = total_biaya - $subtotal_dikurangi WHERE id_transaksi='$id_trx'");
mysqli_query($koneksi, "DELETE FROM detail_transaksi_layanan WHERE id_detail_lyn='$id_detail'");

header("Location: detail_transaksi.php?id=$id_trx");
exit;
?>
