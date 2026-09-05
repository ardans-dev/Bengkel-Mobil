<?php
/**
 * =====================================================================
 * FILE: includes/header.php
 * DESKRIPSI: Template Header & Navigasi Utama (Dark Theme & Minimalis)
 * =====================================================================
 * 
 * STRUKTUR:
 * 1. Brand Logo di sebelah kiri.
 * 2. 4 Pintu Navigasi: Dashboard, Kasir, Antrean & Servis, Data Master (Dropdown).
 * 3. Profil Kasir & Tombol Logout di sebelah kanan.
 */

$user_login   = current_user();
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil inisial nama untuk avatar bulat (misal: "Ahmad Yardan" -> "AY")
$nama_parts = explode(' ', trim($user_login['nama']));
$inisial = strtoupper(substr($nama_parts[0], 0, 1) . (isset($nama_parts[1]) ? substr($nama_parts[1], 0, 1) : ''));

// Cek apakah halaman aktif merupakan bagian dari Data Master
$is_master_active = in_array($current_page, ['pelanggan.php', 'kendaraan.php', 'sparepart.php', 'layanan.php', 'mekanik.php', 'pengaturan.php']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' — ' : '' ?>Bengkel Mobil Ardans</title>
    
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons 1.11.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    <!-- Stylesheet Desain Terpadu (Dark Theme) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- =====================================================================
         NAVBAR UTAMA
         ===================================================================== -->
    <header class="navbar-custom">
        <div class="container-xl navbar-inner">
            
            <!-- 1. Brand Logo & Identitas Bengkel -->
            <a href="dashboard.php" class="navbar-brand-logo">
                <div class="brand-icon-box">
                    <i class="bi bi-wrench-adjustable-circle-fill"></i>
                </div>
                <span>Bengkel<span class="text-info">Ardans</span></span>
            </a>

            <!-- 2. Menu Navigasi Sederhana -->
            <nav class="d-none d-lg-flex align-items-center nav-menu-group">
                <!-- 1. Dashboard -->
                <a href="dashboard.php" class="nav-link-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2"></i> Dashboard
                </a>

                <!-- 2. Kasir -->
                <a href="kasir.php" class="nav-link-item <?= $current_page == 'kasir.php' ? 'active' : '' ?>">
                    <i class="bi bi-cart-check"></i> Kasir
                </a>

                <!-- 3. Antrean & Servis -->
                <a href="transaksi.php" class="nav-link-item <?= in_array($current_page, ['transaksi.php', 'detail_transaksi.php']) ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i> Antrean & Servis
                </a>

                <!-- 4. Data Master (Dropdown Terpadu) -->
                <div class="dropdown">
                    <button class="nav-link-item dropdown-toggle border-0 bg-transparent <?= $is_master_active ? 'active' : '' ?>" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-database"></i> Data Master
                    </button>
                    <ul class="dropdown-menu nav-dropdown-menu">
                        <li>
                            <a class="dropdown-item <?= $current_page == 'pelanggan.php' ? 'active' : '' ?>" href="pelanggan.php">
                                <i class="bi bi-people text-primary me-2"></i>Data Pelanggan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= $current_page == 'kendaraan.php' ? 'active' : '' ?>" href="kendaraan.php">
                                <i class="bi bi-car-front text-info me-2"></i>Data Mobil Pasien
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item <?= $current_page == 'sparepart.php' ? 'active' : '' ?>" href="sparepart.php">
                                <i class="bi bi-box-seam text-warning me-2"></i>Stok Sparepart
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= $current_page == 'layanan.php' ? 'active' : '' ?>" href="layanan.php">
                                <i class="bi bi-tools text-success me-2"></i>Katalog Jasa Servis
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= $current_page == 'mekanik.php' ? 'active' : '' ?>" href="mekanik.php">
                                <i class="bi bi-person-badge text-danger me-2"></i>Data Montir / Mekanik
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item <?= $current_page == 'pengaturan.php' ? 'active' : '' ?>" href="pengaturan.php">
                                <i class="bi bi-gear text-info me-2"></i>Pengaturan Bengkel
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- 3. Sisi Kanan: Profil Kasir / Admin -->
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" target="_blank" class="btn btn-sm btn-outline-info text-decoration-none d-none d-sm-inline-flex align-items-center" title="Buka Landing Page Publik">
                    <i class="bi bi-globe2 me-1"></i> <span class="d-none d-md-inline">Website Publik</span>
                </a>
                <!-- Dropdown Profil Akun -->
                <div class="dropdown">
                    <a href="#" class="user-profile-pill dropdown-toggle text-decoration-none" data-bs-toggle="dropdown">
                        <div class="user-avatar-circle"><?= e($inisial) ?></div>
                        <span class="d-none d-md-inline small fw-semibold text-white"><?= e($user_login['nama']) ?></span>
                        <span class="badge bg-secondary text-white-50 ms-1 d-none d-md-inline" style="font-size: 0.65rem;"><?= e($user_login['role']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end nav-dropdown-menu mt-2">
                        <li class="px-3 py-2 text-white-50 small border-bottom border-secondary mb-1">
                            Login sebagai: <br>
                            <strong class="text-white"><?= e($user_login['nama']) ?></strong> (<?= e($user_login['username']) ?>)
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem bengkel?');">
                                <i class="bi bi-box-arrow-right text-danger me-2"></i>Keluar / Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </header>

    <!-- Sub-Navbar untuk Pengguna Mobile / Tablet Layar Kecil -->
    <div class="mobile-nav-bar d-lg-none">
        <a href="dashboard.php" class="btn-mobile <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="kasir.php" class="btn-mobile <?= $current_page == 'kasir.php' ? 'active' : '' ?>">Kasir</a>
        <a href="transaksi.php" class="btn-mobile <?= in_array($current_page, ['transaksi.php', 'detail_transaksi.php']) ? 'active' : '' ?>">Antrean</a>
        <a href="sparepart.php" class="btn-mobile <?= $current_page == 'sparepart.php' ? 'active' : '' ?>">Sparepart</a>
        <a href="pelanggan.php" class="btn-mobile <?= $current_page == 'pelanggan.php' ? 'active' : '' ?>">Pelanggan</a>
        <a href="kendaraan.php" class="btn-mobile <?= $current_page == 'kendaraan.php' ? 'active' : '' ?>">Mobil</a>
        <a href="layanan.php" class="btn-mobile <?= $current_page == 'layanan.php' ? 'active' : '' ?>">Jasa</a>
        <a href="mekanik.php" class="btn-mobile <?= $current_page == 'mekanik.php' ? 'active' : '' ?>">Mekanik</a>
    </div>

    <!-- Container Utama Konten Halaman -->
    <main class="container-xl my-4">
        <!-- Render notifikasi flash jika ada pesan sukses atau gagal -->
        <?php display_flash(); ?>
