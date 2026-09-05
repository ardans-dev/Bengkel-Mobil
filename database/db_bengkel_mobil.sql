-- =====================================================================
-- SKEMA DATABASE BENGKEL MOBIL ARDANS (db_bengkel_mobil)
-- Versi: 2.0 (Clean Architecture & Relasional Lengkap)
-- Didesain untuk kemudahan pemahaman, keamanan, dan skalabilitas bisnis.
-- =====================================================================

-- 1. Bersihkan database lama jika ada, lalu buat database baru yang segar
DROP DATABASE IF EXISTS db_bengkel_mobil;
CREATE DATABASE db_bengkel_mobil CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_bengkel_mobil;

-- =====================================================================
-- TABEL 1: admin (Pengguna Sistem / Kasir / Owner)
-- Fungsi: Menyimpan kredensial login akun pegawai dan kasir bengkel.
-- =====================================================================
CREATE TABLE admin (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL COMMENT 'Nama asli kasir/admin untuk dicetak di nota',
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Username unik untuk masuk ke sistem',
    password VARCHAR(255) NOT NULL COMMENT 'Password yang sudah dienkripsi dengan Bcrypt',
    role ENUM('admin', 'kasir', 'owner') DEFAULT 'admin' COMMENT 'Tingkatan hak akses pengguna',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembuatan akun'
) ENGINE=InnoDB;

-- =====================================================================
-- TABEL 2: pelanggan (Data Pemilik Mobil)
-- Fungsi: Menyimpan biodata pelanggan tetap bengkel.
-- =====================================================================
CREATE TABLE pelanggan (
    id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL COMMENT 'Nama lengkap pelanggan',
    no_telepon VARCHAR(20) NOT NULL COMMENT 'Nomor WhatsApp/HP untuk konfirmasi servis selesai',
    alamat TEXT NULL COMMENT 'Alamat tempat tinggal pelanggan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pertama kali terdaftar'
) ENGINE=InnoDB;

