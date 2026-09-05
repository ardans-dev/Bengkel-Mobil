<div align="center">

# 🚗 Sistem Informasi Bengkel Mobil & Kasir POS (Pure PHP)

**Sistem manajemen operasional bengkel, inventaris suku cadang, dan kasir servis modern.**  
*Dibangun dengan Pure Native PHP (PDO), MySQL, HTML5, CSS3 (Bootstrap 5), dan Vanilla JavaScript.*

</div>

---

## 📖 Sekilas Tentang Proyek
Sistem ini dirancang khusus untuk mempermudah operasional bengkel mobil modern: mulai dari pencatatan data pelanggan, pendaftaran mobil masuk, manajemen inventaris suku cadang, kontrol teknisi mekanik, hingga penghitungan nota tagihan otomatis dengan transaksi kasir.

Proyek ini menggunakan **arsitektur modular bersih tanpa framework**, menjadikannya sangat ringan, mudah dipelajari, dan aman dari celah keamanan standar web.

---

## 🛡️ Fitur Keamanan & Arsitektur Backend (Security-First)
* 🔒 **100% Prepared Statements (PDO):** Seluruh query database menggunakan parameter binding bawaan PDO untuk menjamin sistem **kebal terhadap SQL Injection**.
* 🔑 **Bcrypt Password Hashing:** Password akun pengguna disimpan dalam bentuk hash aman menggunakan algoritma Bcrypt bawaan PHP (`password_hash` & `password_verify`).
* 🛡️ **Session Hijacking Protection:** Dilengkapi fungsi `session_regenerate_id(true)` saat login untuk mencegah serangan *Session Fixation*.
* ⚖️ **ACID Database Transactions:** Operasi penambahan/pembatalan sparepart kasir dibungkus dalam `beginTransaction()` dan `commit()` / `rollBack()`. Ini menjamin pemotongan stok fisik di gudang dan total nota kasir selalu sinkron 100% tanpa risiko korupsi data.
* 🛡️ **Anti-XSS Escaping:** Setiap output teks dari database dibungkus dengan helper `e()` (`htmlspecialchars`) sebelum dirender ke antarmuka HTML.

---

## ✨ Fitur-Fitur Utama

| Fitur | Deskripsi |
| :--- | :--- |
| **📊 Dashboard Eksekutif** | Menampilkan omset pendapatan, unit dalam pengerjaan, antrean masuk, dan *alert* suku cadang stok menipis. |
| **🛒 Kasir POS (Work Order)** | Pendaftaran servis mobil masuk dengan nomor invoice otomatis (`TRX-YYYYMMDD-XXXX`), pencatatan KM, dan keluhan pelanggan. |
| **🔧 Manajemen Servis (ACID)** | Pemasangan jasa montir dan sparepart secara fleksibel dengan **pemotongan & pengembalian stok gudang otomatis**. |
| **📦 Kontrol Inventaris Gudang** | Pengelolaan SKU suku cadang, batas stok kritis (*minimum stock alert*), harga beli vs harga jual. |
| **👥 Direktori Pelanggan & Unit** | Hubungan relasional *One-to-Many*: 1 pelanggan bisa memiliki banyak mobil dengan riwayat servis lengkap. |
| **🖨️ Cetak Nota Resmi** | Invoice rapi yang siap dicetak langsung ke printer kasir / thermal / PDF (ramah cetak `@media print`). |

---

## 📁 Struktur Folder Modular

```text
Bengkel-Mobil/
│
├── config/
│   └── database.php         # Koneksi PDO (Singleton), Helper Anti-XSS, Format Rupiah & Flash Messages
│
├── database/
│   └── db_bengkel_mobil.sql # Skema database relasional lengkap, Foreign Keys, & Data Awal (Seed Data)
│
├── includes/
│   ├── auth.php             # Guard proteksi autentikasi & validasi session login
│   ├── header.php           # Template header, link Bootstrap 5, & navbar aktif
│   └── footer.php           # Template penutup HTML & script Bootstrap bundle
│
├── index.php                # Landing Page Publik (Profil, Katalog Jasa, Cek Status Servis, Brand)
├── dashboard.php            # Dashboard internal operasional kasir, antrean & metrik bisnis
├── pengaturan.php           # Konfigurasi jam operasional (Buka/Tutup/Libur Mendadak) & Profil
├── kasir.php                # Form pendaftaran servis baru (Kasir POS)
├── detail_transaksi.php     # Kelola suku cadang, jasa servis, status & kasir pembayaran (ACID)
├── transaksi.php            # Riwayat seluruh transaksi servis dengan filter status
├── cetak_nota.php           # Template invoice cetak nota kasir rapi
│
├── pelanggan.php            # Modul CRUD Pelanggan terpusat (List, Tambah, Edit, Hapus)
├── kendaraan.php            # Modul CRUD Kendaraan Pasien (Relasi ke Pelanggan)
├── sparepart.php            # Modul CRUD Inventaris Suku Cadang & Kontrol Stok Kritis
├── layanan.php              # Modul CRUD Katalog Ongkos Jasa Montir
├── mekanik.php              # Modul CRUD Data Teknisi Mekanik & Beban Kerja Aktif
│
├── login.php                # Halaman login aman (Prepared Statements & Bcrypt)
├── logout.php               # Penghancuran sesi login dan redirect
└── README.md
```

---

## 🛠️ Panduan Instalasi & Menjalankan Aplikasi

1. **Clone Repositori:**
   Pindahkan repositori ini ke folder root web server lokal kamu (misal: `C:\laragon\www\` di Laragon atau `C:\xampp\htdocs\` di XAMPP):
   ```bash
   git clone https://github.com/ardans-dev/Bengkel-Mobil.git
   ```

2. **Import Database:**
   - Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) atau HeidiSQL.
   - Import file: `database/db_bengkel_mobil.sql`.
   - Database `db_bengkel_mobil` beserta 9 tabel relasional dan data sampel akan otomatis terbuat.

3. **Konfigurasi Database (Jika Diperlukan):**
   - Buka file `config/database.php`.
   - Sesuaikan username dan password MySQL lokal kamu (secara default: user `root` dan password kosong `""` cocok untuk Laragon/XAMPP).

4. **Akses Aplikasi:**
   Buka browser dan arahkan ke:
   ```text
   http://localhost/Bengkel-Mobil
   ```

---

## 🔑 Kredensial Akun Pengujian (Demo)

| Role | Username | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Hak akses penuh seluruh menu |
| **Kasir** | `kasir` | `kasir123` | Hak akses operasional kasir servis |

---

<div align="center">

**Crafted with ☕ & Clean Architecture by [ardans-dev](https://github.com/ardans-dev)**  
*Dokumentasi ini ditulis lengkap dengan komentar edukatif di setiap baris kode.*

</div>
