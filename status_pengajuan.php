<?php
// file: status_pengajuan.php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$id_admin = $_SESSION['user_id'];

// Query Status Utama: Mengambil catatan revisi/penolakan dari tabel riwayat_arsip
$query = "SELECT a.id_arsip, a.judul, a.status, k.nama_kategori, 
                 (SELECT ra.catatan 
                  FROM riwayat_arsip ra 
                  WHERE ra.id_arsip = a.id_arsip AND ra.catatan IS NOT NULL AND ra.catatan != '' 
                  ORDER BY ra.id_riwayat DESC LIMIT 1) AS catatan_terbaru
          FROM arsip a
          LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
          WHERE a.id_admin = ?
          ORDER BY a.id_arsip DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $id_admin);
$stmt->execute();
$result = $stmt->get_result();

// Query Log History Seluruh Aktivitas Pengajuan Admin Ini
$query_log = "SELECT ra.*, a.judul 
              FROM riwayat_arsip ra
              JOIN arsip a ON ra.id_arsip = a.id_arsip
              WHERE a.id_admin = ?
              ORDER BY ra.id_riwayat DESC";
$stmt_log = $db->prepare($query_log);
$stmt_log->bind_param("i", $id_admin);
$stmt_log->execute();
$result_log = $stmt_log->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pengajuan Arsip - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar { height: 100vh; background-color: #2c3e50; color: white; position: fixed; width: 250px; padding-top: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 20px; display: block; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { margin-left: 250px; padding: 30px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-4">
        <h5 class="fw-bold">GALERI ARSIP</h5>
        <span class="badge bg-primary text-capitalize"><?= htmlspecialchars($_SESSION['role']) ?></span>
    </div>
    <hr class="mx-3">
    <a href="dashboard.php">Dashboard</a>
    <a href="input_arsip.php">Tambah & Ajukan Arsip</a>
    <a href="kelola_arsip.php">Kelola Data Arsip</a>
    <a href="status_pengajuan.php" class="active">Lihat Status Pengajuan</a>
    <a href="laporan.php">Laporan Rekapitulasi</a>
    <hr class="mx-3">
    <a href="logout.php" class="text-danger fw-bold">Logout</a>
</div>

<div class="main-content">
    <h2 class="mb-4">Status Pengajuan Arsip Anda</h2>

    <?php if (isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm">
            <?= htmlspecialchars($_SESSION['flash_msg']) ?>
        </div>
        <?php unset($_SESSION['flash_msg']); ?>
    <?php endif; ?>

    <!-- TABEL 1: DAFTAR STATUS PENGARSPAN SAAT INI -->
    <div class="card p-4 border-0 shadow-sm bg-white mb-5">
        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-card-checklist"></i> Ringkasan Status Dokumen</h5>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Judul Arsip</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Catatan / Keterangan Revisi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['judul']) ?></td>
                                <td><?= htmlspecialchars($row['nama_kategori'] ? $row['nama_kategori'] : 'Tanpa Kategori') ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($row['status'] === 'Disetujui'): ?>
                                        <span class="badge bg-success">Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php if ($row['status'] === 'Ditolak'): ?>
                                        <div class="mb-2">
                                            <span class="text-danger fw-bold">Catatan Revisi:</span> 
                                            <?= htmlspecialchars($row['catatan_terbaru'] ? $row['catatan_terbaru'] : 'Tidak ada catatan tertulis.') ?>
                                        </div>
                                        <a href="edit_arsip.php?id=<?= $row['id_arsip'] ?>" class="btn btn-sm btn-warning fw-bold text-dark">
                                            <i class="bi bi-pencil-square"></i> Perbaiki & Ajukan Kembali
                                        </a>
                                    <?php elseif ($row['status'] === 'Disetujui'): ?>
                                        <span class="text-success"><i class="bi bi-check-circle"></i> Arsip aktif di galeri publik</span>
                                    <?php else: ?>
                                        <span class="text-secondary"><i class="bi bi-hourglass-split"></i> Menunggu pemeriksaan Kepala Arsiparis</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada pengajuan arsip.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL 2: RIWAYAT / LOG AKTIVITAS LENGKAP -->
    <div class="card p-4 border-0 shadow-sm bg-white">
        <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-clock-history"></i> Riwayat & Log Aktivitas Pengajuan</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">No</th>
                        <th style="width: 150px;">Waktu Log</th>
                        <th>Judul Arsip</th>
                        <th style="width: 130px;">Aktor / User</th>
                        <th style="width: 150px;">Aksi</th>
                        <th>Catatan / Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_log && $result_log->num_rows > 0): ?>
                        <?php $no_log = 1; while ($log = $result_log->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center"><?= $no_log++ ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($log['tanggal'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($log['judul']) ?></td>
                                <td><?= htmlspecialchars($log['nama_user']) ?> (<?= htmlspecialchars($log['role_user']) ?>)</td>
                                <td>
                                    <?php if (strpos($log['aksi'], 'Disetujui') !== false): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars($log['aksi']) ?></span>
                                    <?php elseif (strpos($log['aksi'], 'Ditolak') !== false || strpos($log['aksi'], 'Hapus') !== false): ?>
                                        <span class="badge bg-danger"><?= htmlspecialchars($log['aksi']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($log['aksi']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($log['catatan'] ? $log['catatan'] : '-') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada rekam riwayat pengajuan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>