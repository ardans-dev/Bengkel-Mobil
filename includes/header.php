<?php
/**
 * =====================================================================
 * FILE: includes/header.php
 * DESKRIPSI: Template Atas (Header & Navigasi Utama)
 * =====================================================================
 * 
 * FUNGSI:
 * File ini dipanggil di baris awal setiap halaman utama aplikasi.
 * Berisi tag HTML <head>, link Bootstrap 5 CSS, Bootstrap Icons,
 * dan Navbar navigasi responsif dengan tombol Kasir, Master Data, serta Profil.
 */

// Ambil data kasir/admin yang sedang login saat ini
$user_login = current_user();

// Dapatkan nama file yang sedang dibuka untuk menandai menu aktif (Active Tab)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?>Bengkel Mobil Ardans</title>
    
    <!-- Bootstrap 5 CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9; /* Slate-100 halus */
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .nav-link {
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .nav-link.active {
            font-weight: 600;
            color: #38bdf8 !important; /* Biru terang untuk menu aktif */
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.05);
        }
        .btn {
            border-radius: 8px;
            font-weight: 500;
        }
        .badge {
            font-weight: 500;
        }
        footer {
            margin-top: auto;
        }
    </style>
</head>
<body>

    <!-- =====================================================================
         NAVBAR UTAMA BENGKEL
         ===================================================================== -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-2 sticky-top shadow-sm">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand d-flex items-center gap-2" href="index.php">
                <span class="text-primary"><i class="bi bi-gear-wide-connected"></i></span>
                <span>Bengkel<span class="text-info">Ardans</span></span>
            </a>

            <!-- Tombol Hamburger Mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menu Tautan -->
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-bold <?= $current_page == 'kasir.php' ? 'active' : '' ?>" href="kasir.php">
                            <i class="bi bi-cart-check-fill me-1"></i> Kasir POS
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'transaksi.php' ? 'active' : '' ?>" href="transaksi.php">
                            <i class="bi bi-receipt me-1"></i> Riwayat Servis
                        </a>
                    </li>
                    
                    <!-- Dropdown Master Data -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array($current_page, ['pelanggan.php', 'kendaraan.php', 'sparepart.php', 'layanan.php', 'mekanik.php']) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-database me-1"></i> Master Data
                        </a>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item" href="pelanggan.php"><i class="bi bi-people me-2"></i>Data Pelanggan</a></li>
                            <li><a class="dropdown-item" href="kendaraan.php"><i class="bi bi-car-front me-2"></i>Data Kendaraan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="sparepart.php"><i class="bi bi-tools me-2"></i>Stok Sparepart</a></li>
                            <li><a class="dropdown-item" href="layanan.php"><i class="bi bi-wrench-adjustable me-2"></i>Katalog Jasa</a></li>
                            <li><a class="dropdown-item" href="mekanik.php"><i class="bi bi-person-badge me-2"></i>Data Mekanik</a></li>
                        </ul>
                    </li>
                </ul>

                <!-- Profil Pengguna & Logout -->
                <div class="d-flex align-items-center gap-3">
                    <div class="text-white-50 small d-none d-md-block text-end">
                        <div class="text-white fw-bold"><?= e($user_login['nama']) ?></div>
                        <span class="badge bg-secondary text-uppercase" style="font-size: 0.65rem;"><?= e($user_login['role']) ?></span>
                    </div>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
                        <i class="bi bi-box-arrow-right me-1"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Container Utama Konten Halaman -->
    <main class="container my-4">
        <!-- Render pesan notifikasi flash jika ada -->
        <?php display_flash(); ?>
