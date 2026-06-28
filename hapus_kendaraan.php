<?php include 'cek_login.php';
// Memanggil file koneksi
include 'koneksi.php';

// Menangkap ID kendaraan dari URL
$id = $_GET['id'];

// Query untuk menghapus data kendaraan
$hapus = mysqli_query($koneksi, "DELETE FROM kendaraan WHERE id_kendaraan='$id'");

// Mengalihkan kembali ke halaman utama data kendaraan
header("Location: data_kendaraan.php");
exit;
?>