-- =====================================================================
-- TABEL 3: kendaraan (Data Mobil Pasien Bengkel)
-- Fungsi: Mencatat unit mobil yang diservis, terhubung ke 1 pelanggan.
-- Catatan: 1 pelanggan bisa memiliki lebih dari 1 kendaraan (Relasi 1:N).
-- =====================================================================
CREATE TABLE kendaraan (
    id_kendaraan INT AUTO_INCREMENT PRIMARY KEY,
    id_pelanggan INT NOT NULL COMMENT 'ID pemilik mobil (Foreign Key ke tabel pelanggan)',
    plat_nomor VARCHAR(20) NOT NULL UNIQUE COMMENT 'Nomor polisi unik mobil (misal: D 1234 AB)',
    merek VARCHAR(50) NOT NULL COMMENT 'Pabrikan mobil (misal: Toyota, Honda, Daihatsu)',
    tipe VARCHAR(50) NOT NULL COMMENT 'Model spesifik mobil (misal: Avanza 1.3 G, Brio Satya)',
    tahun INT NULL COMMENT 'Tahun perakitan mobil untuk panduan spesifikasi suku cadang',
    transmisi ENUM('Manual', 'Matic') DEFAULT 'Manual' COMMENT 'Tipe transmisi mobil',
    warna VARCHAR(30) NULL COMMENT 'Warna mobil untuk memudahkan identifikasi di bengkel',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kendaraan_pelanggan 
        FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- TABEL 4: mekanik (Montir / Teknisi Bengkel)
-- Fungsi: Mencatat daftar mekanik yang bertugas mengerjakan perbaikan.
-- =====================================================================
CREATE TABLE mekanik (
    id_mekanik INT AUTO_INCREMENT PRIMARY KEY,
    nama_mekanik VARCHAR(100) NOT NULL COMMENT 'Nama teknisi bengkel',
    no_telepon VARCHAR(20) NULL COMMENT 'Nomor kontak mekanik',
    keahlian VARCHAR(100) NULL COMMENT 'Spesialisasi (misal: Mesin, Kelistrikan, Kaki-kaki)',
    status ENUM('Aktif', 'Cuti', 'Nonaktif') DEFAULT 'Aktif' COMMENT 'Status kesiapan montir',
    alamat TEXT NULL COMMENT 'Alamat domisili teknisi',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- TABEL 5: layanan (Katalog Jasa Servis)
-- Fungsi: Daftar ongkos jasa kerja montir tanpa suku cadang.
-- =====================================================================
CREATE TABLE layanan (
    id_layanan INT AUTO_INCREMENT PRIMARY KEY,
    kode_layanan VARCHAR(20) NOT NULL UNIQUE COMMENT 'Kode referensi jasa (misal: SRV-001)',
    nama_layanan VARCHAR(100) NOT NULL COMMENT 'Nama jasa (misal: Tune Up Injeksi, Ganti Oli Mesin)',
    kategori VARCHAR(50) DEFAULT 'Umum' COMMENT 'Kategori pekerjaan (misal: Mesin, Rem, AC)',
    biaya_jasa DECIMAL(12, 2) NOT NULL DEFAULT 0.00 COMMENT 'Ongkos jasa montir dalam rupiah',
    estimasi_waktu VARCHAR(30) DEFAULT '1 Jam' COMMENT 'Perkiraan durasi pengerjaan jasa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- TABEL 6: sparepart (Inventaris Suku Cadang & Gudang)
-- Fungsi: Manajemen stok barang, harga modal, dan harga jual.
-- =====================================================================
CREATE TABLE sparepart (
    id_sparepart INT AUTO_INCREMENT PRIMARY KEY,
    kode_sparepart VARCHAR(30) NOT NULL UNIQUE COMMENT 'Kode SKU unik suku cadang (misal: OLI-SHL-01)',
    nama_sparepart VARCHAR(100) NOT NULL COMMENT 'Nama barang (misal: Shell Helix HX7 10W-40 4L)',
    kategori VARCHAR(50) DEFAULT 'Umum' COMMENT 'Kategori suku cadang (misal: Oli, Filter, Rem, Busi)',
    stok INT NOT NULL DEFAULT 0 COMMENT 'Jumlah stok fisik yang tersedia di gudang',
    satuan VARCHAR(20) DEFAULT 'Pcs' COMMENT 'Satuan kuantitas (misal: Pcs, Botol, Liter, Set)',
    harga_beli DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'Harga modal dari supplier untuk analisa laba',
    harga_jual DECIMAL(12, 2) NOT NULL DEFAULT 0.00 COMMENT 'Harga banderol jual resmi ke pelanggan',
    stok_minimum INT DEFAULT 5 COMMENT 'Batas peringatan restock saat stok menipis',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- TABEL 7: transaksi (Work Order / Nota Servis Induk)
-- Fungsi: Mencatat kunjungan servis masuk, keluhan, dan akumulasi biaya.
-- =====================================================================
CREATE TABLE transaksi (
    id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(30) NOT NULL UNIQUE COMMENT 'Nomor invoice resmi (misal: TRX-20260905-001)',
    id_kendaraan INT NOT NULL COMMENT 'Mobil yang dikerjakan',
    id_mekanik INT NOT NULL COMMENT 'Mekanik penanggung jawab servis',
    id_admin INT NULL COMMENT 'Kasir yang memproses nota pembayaran',
    kilometer INT DEFAULT 0 COMMENT 'Angka odometer spedometer saat mobil masuk',
    keluhan TEXT NOT NULL COMMENT 'Masalah yang dirasakan pemilik kendaraan',
    catatan_mekanik TEXT NULL COMMENT 'Hasil diagnosa dan saran perawatan mekanik',
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu penerimaan servis',
    status_servis ENUM('Menunggu', 'Dikerjakan', 'Selesai', 'Dibatalkan') DEFAULT 'Menunggu' COMMENT 'Alur pengerjaan mobil',
    metode_pembayaran ENUM('Tunai', 'Transfer Bank', 'QRIS') DEFAULT 'Tunai' COMMENT 'Cara bayar pelanggan',
    total_jasa DECIMAL(12, 2) NOT NULL DEFAULT 0.00 COMMENT 'Subtotal akumulasi seluruh biaya jasa',
    total_sparepart DECIMAL(12, 2) NOT NULL DEFAULT 0.00 COMMENT 'Subtotal akumulasi seluruh biaya sparepart',
    total_biaya DECIMAL(12, 2) NOT NULL DEFAULT 0.00 COMMENT 'Total akhir yang wajib dibayar pelanggan',
    jumlah_bayar DECIMAL(12, 2) NOT NULL DEFAULT 0.00 COMMENT 'Uang tunai/transfer yang diserahkan pelanggan',
    kembalian DECIMAL(12, 2) NOT NULL DEFAULT 0.00 COMMENT 'Uang kembalian yang diberikan kasir',
    status_pembayaran ENUM('Belum Lunas', 'Lunas') DEFAULT 'Belum Lunas' COMMENT 'Status tagihan kasir',
    CONSTRAINT fk_transaksi_kendaraan 
        FOREIGN KEY (id_kendaraan) REFERENCES kendaraan(id_kendaraan) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_transaksi_mekanik 
        FOREIGN KEY (id_mekanik) REFERENCES mekanik(id_mekanik) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_transaksi_admin 
        FOREIGN KEY (id_admin) REFERENCES admin(id_admin) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- TABEL 8: detail_transaksi_layanan (Rincian Jasa pada Nota)
-- Fungsi: Tabel perantara relasi Many-to-Many antara Transaksi & Jasa.
-- =====================================================================
CREATE TABLE detail_transaksi_layanan (
    id_detail_lyn INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL COMMENT 'ID nota transaksi induk',
    id_layanan INT NOT NULL COMMENT 'ID referensi jasa servis yang diambil',
    biaya_jasa DECIMAL(12, 2) NOT NULL COMMENT 'Harga jasa saat transaksi berlangsung (snapshot)',
    subtotal DECIMAL(12, 2) NOT NULL COMMENT 'Subtotal biaya jasa item ini',
    CONSTRAINT fk_detail_layanan_transaksi 
        FOREIGN KEY (id_transaksi) REFERENCES transaksi(id_transaksi) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detail_layanan_item 
        FOREIGN KEY (id_layanan) REFERENCES layanan(id_layanan) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- TABEL 9: detail_transaksi_sparepart (Rincian Suku Cadang pada Nota)
-- Fungsi: Tabel perantara relasi Many-to-Many antara Transaksi & Suku Cadang.
-- =====================================================================
CREATE TABLE detail_transaksi_sparepart (
    id_detail_sp INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL COMMENT 'ID nota transaksi induk',
    id_sparepart INT NOT NULL COMMENT 'ID referensi suku cadang yang dipasang',
    harga_satuan DECIMAL(12, 2) NOT NULL COMMENT 'Harga satuan saat transaksi berlangsung (snapshot)',
    jumlah INT NOT NULL DEFAULT 1 COMMENT 'Banyaknya barang yang digunakan',
    subtotal DECIMAL(12, 2) NOT NULL COMMENT 'Hasil kali harga satuan dengan jumlah',
    CONSTRAINT fk_detail_sparepart_transaksi 
        FOREIGN KEY (id_transaksi) REFERENCES transaksi(id_transaksi) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detail_sparepart_item 
        FOREIGN KEY (id_sparepart) REFERENCES sparepart(id_sparepart) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- =====================================================================
-- TABEL 10: pengaturan_bengkel (Profil & Jam Operasional Bengkel)
-- Fungsi: Menyimpan profil, jadwal buka/tutup, dan status operasional real-time.
-- =====================================================================
CREATE TABLE pengaturan_bengkel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_bengkel VARCHAR(100) NOT NULL DEFAULT 'Bengkel Ardans',
    slogan VARCHAR(255) NOT NULL DEFAULT 'Solusi Terpercaya Perawatan & Perbaikan Mobil Anda',
    alamat TEXT NOT NULL,
    no_telepon VARCHAR(25) NOT NULL DEFAULT '0812-3456-7890',
    no_whatsapp VARCHAR(25) NOT NULL DEFAULT '6281234567890',
    email VARCHAR(100) NOT NULL DEFAULT 'kontak@bengkelardans.id',
    jam_buka TIME NOT NULL DEFAULT '08:00:00',
    jam_tutup TIME NOT NULL DEFAULT '16:00:00',
    hari_operasional VARCHAR(100) NOT NULL DEFAULT 'Senin - Sabtu',
    status_manual ENUM('Otomatis', 'Buka_Paksa', 'Tutup_Sementara', 'Libur_Mendadak') NOT NULL DEFAULT 'Otomatis',
    pesan_pengumuman TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- DATA AWAL (SEED DATA / DUMMY REALISTIS UNTUK PENGUJIAN)
-- =====================================================================

-- 1. Buat Akun Pengguna Default:
-- Akun 1: username 'admin' | password 'admin123' (hash Bcrypt: $2y$10$wE1UuI.1Gf4Y9Oeqf3NqOu6.7J3g7L0L0K1Y6Uv2Oq5M0D2Xq4D8O)
-- Akun 2: username 'kasir' | password 'kasir123' (hash Bcrypt: $2y$10$wE1UuI.1Gf4Y9Oeqf3NqOu6.7J3g7L0L0K1Y6Uv2Oq5M0D2Xq4D8O)
INSERT INTO admin (nama_lengkap, username, password, role) VALUES
('Ahmad Yardan Rasika', 'admin', '$2y$12$ZsDSzzqK0i2elqYO0og9IucW.AY7pOJOP4F7AYYCHL9DIf3Rwz5Ju', 'admin'),
('Kasir Bengkel', 'kasir', '$2y$12$01zuX1WV1sbqEBEKpgAjneNqNdjBy.C8SfTabUvz/NEqZ/fvrCufW', 'kasir');

-- 2. Data Pelanggan Awal
INSERT INTO pelanggan (nama, no_telepon, alamat) VALUES
('Budi Santoso', '081234567890', 'Jl. Sukajadi No. 12, Bandung'),
('Siti Nurhaliza', '082198765432', 'Jl. Dago Asri No. 45, Bandung'),
('Rian Pratama', '085711223344', 'Jl. Buah Batu No. 88, Bandung');

-- 3. Data Mobil Pelanggan
INSERT INTO kendaraan (id_pelanggan, plat_nomor, merek, tipe, tahun, transmisi, warna) VALUES
(1, 'D 1234 AB', 'Toyota', 'Avanza 1.3 G', 2019, 'Manual', 'Hitam Metalik'),
(2, 'D 5678 XY', 'Honda', 'Brio Satya E', 2021, 'Matic', 'Putih Mutiara'),
(3, 'B 9900 ZZZ', 'Mitsubishi', 'Xpander Ultimate', 2022, 'Matic', 'Abu-abu');

-- 4. Data Mekanik
INSERT INTO mekanik (nama_mekanik, no_telepon, keahlian, status, alamat) VALUES
('Maman Suparman', '081399887766', 'Spesialis Mesin & Transmisi', 'Aktif', 'Cicaheum, Bandung'),
('Asep Kurnia', '081344556677', 'Kelistrikan & AC Mobil', 'Aktif', 'Antapani, Bandung'),
('Dedi Cahyadi', '085211998844', 'Kaki-kaki, Rem & Spooring', 'Aktif', 'Ujungberung, Bandung');

-- 5. Katalog Jasa Servis
INSERT INTO layanan (kode_layanan, nama_layanan, kategori, biaya_jasa, estimasi_waktu) VALUES
('SRV-001', 'Tune Up Injeksi Standar', 'Mesin', 150000.00, '1 Jam'),
('SRV-002', 'Jasa Ganti Oli Mesin & Filter', 'Mesin', 35000.00, '30 Menit'),
('SRV-003', 'Overhaul Rem 4 Roda & Kuras Minyak Rem', 'Rem & Kaki-kaki', 200000.00, '2 Jam'),
('SRV-004', 'Servis AC Ringan (Cuci Blower & Evaporator)', 'Kelistrikan & AC', 175000.00, '1.5 Jam'),
('SRV-005', 'Spooring & Balancing 4 Roda', 'Rem & Kaki-kaki', 125000.00, '1 Jam');

-- 6. Katalog Suku Cadang
INSERT INTO sparepart (kode_sparepart, nama_sparepart, kategori, stok, satuan, harga_beli, harga_jual, stok_minimum) VALUES
('OLI-SHL-01', 'Shell Helix HX7 10W-40 (4 Liter)', 'Oli Mesin', 18, 'Galon', 260000.00, 330000.00, 5),
('FLT-TOY-01', 'Filter Oli Original Avanza / Xenia', 'Filter', 25, 'Pcs', 25000.00, 45000.00, 10),
('BSI-NGK-01', 'Busi NGK Iridium BKR6EIX (Set 4 Pcs)', 'Pengapian', 12, 'Set', 180000.00, 240000.00, 4),
('REM-BND-01', 'Kampas Rem Depan Bendix Avanza', 'Pengereman', 8, 'Set', 160000.00, 220000.00, 3),
('MNY-SEI-01', 'Minyak Rem Seiken DOT 3 (300 ml)', 'Fluida', 4, 'Botol', 22000.00, 35000.00, 5);

-- 7. Contoh Transaksi Sampel (Servis Selesai)
INSERT INTO transaksi (
    kode_transaksi, id_kendaraan, id_mekanik, id_admin, kilometer, 
    keluhan, catatan_mekanik, tanggal, status_servis, metode_pembayaran, 
    total_jasa, total_sparepart, total_biaya, jumlah_bayar, kembalian, status_pembayaran
) VALUES (
    'TRX-20260905-001', 1, 1, 1, 45200, 
    'Ganti oli rutin 5.000 KM dan tarikan mesin agak tersendat', 
    'Mesin sudah ditune up dan busi dibersihkan. Filter oli baru terpasang.', 
    NOW(), 'Selesai', 'Tunai', 
    185000.00, 375000.00, 560000.00, 600000.00, 40000.00, 'Lunas'
);

-- Detail Transaksi Sampel
INSERT INTO detail_transaksi_layanan (id_transaksi, id_layanan, biaya_jasa, subtotal) VALUES
(1, 1, 150000.00, 150000.00),
(1, 2, 35000.00, 35000.00);

INSERT INTO detail_transaksi_sparepart (id_transaksi, id_sparepart, harga_satuan, jumlah, subtotal) VALUES
(1, 1, 330000.00, 1, 330000.00),
(1, 2, 45000.00, 1, 45000.00);

-- 8. Pengaturan Awal Bengkel
INSERT INTO pengaturan_bengkel (
    id, nama_bengkel, slogan, alamat, no_telepon, no_whatsapp, email, 
    jam_buka, jam_tutup, hari_operasional, status_manual, pesan_pengumuman
) VALUES (
    1,
    'Bengkel Ardans',
    'Solusi Terpercaya Perawatan & Perbaikan Mobil Anda',
    'Jl. Soekarno Hatta No. 450, Bandung, Jawa Barat (Samping Pool Bus)',
    '0812-3456-7890',
    '6281234567890',
    'kontak@bengkelardans.id',
    '08:00:00',
    '16:00:00',
    'Senin - Sabtu (Minggu & Tanggal Merah Libur)',
    'Otomatis',
    NULL
);
