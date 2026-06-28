<?php include 'cek_login.php';
include 'koneksi.php';
$id = $_GET['id'];
mysqli_query($koneksi, "DELETE FROM layanan WHERE id_layanan='$id'");
header("Location: data_layanan.php");
exit;
?>
