<?php include 'cek_login.php';
include 'koneksi.php';
$id = $_GET['id'];
mysqli_query($koneksi, "DELETE FROM sparepart WHERE id_sparepart='$id'");
header("Location: data_sparepart.php");
exit;
?>
