<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bengkel Mobil Budi - Solusi Kendaraan Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1613214149922-f1809c99b414?q=80&w=1000&auto=format&fit=crop') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">BENGKEL BUDI</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#layanan">Layanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                    <li class="nav-item ms-3"><a class="btn btn-outline-light btn-sm mt-1" href="login.php">Login Pegawai</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold">Servis Mobil Terpercaya & Profesional</h1>
            <p class="lead mb-4">Kami menangani segala keluhan kendaraan Anda dengan mekanik handal dan suku cadang berkualitas.</p>
            <a href="#kontak" class="btn btn-primary btn-lg">Hubungi Kami Sekarang</a>
        </div>
    </header>

    <section id="layanan" class="container mt-5 pt-4">
        <h2 class="text-center mb-4">Layanan Unggulan Kami</h2>
        <div class="row text-center">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100 border-0 bg-light">
                    <div class="card-body">
                        <h4 class="card-title text-primary">⚙️ Tune Up</h4>
                        <p class="card-text">Kembalikan performa mesin mobil Anda seperti baru dengan perawatan rutin dari teknisi kami.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100 border-0 bg-light">
                    <div class="card-body">
                        <h4 class="card-title text-success">🛢️ Ganti Oli</h4>
                        <p class="card-text">Tersedia berbagai macam oli berkualitas untuk menjaga keawetan dan suhu mesin kendaraan.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100 border-0 bg-light">
                    <div class="card-body">
                        <h4 class="card-title text-danger">🔧 Servis Kaki-Kaki</h4>
                        <p class="card-text">Pengecekan dan perbaikan suspensi, rem, dan spooring agar perjalanan Anda aman dan nyaman.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer id="kontak" class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <h5>Bengkel Mobil Budi</h5>
            <p>Jl. Solo-Purwodadi Km 9.2 | Telp: 0815-4832-9839</p>
            <small class="text-secondary">&copy; 2026 Bengkel Budi. All rights reserved.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>