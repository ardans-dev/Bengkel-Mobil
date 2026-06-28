<?php include 'cek_login.php';
include 'koneksi.php';
$id = $_GET['id'];
mysqli_query($koneksi, "DELETE FROM mekanik WHERE id_mekanik='$id'");
header("Location: data_mekanik.php");
exit;
?>
