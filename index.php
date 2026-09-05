<?php
/**
 * =====================================================================
 * FILE: index.php
 * DESKRIPSI: Landing Page Publik Resmi Bengkel Mobil Ardans
 * =====================================================================
 * 
 * FITUR UTAMA:
 * 1. Profil & Fasilitas Bengkel Ardans
 * 2. Status Buka/Tutup/Libur Real-Time & Otomatis (08:00 - 16:00 WIB, Minggu & Tanggal Merah Libur)
 * 3. Katalog Jasa Servis Dinamis dari Database
 * 4. Pelacak Status Servis Mandiri (PRIVACY-SAFE: Tanpa Data Pemilik & Plat)
 * 5. Showcase Brand Suku Cadang & Partner OEM (Toyota, Daihatsu, Honda, Shell, dll.)
 * 6. Alamat Lengkap, Kontak Resmi, Tombol WhatsApp Langsung, & Google Maps
 * 7. Menu Login Khusus Admin di Footer Bawah (Bukan di Navbar)
 */

require_once __DIR__ . '/config/database.php';

// Ambil Konfigurasi Bengkel & Status Operasional Terkini
$cfg = get_pengaturan_bengkel();
$live_status = cek_status_bengkel();

// Ambil Daftar Layanan Unggulan dari Database
$daftar_layanan = [];
try {
    $stmt = db()->query("
        SELECT kode_layanan, nama_layanan, kategori, biaya_jasa, estimasi_waktu 
        FROM layanan 
        ORDER BY kategori ASC, nama_layanan ASC
    ");
    $daftar_layanan = $stmt->fetchAll();
} catch (PDOException $e) {
    $daftar_layanan = [];
}

// Logika Pencarian Status Servis Mandiri (Privacy-Safe)
$search_nota  = trim($_GET['nota'] ?? '');
$hasil_servis = null;
$search_error = null;

if (!empty($search_nota)) {
    try {
        // PERHATIAN: Hanya mengambil status pengerjaan & pembayaran,
        // DILARANG meng-join atau menampilkan nama pelanggan, no telepon, maupun plat nomor!
        $stmt = db()->prepare("
            SELECT 
                kode_transaksi,
                tanggal,
                status_servis,
                status_pembayaran,
                catatan_mekanik
            FROM transaksi
            WHERE kode_transaksi = :nota
            LIMIT 1
        ");
        $stmt->execute([':nota' => $search_nota]);
        $hasil_servis = $stmt->fetch();

        if (!$hasil_servis) {
            $search_error = "Nomor nota \"" . e($search_nota) . "\" tidak ditemukan. Pastikan nomor nota sesuai dengan surat pendaftaran servis Anda (contoh: TRX-20260905-001).";
        }
    } catch (PDOException $e) {
        $search_error = "Terjadi kendala saat memeriksa sistem pelacakan. Silakan coba sesaat lagi.";
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($cfg['nama_bengkel']) ?> - Perawatan & Perbaikan Mobil Terpercaya</title>
    
    <!-- Meta SEO -->
    <meta name="description" content="<?= e($cfg['slogan']) ?>. Spesialis Tune Up Injeksi, Ganti Oli, Rem, AC, dan Overhaul Mesin bergaransi.">
    <link rel="icon" type="image/png" href="assets/img/logo.png">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Design System CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-dark text-light" style="font-family: var(--font-sans);">

    <!-- =====================================================================
         1. BANNER PENGUMUMAN KHUSUS (Jika Ada)
         ===================================================================== -->
    <?php if (!empty($cfg['pesan_pengumuman'])): ?>
        <div class="alert alert-warning border-0 rounded-0 py-2 px-3 text-center mb-0 small fw-semibold">
            <i class="bi bi-megaphone-fill me-2"></i>
            <strong>Pemberitahuan Khusus:</strong> <?= e($cfg['pesan_pengumuman']) ?>
        </div>
    <?php endif; ?>

    <!-- =====================================================================
         2. NAVBAR PUBLIK (Murni untuk Pengunjung - Tanpa Menu Login Admin)
         ===================================================================== -->
    <header class="landing-header py-2">
        <div class="container-xl d-flex align-items-center justify-content-between">
            
            <!-- Logo & Nama Bengkel -->
            <a href="index.php" class="landing-brand-logo">
                <img src="assets/img/logo.png" alt="Logo <?= e($cfg['nama_bengkel']) ?>" class="landing-brand-img">
                <div>
                    <span class="text-white fw-bold">Bengkel<span class="text-info">Ardans</span></span>
                    <div class="d-none d-sm-block text-secondary" style="font-size: 0.7rem; font-weight: 500; line-height: 1;">Auto Service & Repair</div>
                </div>
            </a>

            <!-- Menu Navigasi Publik (Desktop) -->
            <nav class="d-none d-lg-flex align-items-center gap-1">
                <a href="#beranda" class="landing-nav-link">Beranda</a>
                <a href="#layanan" class="landing-nav-link">Layanan</a>
                <a href="#cek-servis" class="landing-nav-link">Cek Status Servis</a>
                <a href="#brand" class="landing-nav-link">Brand Sparepart</a>
                <a href="#profil" class="landing-nav-link">Profil Bengkel</a>
                <a href="#kontak" class="landing-nav-link">Lokasi & Kontak</a>
            </nav>

            <!-- Sisi Kanan: Status Buka Real-Time & Tombol WA -->
            <div class="d-flex align-items-center gap-3">
                
                <!-- Live Status Buka / Tutup Badge -->
                <?php if ($live_status['status'] === 'buka'): ?>
                    <span class="status-pill status-pill-open d-none d-md-inline-flex" title="<?= e($live_status['sublabel']) ?>">
                        <span class="status-pulse-dot"></span> Buka (08:00 - 16:00)
                    </span>
                <?php elseif ($live_status['status'] === 'libur'): ?>
                    <span class="status-pill status-pill-holiday d-none d-md-inline-flex" title="<?= e($live_status['sublabel']) ?>">
                        <span class="status-pulse-dot"></span> <?= e($live_status['label']) ?>
                    </span>
                <?php else: ?>
                    <span class="status-pill status-pill-closed d-none d-md-inline-flex" title="<?= e($live_status['sublabel']) ?>">
                        <span class="status-pulse-dot"></span> Sedang Tutup
                    </span>
                <?php endif; ?>

                <!-- Tombol Konsultasi WhatsApp Cepat -->
                <a href="https://wa.me/<?= e($cfg['no_whatsapp']) ?>?text=Halo%20Bengkel%20Ardans,%20saya%20ingin%20konsultasi%20servis%20mobil%20saya" target="_blank" class="btn btn-sm btn-success fw-semibold px-3 py-2 d-inline-flex align-items-center gap-1 rounded-pill shadow-sm">
                    <i class="bi bi-whatsapp"></i>
                    <span class="d-none d-sm-inline">Konsultasi WA</span>
                </a>

            </div>

        </div>
    </header>

    <!-- Sub-Navbar Mobile (Sticky Quick Navigation) -->
    <div class="d-flex d-lg-none bg-surface border-bottom border-secondary overflow-x-auto py-2 px-3 gap-2 text-nowrap" style="scrollbar-width: none;">
        <a href="#beranda" class="btn btn-sm btn-outline-secondary py-1 px-2 text-white">Beranda</a>
        <a href="#layanan" class="btn btn-sm btn-outline-secondary py-1 px-2 text-white">Layanan</a>
        <a href="#cek-servis" class="btn btn-sm btn-outline-info py-1 px-2">🔍 Cek Servis</a>
        <a href="#brand" class="btn btn-sm btn-outline-secondary py-1 px-2 text-white">Brand</a>
        <a href="#profil" class="btn btn-sm btn-outline-secondary py-1 px-2 text-white">Profil</a>
        <a href="#kontak" class="btn btn-sm btn-outline-secondary py-1 px-2 text-white">Kontak</a>
    </div>

    <main>
        <!-- =====================================================================
             3. HERO SECTION (Selamat Datang & Profil Singkat)
             ===================================================================== -->
        <section id="beranda" class="hero-wrapper">
            <div class="container-xl">
                <div class="row align-items-center g-4">
                    
                    <div class="col-lg-7">
                        <div class="hero-badge">
                            <i class="bi bi-shield-fill-check"></i>
                            <span>Bengkel Perawatan & Perbaikan Mobil Terpercaya</span>
                        </div>

                        <h1 class="hero-title">
                            Solusi Terpercaya Perawatan & Perbaikan <span class="text-info">Mobil Anda</span>
                        </h1>

                        <p class="hero-subtitle">
                            Layanan servis berkala, tune up injeksi, ganti oli mesin, perbaikan sistem pengereman, kelistrikan, hingga overhaul mesin bergaransi. Ditangani teknisi bersertifikasi dengan suku cadang 100% original.
                        </p>

                        <!-- Baris Status Jam Operasional Hari Ini -->
                        <div class="d-inline-flex flex-wrap align-items-center gap-2 p-2 px-3 rounded-pill bg-surface border border-secondary mb-4 small">
                            <span class="badge <?= $live_status['badge_class'] ?> rounded-pill px-3 py-1">
                                <i class="bi <?= $live_status['badge_icon'] ?> me-1"></i><?= e($live_status['label']) ?>
                            </span>
                            <span class="text-white-50">
                                Jam Kerja: <strong class="text-white"><?= substr($cfg['jam_buka'], 0, 5) ?> - <?= substr($cfg['jam_tutup'], 0, 5) ?> WIB</strong> (Minggu & Tgl Merah Libur)
                            </span>
                        </div>

                        <!-- Tombol CTA Utama -->
                        <div class="d-flex flex-wrap gap-3">
                            <a href="#cek-servis" class="btn btn-primary px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow">
                                <i class="bi bi-search"></i> Cek Status Servis
                            </a>
                            <a href="https://wa.me/<?= e($cfg['no_whatsapp']) ?>?text=Halo%20Bengkel%20Ardans,%20saya%20ingin%20booking%20jadwal%20servis%20mobil" target="_blank" class="btn btn-outline-success px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                                <i class="bi bi-calendar2-check"></i> Booking Servis via WA
                            </a>
                            <a href="#layanan" class="btn btn-outline-secondary px-4 py-2 text-white">
                                Lihat Jasa Servis
                            </a>
                        </div>

                        <!-- Metrik / Keunggulan Cepat -->
                        <div class="row g-3 mt-4 pt-3 border-top border-secondary">
                            <div class="col-4">
                                <h4 class="fw-bold text-info mb-0 font-monospace">5.000+</h4>
                                <span class="small text-secondary">Mobil Tertangani</span>
                            </div>
                            <div class="col-4">
                                <h4 class="fw-bold text-success mb-0 font-monospace">100%</h4>
                                <span class="small text-secondary">Original Parts</span>
                            </div>
                            <div class="col-4">
                                <h4 class="fw-bold text-warning mb-0 font-monospace">Bergaransi</h4>
                                <span class="small text-secondary">Jaminan Servis</span>
                            </div>
                        </div>

                    </div>

                    <div class="col-lg-5 text-center">
                        <div class="position-relative d-inline-block">
                            <img src="assets/img/logo.png" alt="Emblem Bengkel Ardans" class="hero-logo-img img-fluid">
                            <div class="position-absolute bottom-0 start-50 translate-middle-x mb-n3 px-3 py-1 rounded-pill bg-dark border border-info text-info small fw-bold shadow">
                                <i class="bi bi-patch-check-fill me-1"></i> Certified Workshop
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- =====================================================================
             4. WIDGET PELACAK STATUS SERVIS (PRIVACY-SAFE: NO PII/CUSTOMER DATA)
             ===================================================================== -->
        <section id="cek-servis" class="py-5" style="background: #090e1b; border-bottom: 1px solid var(--color-border);">
            <div class="container-xl">
                
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-8 text-center">
                        <span class="badge bg-primary-subtle text-info border border-info-subtle px-3 py-2 rounded-pill mb-2">
                            <i class="bi bi-clock-history me-1"></i> Live Service Tracker
                        </span>
                        <h2 class="h3 fw-bold text-white mb-2">Pantau Status Pengerjaan Mobil Anda</h2>
                        <p class="text-secondary small">
                            Cukup masukkan <strong>Nomor Nota Transaksi</strong> untuk mengetahui progres servis kendaraan secara real-time.
                        </p>
                        
                        <!-- Jaminan Privasi Pelanggan -->
                        <div class="d-inline-flex align-items-center gap-2 p-2 px-3 rounded bg-surface border border-secondary text-info small">
                            <i class="bi bi-shield-lock-fill text-warning fs-6"></i>
                            <span><strong>Privasi Terjamin:</strong> Sistem ini tidak menampilkan nama pemilik, nomor telepon, maupun plat nomor demi keamanan data pribadi Anda.</span>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        
                        <!-- Form Pencarian Nota -->
                        <div class="tracker-box p-4 mb-4">
                            <form action="index.php#cek-servis" method="GET" class="row g-2 align-items-center">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <span class="input-group-text bg-dark border-secondary text-info">
                                            <i class="bi bi-receipt"></i>
                                        </span>
                                        <input type="text" name="nota" class="form-control form-control-lg bg-dark text-white border-secondary" placeholder="Masukkan ID Nota (Contoh: TRX-20260905-001)" value="<?= e($search_nota) ?>" required autofocus>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-search"></i> Cek Status
                                    </button>
                                </div>
                            </form>

                            <!-- Rekomendasi Contoh Cepat untuk Pengunjung / Penguji -->
                            <div class="mt-2 text-secondary small">
                                <i class="bi bi-lightbulb text-warning me-1"></i> Ingin coba cek? Klik contoh nota: 
                                <a href="index.php?nota=TRX-20260905-001#cek-servis" class="text-info text-decoration-none font-monospace">TRX-20260905-001</a> | 
                                <a href="index.php?nota=TRX-20260905-002#cek-servis" class="text-info text-decoration-none font-monospace">TRX-20260905-002</a>
                            </div>
                        </div>

                        <!-- Pesan Error / Nota Tidak Ditemukan -->
                        <?php if ($search_error): ?>
                            <div class="alert alert-danger border-danger-subtle bg-danger-subtle text-danger-emphasis d-flex align-items-center gap-3 p-3 rounded-3 shadow-sm">
                                <i class="bi bi-exclamation-octagon-fill fs-3"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Nota Tidak Ditemukan</h6>
                                    <div class="small"><?= $search_error ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Hasil Pencarian: Tampilan Status Pengerjaan (Tanpa Data Pelanggan) -->
                        <?php if ($hasil_servis): ?>
                            <?php
                            $status = $hasil_servis['status_servis'];
                            $bayar  = $hasil_servis['status_pembayaran'];

                            // Hitung State Stepper
                            $step1_class = 'completed'; // Diterima selalu completed jika nota terbit
                            $step2_class = ($status === 'Dikerjakan') ? 'active' : (($status === 'Selesai') ? 'completed' : '');
                            $step3_class = ($status === 'Selesai') ? 'completed' : '';

                            if ($status === 'Menunggu') {
                                $step1_class = 'active';
                            }
                            ?>

                            <div class="card bg-surface border-info shadow-lg overflow-hidden">
                                <div class="card-header bg-dark border-secondary py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="text-secondary small">Nomor Nota:</span>
                                        <h5 class="fw-bold text-white mb-0 font-monospace"><?= e($hasil_servis['kode_transaksi']) ?></h5>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge <?= $status === 'Selesai' ? 'bg-success' : ($status === 'Dikerjakan' ? 'bg-primary' : ($status === 'Batal' ? 'bg-danger' : 'bg-warning text-dark')) ?> px-3 py-2 fs-6">
                                            Status: <?= e($status) ?>
                                        </span>
                                        <span class="badge <?= $bayar === 'Lunas' ? 'bg-success' : 'bg-danger' ?> px-3 py-2 fs-6">
                                            <?= e($bayar) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body p-4">
                                    
                                    <!-- Visual Stepper Progress Bar -->
                                    <div class="tracker-stepper">
                                        <!-- Step 1 -->
                                        <div class="tracker-step <?= $step1_class ?>">
                                            <div class="tracker-step-icon">
                                                <i class="bi <?= $step1_class === 'completed' ? 'bi-check-lg' : 'bi-card-checklist' ?>"></i>
                                            </div>
                                            <div class="tracker-step-title">1. Mobil Diterima</div>
                                            <small class="text-secondary">Antrean Terdaftar</small>
                                        </div>

                                        <!-- Step 2 -->
                                        <div class="tracker-step <?= $step2_class ?>">
                                            <div class="tracker-step-icon">
                                                <i class="bi <?= $step2_class === 'completed' ? 'bi-check-lg' : 'bi-wrench-adjustable' ?>"></i>
                                            </div>
                                            <div class="tracker-step-title">2. Dikerjakan Mekanik</div>
                                            <small class="text-secondary">Proses Servis & Part</small>
                                        </div>

                                        <!-- Step 3 -->
                                        <div class="tracker-step <?= $step3_class ?>">
                                            <div class="tracker-step-icon">
                                                <i class="bi <?= $step3_class === 'completed' ? 'bi-check2-all' : 'bi-flag-fill' ?>"></i>
                                            </div>
                                            <div class="tracker-step-title">3. Servis Selesai</div>
                                            <small class="text-secondary">Siap Diambil</small>
                                        </div>
                                    </div>

                                    <hr class="border-secondary my-4">

                                    <!-- Detail Pengerjaan Teknis (Strictly No Customer Data) -->
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="p-3 rounded bg-dark border border-secondary">
                                                <span class="text-secondary small d-block mb-1">
                                                    <i class="bi bi-calendar-event me-1"></i> Tanggal Masuk Servis:
                                                </span>
                                                <strong class="text-white"><?= format_tanggal($hasil_servis['tanggal']) ?></strong>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="p-3 rounded bg-dark border border-secondary">
                                                <span class="text-secondary small d-block mb-1">
                                                    <i class="bi bi-cash-stack me-1"></i> Status Tagihan:
                                                </span>
                                                <strong class="<?= $bayar === 'Lunas' ? 'text-success' : 'text-danger' ?>">
                                                    <?= $bayar === 'Lunas' ? 'Lunas (Pembayaran Selesai)' : 'Belum Lunas (Selesaikan di Kasir saat pengambilan)' ?>
                                                </strong>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="p-3 rounded bg-dark border border-secondary">
                                                <span class="text-secondary small d-block mb-1">
                                                    <i class="bi bi-clipboard2-pulse me-1"></i> Catatan Pengerjaan Mekanik:
                                                </span>
                                                <div class="text-light">
                                                    <?= !empty($hasil_servis['catatan_mekanik']) ? nl2br(e($hasil_servis['catatan_mekanik'])) : '<em class="text-secondary">Belum ada catatan teknis khusus dari mekanik.</em>' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hubungi Bengkel untuk Konfirmasi Pengambilan -->
                                    <div class="mt-4 pt-3 border-top border-secondary d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                                        <div class="small text-secondary">
                                            Ada pertanyaan mengenai servis nota ini? Hubungi CS kami.
                                        </div>
                                        <a href="https://wa.me/<?= e($cfg['no_whatsapp']) ?>?text=Halo%20Bengkel%20Ardans,%20saya%20ingin%20menanyakan%20status%20servis%20nota%20<?= e($hasil_servis['kode_transaksi']) ?>" target="_blank" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-2">
                                            <i class="bi bi-whatsapp"></i> Chat WhatsApp Nota Ini
                                        </a>
                                    </div>

                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </section>

        <!-- =====================================================================
             5. DAFTAR KATALOG LAYANAN SERVIS
             ===================================================================== -->
        <section id="layanan" class="py-5">
            <div class="container-xl">
                
                <div class="text-center mb-5">
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill mb-2">
                        <i class="bi bi-tools me-1"></i> Katalog Servis Profesional
                    </span>
                    <h2 class="h3 fw-bold text-white mb-2">Daftar Layanan Perawatan Mobil</h2>
                    <p class="text-secondary small max-w-600 mx-auto">
                        Pilihan layanan lengkap untuk memastikan performa kendaraan Anda selalu dalam kondisi prima dan aman di jalan raya.
                    </p>
                </div>

                <div class="row g-4">
                    <?php if (empty($daftar_layanan)): ?>
                        <div class="col-12 text-center text-secondary py-4">
                            Katalog layanan sedang diperbarui.
                        </div>
                    <?php else: ?>
                        <?php foreach ($daftar_layanan as $layanan): ?>
                            <?php
                            // Pilih ikon sesuai kategori
                            $kat = strtolower($layanan['kategori']);
                            $icon = 'bi-wrench';
                            if (str_contains($kat, 'mesin')) {
                                $icon = 'bi-gear-wide-connected';
                            } elseif (str_contains($kat, 'rem') || str_contains($kat, 'kaki')) {
                                $icon = 'bi-disc';
                            } elseif (str_contains($kat, 'ac') || str_contains($kat, 'listrik')) {
                                $icon = 'bi-snow';
                            }
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="service-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="service-icon-box">
                                            <i class="bi <?= $icon ?>"></i>
                                        </div>
                                        <span class="badge bg-secondary-subtle text-white-50 border border-secondary small">
                                            <?= e($layanan['kategori']) ?>
                                        </span>
                                    </div>

                                    <h5 class="fw-bold text-white mb-2"><?= e($layanan['nama_layanan']) ?></h5>
                                    
                                    <div class="text-secondary small mb-3">
                                        <i class="bi bi-clock me-1 text-info"></i> Estimasi Pengerjaan: <strong><?= e($layanan['estimasi_waktu'] ?: 'Menyesuaikan') ?></strong>
                                    </div>

                                    <div class="mt-auto pt-3 border-top border-secondary d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-secondary d-block" style="font-size: 0.75rem;">Tarif Jasa Mulai:</span>
                                            <span class="service-price-tag"><?= rupiah($layanan['biaya_jasa']) ?></span>
                                        </div>
                                        <a href="https://wa.me/<?= e($cfg['no_whatsapp']) ?>?text=Halo%20Bengkel%20Ardans,%20saya%20ingin%20konsultasi%20layanan%20<?= urlencode($layanan['nama_layanan']) ?>" target="_blank" class="btn btn-sm btn-outline-info rounded-pill" title="Konsultasi jasa ini">
                                            <i class="bi bi-whatsapp"></i> Tanya
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Call to action booking banner -->
                <div class="p-4 rounded-3 bg-surface border border-secondary mt-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h5 class="fw-bold text-white mb-1">Butuh Servis Khusus atau Keluhan Mesin Tertentu?</h5>
                        <p class="text-secondary small mb-0">Teknisi kami siap melakukan general check-up dan diagnosa komputer OBD2 untuk mobil Anda.</p>
                    </div>
                    <a href="https://wa.me/<?= e($cfg['no_whatsapp']) ?>?text=Halo%20Bengkel%20Ardans,%20saya%20ingin%20konsultasi%20keluhan%20mobil%20saya" target="_blank" class="btn btn-success fw-semibold px-4 py-2 text-nowrap">
                        <i class="bi bi-whatsapp me-1"></i> Konsultasi Langsung
                    </a>
                </div>

            </div>
        </section>

        <!-- =====================================================================
             6. BRAND SUKU CADANG & PARTNER OEM YANG DIGUNAKAN
             ===================================================================== -->
        <section id="brand" class="py-5" style="background: #090e1b; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);">
            <div class="container-xl">
                
                <div class="text-center mb-5">
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill mb-2">
                        <i class="bi bi-shield-check me-1"></i> 100% Genuine & OEM Parts
                    </span>
                    <h2 class="h3 fw-bold text-white mb-2">Brand Suku Cadang & Pelumas Resmi</h2>
                    <p class="text-secondary small max-w-600 mx-auto">
                        Bengkel Ardans hanya menggunakan suku cadang, oli mesin, dan komponen dari pabrikan resmi terkemuka untuk menjamin keawetan dan performa mesin mobil Anda.
                    </p>
                </div>

                <!-- Brand Grid Showcase dengan Logo Vektor Resmi -->
                <div class="brand-grid">
                    
                    <!-- 1. Toyota -->
                    <div class="brand-badge-card" title="Toyota Genuine Parts">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/toyota.svg" alt="Logo Toyota" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Toyota Genuine</div>
                    </div>

                    <!-- 2. Daihatsu -->
                    <div class="brand-badge-card" title="Daihatsu Genuine Parts">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/daihatsu.svg" alt="Logo Daihatsu" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Daihatsu Genuine</div>
                    </div>

                    <!-- 3. Honda -->
                    <div class="brand-badge-card" title="Honda Genuine Parts">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/honda.svg" alt="Logo Honda" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Honda Genuine</div>
                    </div>

                    <!-- 4. Mitsubishi -->
                    <div class="brand-badge-card" title="Mitsubishi Motors Genuine">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/mitsubishi.svg" alt="Logo Mitsubishi" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Mitsubishi Genuine</div>
                    </div>

                    <!-- 5. Suzuki -->
                    <div class="brand-badge-card" title="Suzuki Genuine Parts">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/suzuki.svg" alt="Logo Suzuki" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Suzuki Genuine</div>
                    </div>

                    <!-- 6. Nissan -->
                    <div class="brand-badge-card" title="Nissan Genuine Parts">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/nissan.svg" alt="Logo Nissan" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Nissan Genuine</div>
                    </div>

                    <!-- 7. Shell Helix -->
                    <div class="brand-badge-card" title="Shell Helix Lubricants">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/shell.svg" alt="Logo Shell Helix" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Pelumas Resmi</div>
                    </div>

                    <!-- 8. Castrol -->
                    <div class="brand-badge-card" title="Castrol Engine Oils">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/castrol.svg" alt="Logo Castrol" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Engine Oils</div>
                    </div>

                    <!-- 9. Pertamina Fastron -->
                    <div class="brand-badge-card" title="Pertamina Fastron Synthetic">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/pertamina.svg" alt="Logo Fastron Pertamina" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Synthetic Oil</div>
                    </div>

                    <!-- 10. Denso -->
                    <div class="brand-badge-card" title="Denso Automotive Parts">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/denso.svg" alt="Logo Denso" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Busi &amp; AC OEM</div>
                    </div>

                    <!-- 11. Bosch -->
                    <div class="brand-badge-card" title="Bosch Automotive Tech">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/bosch.svg" alt="Logo Bosch" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">German Tech</div>
                    </div>

                    <!-- 12. NGK -->
                    <div class="brand-badge-card" title="NGK Spark Plugs">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/ngk.svg" alt="Logo NGK Spark Plugs" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Laser Iridium</div>
                    </div>

                    <!-- 13. Bendix -->
                    <div class="brand-badge-card" title="Bendix Brake Systems">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/bendix.svg" alt="Logo Bendix" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Kampas Rem</div>
                    </div>

                    <!-- 14. GS Astra -->
                    <div class="brand-badge-card" title="GS Astra Battery">
                        <div class="brand-logo-wrap">
                            <img src="assets/img/brands/gs-astra.svg" alt="Logo GS Astra" class="brand-logo-img">
                        </div>
                        <div class="brand-badge-category">Aki Resmi Mobil</div>
                    </div>

                </div>

                <!-- Jaminan Kualitas & Catatan Hak Cipta / Trademark Disclaimer -->
                <div class="text-center mt-4">
                    <div class="small text-secondary mb-1">
                        <i class="bi bi-shield-fill-check text-success me-1"></i> Semua suku cadang dan pelumas dijamin <strong>100% Original</strong> dengan garansi pengerjaan dari Bengkel Ardans.
                    </div>
                    <div class="text-muted" style="font-size: 0.72rem;">
                        * Seluruh logo, merek dagang, dan nama produk adalah hak milik masing-masing perusahaan pemegang merek dan hanya digunakan sebagai informasi suku cadang serta spesialisasi servis di Bengkel Ardans.
                    </div>
                </div>

            </div>
        </section>

        <!-- =====================================================================
             7. PROFIL BENGKEL & FASILITAS
             ===================================================================== -->
        <section id="profil" class="py-5">
            <div class="container-xl">
                
                <div class="row align-items-center g-5 mb-5">
                    <div class="col-lg-6">
                        <span class="badge bg-primary-subtle text-info border border-info-subtle px-3 py-2 rounded-pill mb-2">
                            <i class="bi bi-building-check me-1"></i> Tentang Bengkel Ardans
                        </span>
                        <h2 class="h3 fw-bold text-white mb-3">Kejujuran, Kualitas, & Keamanan Berkendara Anda</h2>
                        <p class="text-secondary">
                            Bengkel Ardans didirikan dengan komitmen memberikan solusi perawatan mobil yang transparan dan profesional. Kami percaya setiap pemilik kendaraan berhak mendapatkan penjelasan teknis yang jujur tanpa ada biaya tersembunyi.
                        </p>
                        <p class="text-secondary">
                            Didukung teknisi handal yang terlatih dalam menangani berbagai macam pabrikan kendaraan (Jepang, Korea, dan Eropa), kami siap menjadi mitra terpercaya untuk seluruh kebutuhan otomotif Anda.
                        </p>

                        <div class="row g-3 mt-2">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2 text-white">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <span class="small fw-semibold">Transparansi Biaya</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2 text-white">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <span class="small fw-semibold">Garansi Pengerjaan</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2 text-white">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <span class="small fw-semibold">Scanner OBD2 Digital</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2 text-white">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <span class="small fw-semibold">Riwayat Servis Tercatat</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card bg-surface border-secondary shadow p-3">
                            <h6 class="fw-bold text-white mb-3"><i class="bi bi-stars text-warning me-2"></i>Fasilitas Unggulan Bengkel</h6>
                            <div class="d-flex flex-column gap-3">
                                
                                <div class="facility-card">
                                    <div class="facility-icon"><i class="bi bi-cup-hot-fill"></i></div>
                                    <div>
                                        <h6 class="text-white fw-bold mb-1">Ruang Tunggu Nyaman Ber-AC</h6>
                                        <p class="text-secondary small mb-0">Nikmati ruang tunggu bersih dengan sofa empuk, pendingin udara, teh & kopi gratis saat mobil diservis.</p>
                                    </div>
                                </div>

                                <div class="facility-card">
                                    <div class="facility-icon"><i class="bi bi-wifi"></i></div>
                                    <div>
                                        <h6 class="text-white fw-bold mb-1">Wi-Fi Cepat Gratis</h6>
                                        <p class="text-secondary small mb-0">Akses internet berkecepatan tinggi gratis sehingga Anda tetap dapat bekerja santai sembari menunggu.</p>
                                    </div>
                                </div>

                                <div class="facility-card">
                                    <div class="facility-icon"><i class="bi bi-tools"></i></div>
                                    <div>
                                        <h6 class="text-white fw-bold mb-1">Car Lift Hidrolik & Alat Modern</h6>
                                        <p class="text-secondary small mb-0">Area servis dilengkapi car lift hidrolik standar keselamatan tinggi untuk pengecekan kolong yang presisi.</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- =====================================================================
             8. ALAMAT, KONTAK & PETA LOKASI
             ===================================================================== -->
        <section id="kontak" class="py-5" style="background: #090e1b; border-top: 1px solid var(--color-border);">
            <div class="container-xl">
                
                <div class="row g-4 align-items-center">
                    
                    <div class="col-lg-5">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill mb-2">
                            <i class="bi bi-geo-alt-fill me-1"></i> Lokasi & Hubungi Kami
                        </span>
                        <h2 class="h3 fw-bold text-white mb-3">Kunjungi Bengkel Ardans</h2>
                        <p class="text-secondary small mb-4">
                            Silakan datang langsung ke workshop kami atau buat janji temu melalui WhatsApp untuk antrean prioritas.
                        </p>

                        <!-- Informasi Alamat & Kontak -->
                        <div class="d-flex flex-column gap-3 mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="p-2 rounded bg-surface border border-secondary text-danger">
                                    <i class="bi bi-geo-alt fs-5"></i>
                                </div>
                                <div>
                                    <strong class="text-white d-block">Alamat Bengkel:</strong>
                                    <span class="text-secondary small"><?= nl2br(e($cfg['alamat'])) ?></span>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-3">
                                <div class="p-2 rounded bg-surface border border-secondary text-success">
                                    <i class="bi bi-whatsapp fs-5"></i>
                                </div>
                                <div>
                                    <strong class="text-white d-block">WhatsApp Konsultasi:</strong>
                                    <a href="https://wa.me/<?= e($cfg['no_whatsapp']) ?>" target="_blank" class="text-success small fw-semibold text-decoration-none">
                                        +<?= e($cfg['no_whatsapp']) ?> (Chat Langsung)
                                    </a>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-3">
                                <div class="p-2 rounded bg-surface border border-secondary text-info">
                                    <i class="bi bi-telephone fs-5"></i>
                                </div>
                                <div>
                                    <strong class="text-white d-block">Telepon Kantor:</strong>
                                    <span class="text-secondary small"><?= e($cfg['no_telepon']) ?></span>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-3">
                                <div class="p-2 rounded bg-surface border border-secondary text-warning">
                                    <i class="bi bi-clock-history fs-5"></i>
                                </div>
                                <div>
                                    <strong class="text-white d-block">Jam Operasional:</strong>
                                    <span class="text-secondary small">
                                        <?= e($cfg['hari_operasional']) ?>: <strong><?= substr($cfg['jam_buka'], 0, 5) ?> - <?= substr($cfg['jam_tutup'], 0, 5) ?> WIB</strong>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Direct WhatsApp -->
                        <a href="https://wa.me/<?= e($cfg['no_whatsapp']) ?>?text=Halo%20Bengkel%20Ardans,%20saya%20ingin%20tanya%20jadwal%20servis%20mobil" target="_blank" class="btn btn-success btn-lg fw-bold px-4 d-inline-flex align-items-center gap-2 w-100 justify-content-center shadow">
                            <i class="bi bi-whatsapp fs-5"></i> Chat Konsultasi Sekarang
                        </a>

                    </div>

                    <!-- Embed Peta Lokasi / Map Placeholder -->
                    <div class="col-lg-7">
                        <div class="card bg-surface border-secondary overflow-hidden shadow">
                            <div class="card-header bg-dark border-secondary py-2 px-3 d-flex justify-content-between align-items-center">
                                <span class="small fw-semibold text-white">
                                    <i class="bi bi-map text-info me-1"></i> Peta Lokasi Bengkel Ardans
                                </span>
                                <a href="https://maps.google.com/?q=<?= urlencode($cfg['alamat']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary text-info py-0 px-2" style="font-size: 0.75rem;">
                                    Buka di Google Maps
                                </a>
                            </div>
                            <div class="ratio ratio-16x9">
                                <iframe 
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126748.56347862248!2d107.5731165!3d-6.9034443!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e6398252477f%3A0x146a1f93d3e815b2!2sBandung%2C%20Bandung%20City%2C%20West%20Java!5e0!3m2!1sen!2sid!4v1690000000000!5m2!1sen!2sid" 
                                    style="border:0;" 
                                    allowfullscreen="" 
                                    loading="lazy" 
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </section>
    </main>

    <!-- =====================================================================
         9. FOOTER PUBLIK (Dengan Menu Login Khusus Admin di Paling Bawah)
         ===================================================================== -->
    <footer class="landing-footer">
        <div class="container-xl">
            <div class="row g-4 mb-4">
                
                <!-- Kolom 1: Profil Brand & Sosmed -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="assets/img/logo.png" alt="Logo Bengkel Ardans" style="width: 38px; height: 38px; border-radius: 6px; border: 1px solid rgba(56, 189, 248, 0.4);">
                        <span class="fs-5 fw-bold text-white">Bengkel<span class="text-info">Ardans</span></span>
                    </div>
                    <p class="small text-secondary mb-3">
                        <?= e($cfg['slogan']) ?>. Melayani servis berkala, ganti oli, tune up injeksi, dan perawatan menyeluruh untuk seluruh tipe mobil.
                    </p>
                    
                    <!-- Tautan Sosial Media -->
                    <div class="d-flex gap-2">
                        <a href="https://instagram.com" target="_blank" class="btn btn-sm btn-outline-secondary text-white rounded-circle p-2" title="Instagram" style="width: 36px; height: 36px;">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://facebook.com" target="_blank" class="btn btn-sm btn-outline-secondary text-white rounded-circle p-2" title="Facebook" style="width: 36px; height: 36px;">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="https://tiktok.com" target="_blank" class="btn btn-sm btn-outline-secondary text-white rounded-circle p-2" title="TikTok" style="width: 36px; height: 36px;">
                            <i class="bi bi-tiktok"></i>
                        </a>
                        <a href="https://youtube.com" target="_blank" class="btn btn-sm btn-outline-secondary text-white rounded-circle p-2" title="YouTube" style="width: 36px; height: 36px;">
                            <i class="bi bi-youtube"></i>
                        </a>
                    </div>
                </div>

                <!-- Kolom 2: Navigasi Cepat -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3 small text-uppercase">Navigasi</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                        <li><a href="#beranda">Beranda</a></li>
                        <li><a href="#layanan">Katalog Layanan</a></li>
                        <li><a href="#cek-servis">Cek Status Servis</a></li>
                        <li><a href="#brand">Brand Sparepart</a></li>
                        <li><a href="#profil">Profil & Fasilitas</a></li>
                        <li><a href="#kontak">Lokasi Workshop</a></li>
                    </ul>
                </div>

                <!-- Kolom 3: Jam Operasional -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3 small text-uppercase">Jam Buka</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0 text-secondary">
                        <li class="d-flex justify-content-between">
                            <span>Senin - Sabtu:</span>
                            <strong class="text-white"><?= substr($cfg['jam_buka'], 0, 5) ?> - <?= substr($cfg['jam_tutup'], 0, 5) ?> WIB</strong>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span>Minggu:</span>
                            <span class="text-warning">Libur Rutin</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span>Tanggal Merah:</span>
                            <span class="text-warning">Libur Nasional</span>
                        </li>
                        <li class="pt-2 border-top border-secondary">
                            <span class="badge <?= $live_status['badge_class'] ?> px-2 py-1">
                                <?= e($live_status['label']) ?>
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Kolom 4: Hubungi Kami -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3 small text-uppercase">Hubungi Kami</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0 text-secondary">
                        <li>
                            <i class="bi bi-geo-alt text-danger me-2"></i><?= e($cfg['alamat']) ?>
                        </li>
                        <li>
                            <i class="bi bi-whatsapp text-success me-2"></i>+<?= e($cfg['no_whatsapp']) ?>
                        </li>
                        <li>
                            <i class="bi bi-envelope text-info me-2"></i><?= e($cfg['email']) ?>
                        </li>
                    </ul>
                </div>

            </div>

            <!-- Bagian Paling Bawah: Hak Cipta & MENU LOGIN KHUSUS ADMIN DI FOOTER -->
            <div class="pt-4 border-top border-secondary d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <div class="small text-secondary text-center text-sm-start">
                    &copy; <?= date('Y') ?> <strong><?= e($cfg['nama_bengkel']) ?></strong>. All rights reserved. Built with Pure Native PHP & MySQL.
                </div>

                <!-- MENU LOGIN KHUSUS ADMIN DI BAGIAN BAWAH AJA (JANGAN DI NAVBAR) -->
                <div>
                    <a href="login.php" class="admin-portal-link" title="Masuk ke Sistem Operasional Bengkel">
                        <i class="bi bi-shield-lock"></i>
                        <span>Akses Staf & Admin</span>
                    </a>
                </div>
            </div>

        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Smooth Scrolling & Live Watcher Script -->
    <script>
        // Smooth scrolling untuk link anchor (#)
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Fokus otomatis ke hasil servis jika query dilakukan
        <?php if (!empty($search_nota)): ?>
            window.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('cek-servis');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth' });
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
