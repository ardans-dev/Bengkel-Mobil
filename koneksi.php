<?php
$host     = "localhost";
$user     = "root";
$password = ""; // Default Laragon biasanya kosong
$database = "db_bengkel_mobil";

$koneksi = mysqli_connect("localhost", "root", "", "db_bengkel_mobil");

// Memeriksa koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>