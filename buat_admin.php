<?php
include 'koneksi.php';

$username = 'admin';
$password_asli = 'admin123';

// Fungsi sakti PHP untuk mengubah teks menjadi karakter acak yang tidak bisa dikembalikan
$password_acak = password_hash($password_asli, PASSWORD_DEFAULT);

// Hapus admin lama (jika ada) agar tidak dobel
mysqli_query($koneksi, "DELETE FROM admin WHERE username='$username'");

// Masukkan admin baru dengan password yang sudah diacak
$query = "INSERT INTO admin (username, password) VALUES ('$username', '$password_acak')";

if (mysqli_query($koneksi, $query)) {
    echo "Berhasil! Password acaknya adalah: <br> <strong>" . $password_acak . "</strong><br><br>";
    echo "Silakan hapus file ini jika sudah selesai.";
} else {
    echo "Gagal: " . mysqli_error($koneksi);
}
?>