<?php
// file: dashboard.php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// 1. STATISTIK RINGKASAN
if ($role === 'admin') {
    // Total Pengajuan Admin Ini
    $q_total = $db->query("SELECT COUNT(*) as total FROM arsip WHERE id_admin = '$user_id'");
    $total_arsip = $q_total->fetch_assoc()['total'];

    $q_pending = $db->query("SELECT COUNT(*) as total FROM arsip WHERE id_admin = '$user_id' AND status = 'Pending'");
    $total_pending = $q_pending->fetch_assoc()['total'];

    $q_setuju = $db->query("SELECT COUNT(*) as total FROM arsip WHERE id_admin = '$user_id' AND status = 'Disetujui'");
    $total_setuju = $q_setuju->fetch_assoc()['total'];

    $q_tolak = $db->query("SELECT COUNT(*) as total FROM arsip WHERE id_admin = '$user_id' AND status = 'Ditolak'");
    $total_tolak = $q_tolak->fetch_assoc()['total'];
} else {
    // Total Keseluruhan Sistem untuk Kepala Arsiparis
    $q_total = $db->query("SELECT COUNT(*) as total FROM arsip");
    $total_arsip = $q_total->fetch_assoc()['total'];

    $q_pending = $db->query("SELECT COUNT(*) as total FROM arsip WHERE status = 'Pending'");
    $total_pending = $q_pending->fetch_assoc()['total'];

    $q_setuju = $db->query("SELECT COUNT(*) as total FROM arsip WHERE status = 'Disetujui'");
    $total_setuju = $q_setuju->fetch_assoc()['total'];

    $q_tolak = $db->query("SELECT COUNT(*) as total FROM arsip WHERE status = 'Ditolak'");
    $total_tolak = $q_tolak->fetch_assoc()['total'];
}

// 2. QUERY AMBIL HISTORY / LOG AKTIVITAS TERBARU DARI DATABASE
if ($role === 'admin') {
    $query_riwayat = "SELECT ra.*, a.judul 
                      FROM riwayat_arsip ra 
                      LEFT JOIN arsip a ON ra.id_arsip = a.id_arsip 
                      WHERE a.id_admin = '$user_id' OR ra.nama_user = '$username'
                      ORDER BY ra.id_riwayat DESC LIMIT 10";
} else {
    $query_riwayat = "SELECT ra.*, a.judul 
                      FROM riwayat_arsip ra 
                      LEFT JOIN arsip a ON ra.id_arsip = a.id_arsip 
                      ORDER BY ra.id_riwayat DESC LIMIT 10";
}
$result_riwayat = $db->query($query_riwayat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Galeri Artikel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar { height: 100vh; background-color: #2c3e50; color: white; position: fixed; width: 250px; padding-top: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 20px; display: block; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-stat { border-radius: 10px; border: none; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-4">
        <h5 class="fw-bold">GALERI ARTIKEL</h5>
        <span class="badge bg-primary text-capitalize"><?= htmlspecialchars($role) ?></span>
    </div>
    <hr class="mx-3">
    <a href="dashboard.php" class="active">Dashboard</a>
    
    <?php if ($role === 'admin'): ?>
        <a href="input_arsip.php">Tambah & Ajukan Artikel</a>
        <a href="kelola_arsip.php">Kelola Data Artikel</a>
    <?php else: ?>
        <a href="verifikasi_arsip.php">Verifikasi & Approval</a>
        <a href="hasil_clustering.php">Proses K-Means Clustering</a>
    <?php endif; ?>
    
    <a href="laporan.php">Laporan Rekapitulasi</a>
    <hr class="mx-3">
    <a href="logout.php" class="text-danger fw-bold">Logout</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Selamat Datang, <?= htmlspecialchars($username) ?>!</h2>
            <p class="text-muted small mb-0">Sistem Informasi Galeri Artikel Tapak Tilas Digital</p>
        </div>
        <span class="badge bg-dark p-2 fs-6"><i class="bi bi-calendar3"></i> <?= date('d F Y') ?></span>
    </div>

    <!-- KARTU STATISTIK RINGKASAN -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-stat bg-primary text-white p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-white-50">Total Artikel</h6>
                        <h3 class="fw-bold mb-0"><?= $total_arsip ?></h3>
                    </div>
                    <i class="bi bi-journal-album fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat bg-warning text-dark p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-dark-50">Menunggu (Pending)</h6>
                        <h3 class="fw-bold mb-0"><?= $total_pending ?></h3>
                    </div>
                    <i class="bi bi-clock-history fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat bg-success text-white p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-white-50">Disetujui (ACC)</h6>
                        <h3 class="fw-bold mb-0"><?= $total_setuju ?></h3>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat bg-danger text-white p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-white-50">Ditolak</h6>
                        <h3 class="fw-bold mb-0"><?= $total_tolak ?></h3>
                    </div>
                    <i class="bi bi-x-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL RIWAYAT / HISTORY AKTIVITAS VERIFIKASI TERBARU -->
    <div class="card p-4 border-0 shadow-sm bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-primary">
                <i class="bi bi-clock-history me-1"></i> Riwayat & Log Aktivitas Terbaru
            </h5>
            <a href="laporan.php" class="btn btn-sm btn-outline-primary fw-bold">Lihat Semua Laporan</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">No</th>
                        <th style="width: 170px;">Hari, Tanggal & Jam</th>
                        <th>Judul Dokumen Artikel</th>
                        <th style="width: 140px;">Oleh</th>
                        <th style="width: 150px;" class="text-center">Aksi / Status</th>
                        <th>Catatan / Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_riwayat && $result_riwayat->num_rows > 0): ?>
                        <?php 
                        $nama_hari = array(
                            'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
                            'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'
                        );
                        $no_r = 1; 
                        while ($r = $result_riwayat->fetch_assoc()): 
                            $day_english = date('D', strtotime($r['tanggal']));
                            $day_indo = isset($nama_hari[$day_english]) ? $nama_hari[$day_english] : $day_english;
                        ?>
                            <tr>
                                <td class="text-center"><?= $no_r++ ?></td>
                                <td class="fw-semibold text-secondary">
                                    <i class="bi bi-calendar-event me-1"></i><?= $day_indo ?>, <?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?> WIB
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($r['judul'] ? $r['judul'] : 'Dokumen Dihapus') ?></td>
                                <td><?= htmlspecialchars($r['nama_user']) ?> <span class="text-muted">(<?= htmlspecialchars($r['role_user']) ?>)</span></td>
                                <td class="text-center">
                                    <?php if (strpos($r['aksi'], 'Disetujui') !== false): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($r['aksi']) ?></span>
                                    <?php elseif (strpos($r['aksi'], 'Ditolak') !== false): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> <?= htmlspecialchars($r['aksi']) ?></span>
                                    <?php elseif (strpos($r['aksi'], 'Hapus') !== false): ?>
                                        <span class="badge bg-dark"><i class="bi bi-trash"></i> <?= htmlspecialchars($r['aksi']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark"><i class="bi bi-file-earmark-plus"></i> <?= htmlspecialchars($r['aksi']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['catatan'] ? $r['catatan'] : '-') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat aktivitas verifikasi yang terekam.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>