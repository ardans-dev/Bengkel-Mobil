<?php
/**
 * =====================================================================
 * FILE: pengaturan.php
 * DESKRIPSI: Panel Pengaturan Jam Operasional & Status Bengkel Mobil Ardans
 * =====================================================================
 */

require_once __DIR__ . '/includes/auth.php';

$page_title = "Pengaturan Bengkel";
$user = current_user();

// Dapatkan data pengaturan saat ini
$config = get_pengaturan_bengkel();

// Proses Simpan Perubahan Pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_bengkel     = trim($_POST['nama_bengkel'] ?? '');
    $slogan           = trim($_POST['slogan'] ?? '');
    $alamat           = trim($_POST['alamat'] ?? '');
    $no_telepon       = trim($_POST['no_telepon'] ?? '');
    $no_whatsapp      = trim($_POST['no_whatsapp'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $jam_buka         = trim($_POST['jam_buka'] ?? '08:00');
    $jam_tutup        = trim($_POST['jam_tutup'] ?? '16:00');
    $hari_operasional = trim($_POST['hari_operasional'] ?? 'Senin - Sabtu');
    $status_manual    = trim($_POST['status_manual'] ?? 'Otomatis');
    $pesan_pengumuman = trim($_POST['pesan_pengumuman'] ?? '');

    // Validasi sederhana
    if (empty($nama_bengkel) || empty($jam_buka) || empty($jam_tutup)) {
        set_flash('danger', 'Nama bengkel, jam buka, dan jam tutup tidak boleh kosong!');
    } else {
        try {
            // Format waktu menjadi HH:MM:00
            if (strlen($jam_buka) === 5) $jam_buka .= ':00';
            if (strlen($jam_tutup) === 5) $jam_tutup .= ':00';

            // Bersihkan nomor WhatsApp dari karakter non-digit
            $no_wa_clean = preg_replace('/[^0-9]/', '', $no_whatsapp);
            if (str_starts_with($no_wa_clean, '0')) {
                $no_wa_clean = '62' . substr($no_wa_clean, 1);
            }

            $stmt = db()->prepare("
                UPDATE pengaturan_bengkel SET
                    nama_bengkel     = :nama_bengkel,
                    slogan           = :slogan,
                    alamat           = :alamat,
                    no_telepon       = :no_telepon,
                    no_whatsapp      = :no_whatsapp,
                    email            = :email,
                    jam_buka         = :jam_buka,
                    jam_tutup        = :jam_tutup,
                    hari_operasional = :hari_operasional,
                    status_manual    = :status_manual,
                    pesan_pengumuman = :pesan_pengumuman
                WHERE id = :id
            ");

            $stmt->execute([
                ':nama_bengkel'     => $nama_bengkel,
                ':slogan'           => $slogan,
                ':alamat'           => $alamat,
                ':no_telepon'       => $no_telepon,
                ':no_whatsapp'      => $no_wa_clean,
                ':email'            => $email,
                ':jam_buka'         => $jam_buka,
                ':jam_tutup'        => $jam_tutup,
                ':hari_operasional' => $hari_operasional,
                ':status_manual'    => $status_manual,
                ':pesan_pengumuman' => !empty($pesan_pengumuman) ? $pesan_pengumuman : null,
                ':id'               => $config['id'] ?? 1
            ]);

            set_flash('success', 'Pengaturan bengkel dan jam operasional berhasil disimpan!');
            redirect('pengaturan.php');
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal memperbarui pengaturan: ' . $e->getMessage());
        }
    }
}

// Cek status operasional terkini
$live_status = cek_status_bengkel();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">
            <i class="bi bi-gear-fill text-info me-2"></i>Pengaturan Operasional Bengkel
        </h2>
        <p class="text-secondary small mb-0">Kelola jadwal jam kerja, status buka/tutup dinamis, libur mendadak, dan kontak resmi bengkel.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php" target="_blank" class="btn btn-outline-info btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i>Lihat Landing Page
        </a>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Ke Dashboard
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Status Real-time Card -->
    <div class="col-lg-4">
        <div class="card bg-surface border-secondary shadow-sm mb-4">
            <div class="card-header border-secondary bg-transparent py-3">
                <h6 class="text-white fw-bold mb-0">
                    <i class="bi bi-broadcast text-danger me-2"></i>Status Operasional Terkini
                </h6>
            </div>
            <div class="card-body">
                <div class="p-3 rounded-3 text-center mb-3 <?= $live_status['is_buka'] ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger' ?>">
                    <span class="badge <?= $live_status['badge_class'] ?> fs-6 px-3 py-2 mb-2 d-inline-block">
                        <i class="bi <?= $live_status['badge_icon'] ?> me-1"></i><?= e($live_status['label']) ?>
                    </span>
                    <div class="fw-semibold text-white small"><?= e($live_status['sublabel']) ?></div>
                    <div class="text-white-50 small mt-2 fst-italic">"<?= e($live_status['pesan']) ?>"</div>
                </div>

                <ul class="list-group list-group-flush bg-transparent">
                    <li class="list-group-item bg-transparent text-secondary d-flex justify-content-between px-0 py-2 border-secondary">
                        <span>Waktu Server (WIB):</span>
                        <strong class="text-info"><?= date('H:i:s') ?> WIB</strong>
                    </li>
                    <li class="list-group-item bg-transparent text-secondary d-flex justify-content-between px-0 py-2 border-secondary">
                        <span>Hari Ini:</span>
                        <strong class="text-white"><?= ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][(int)date('w')] ?>, <?= date('d M Y') ?></strong>
                    </li>
                    <li class="list-group-item bg-transparent text-secondary d-flex justify-content-between px-0 py-2 border-secondary">
                        <span>Mode Operasional:</span>
                        <span class="badge bg-secondary"><?= e($config['status_manual']) ?></span>
                    </li>
                    <li class="list-group-item bg-transparent text-secondary d-flex justify-content-between px-0 py-2 border-secondary">
                        <span>Jam Reguler:</span>
                        <span class="text-white"><?= substr($config['jam_buka'], 0, 5) ?> - <?= substr($config['jam_tutup'], 0, 5) ?> WIB</span>
                    </li>
                </ul>

                <div class="alert alert-dark border-secondary small mt-3 mb-0 text-white-50">
                    <i class="bi bi-info-circle text-info me-1"></i>
                    Status ini otomatis muncul di <b>Halaman Depan (Landing Page)</b> pengunjung bengkel.
                </div>
            </div>
        </div>

        <!-- Kartu Pintasan Tanggal Merah -->
        <div class="card bg-surface border-secondary shadow-sm">
            <div class="card-header border-secondary bg-transparent py-3">
                <h6 class="text-white fw-bold mb-0">
                    <i class="bi bi-calendar3 text-warning me-2"></i>Libur Nasional Terdekat
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush bg-transparent" style="max-height: 250px; overflow-y: auto;">
                    <?php
                    $libur_nas = daftar_hari_libur_nasional();
                    ksort($libur_nas);
                    $count = 0;
                    $today = date('Y-m-d');
                    foreach ($libur_nas as $tgl => $nama):
                        if ($tgl >= $today && $count < 6):
                            $count++;
                    ?>
                        <div class="list-group-item bg-transparent border-secondary py-2 px-3">
                            <div class="small fw-semibold text-warning"><?= date('d M Y', strtotime($tgl)) ?></div>
                            <div class="small text-white-50"><?= e($nama) ?></div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Form Edit Pengaturan -->
    <div class="col-lg-8">
        <div class="card bg-surface border-secondary shadow-sm">
            <div class="card-header border-secondary bg-transparent py-3">
                <h6 class="text-white fw-bold mb-0">
                    <i class="bi bi-sliders text-info me-2"></i>Form Konfigurasi Jadwal & Profil Bengkel
                </h6>
            </div>
            <div class="card-body">
                <form action="pengaturan.php" method="POST">
                    
                    <!-- Bagian 1: Status & Jadwal Kerja -->
                    <h6 class="text-info text-uppercase fw-bold small mb-3 border-bottom border-secondary pb-2">
                        1. Status Operasional & Jadwal
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label text-white small fw-bold">Mode Kontrol Status Bengkel</label>
                            <select name="status_manual" class="form-select bg-dark text-white border-secondary">
                                <option value="Otomatis" <?= ($config['status_manual'] ?? '') === 'Otomatis' ? 'selected' : '' ?>>
                                    🟢 Otomatis (Ikuti Jam Buka/Tutup, Minggu & Tanggal Merah Libur)
                                </option>
                                <option value="Buka_Paksa" <?= ($config['status_manual'] ?? '') === 'Buka_Paksa' ? 'selected' : '' ?>>
                                    🟡 Buka Paksa (Lembur / Buka saat hari libur)
                                </option>
                                <option value="Tutup_Sementara" <?= ($config['status_manual'] ?? '') === 'Tutup_Sementara' ? 'selected' : '' ?>>
                                    🔴 Tutup Sementara (Istirahat siang / Tutup sesaat)
                                </option>
                                <option value="Libur_Mendadak" <?= ($config['status_manual'] ?? '') === 'Libur_Mendadak' ? 'selected' : '' ?>>
                                    ⚠️ Libur Mendadak (Pemberitahuan khusus hari ini)
                                </option>
                            </select>
                            <small class="text-secondary">Pilih "Otomatis" untuk membiarkan sistem menyalakan badge buka/tutup sesuai jam kerja.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Jam Buka Reguler</label>
                            <input type="time" name="jam_buka" class="form-control bg-dark text-white border-secondary" value="<?= substr($config['jam_buka'], 0, 5) ?>" required>
                            <small class="text-secondary">Default: 08:00 WIB</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Jam Tutup Reguler</label>
                            <input type="time" name="jam_tutup" class="form-control bg-dark text-white border-secondary" value="<?= substr($config['jam_tutup'], 0, 5) ?>" required>
                            <small class="text-secondary">Default: 16:00 WIB</small>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label text-white small fw-bold">Hari Operasional (Teks Tampilan)</label>
                            <input type="text" name="hari_operasional" class="form-control bg-dark text-white border-secondary" value="<?= e($config['hari_operasional']) ?>" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label text-white small fw-bold">Pesan Pengumuman Khusus (Opsional)</label>
                            <textarea name="pesan_pengumuman" rows="2" class="form-control bg-dark text-white border-secondary" placeholder="Contoh: Hari ini tutup lebih awal pukul 14:00 karena ada maintenance alat hidrolik."><?= e($config['pesan_pengumuman'] ?? '') ?></textarea>
                            <small class="text-secondary">Pesan ini akan tampil di banner pengumuman jika diisi.</small>
                        </div>
                    </div>

                    <!-- Bagian 2: Profil & Kontak Bengkel -->
                    <h6 class="text-info text-uppercase fw-bold small mb-3 border-bottom border-secondary pb-2">
                        2. Identitas Bengkel & Kontak Publik
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Nama Bengkel</label>
                            <input type="text" name="nama_bengkel" class="form-control bg-dark text-white border-secondary" value="<?= e($config['nama_bengkel']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Slogan / Tagline</label>
                            <input type="text" name="slogan" class="form-control bg-dark text-white border-secondary" value="<?= e($config['slogan']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Nomor WhatsApp Konsultasi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-success"><i class="bi bi-whatsapp"></i></span>
                                <input type="text" name="no_whatsapp" class="form-control bg-dark text-white border-secondary" value="<?= e($config['no_whatsapp']) ?>" placeholder="6281234567890" required>
                            </div>
                            <small class="text-secondary">Gunakan format internasional (contoh: 6281234567890)</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Telepon Kantor / Bengkel</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-info"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="no_telepon" class="form-control bg-dark text-white border-secondary" value="<?= e($config['no_telepon']) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label text-white small fw-bold">Email Resmi</label>
                            <input type="email" name="email" class="form-control bg-dark text-white border-secondary" value="<?= e($config['email']) ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label text-white small fw-bold">Alamat Fisik Lengkap</label>
                            <textarea name="alamat" rows="2" class="form-control bg-dark text-white border-secondary" required><?= e($config['alamat']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="dashboard.php" class="btn btn-secondary px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i>Simpan Pengaturan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
