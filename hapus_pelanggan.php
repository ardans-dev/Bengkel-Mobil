<?php include 'cek_login.php';
// Memanggil file koneksi
include 'koneksi.php';

// Menangkap ID yang dikirim melalui URL
$id = $_GET['id'];

// Menghapus data dari database
$hapus = mysqli_query($koneksi, "DELETE FROM pelanggan WHERE id_pelanggan='$id'");

// Mengalihkan kembali ke halaman utama data pelanggan
header("Location: data_pelanggan.php");
?>
