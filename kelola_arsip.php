<?php
// file: kelola_arsip.php
session_start();
require_once 'koneksi.php';

// Proteksi halaman: hanya boleh diakses oleh Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$id_admin = $_SESSION['user_id'];

// AMBIL DAFTAR ARSIP MILIK ADMIN YANG SEDANG LOGIN
$query = "SELECT a.id_arsip, a.judul, a.status, k.nama_kategori 
          FROM arsip a
          LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
          WHERE a.id_admin = ?
          ORDER BY a.id_arsip DESC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $id_admin);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Artikel - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar { height: 100vh; background-color: #2c3e50; color: white; position: fixed; width: 250px; padding-top: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 20px; display: block; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { margin-left: 250px; padding: 30px; }
        .thumbnail-table {
            width: 80px;
            height: 55px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<!-- Sidebar Navigasi Admin (Tanpa Menu Status Pengajuan) -->
<div class="sidebar">
    <div class="text-center mb-4">
        <h5 class="fw-bold">GALERI ARTIKEL</h5>
        <span class="badge bg-primary text-capitalize"><?= htmlspecialchars($_SESSION['role']) ?></span>
    </div>
    <hr class="mx-3">
    <a href="dashboard.php">Dashboard</a>
    <a href="input_arsip.php">Tambah & Ajukan Artikel</a>
    <a href="kelola_arsip.php" class="active">Kelola Data Artikel</a>
    <a href="laporan.php">Laporan Rekapitulasi</a>
    <hr class="mx-3">
    <a href="logout.php" class="text-danger fw-bold">Logout</a>
</div>

<!-- Konten Utama -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Kelola Data Artikel</h2>
        <a href="input_arsip.php" class="btn btn-primary fw-bold"><i class="bi bi-plus-circle"></i> Tambah Arsip Baru</a>
    </div>

    <!-- NOTIFIKASI FLASH SETELAH SELESAI EDIT -->
    <?php if (isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_msg']); ?>
    <?php endif; ?>

    <div class="card p-4 border-0 shadow-sm bg-white">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th style="width: 100px;">Foto</th>
                        <th>Judul Arsip</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th style="width: 120px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <?php
                                    $id_arsip = $row['id_arsip'];
                                    $query_img = "SELECT nama_file FROM arsip_gambar WHERE id_arsip = ? LIMIT 1";
                                    $stmt_img = $db->prepare($query_img);
                                    $stmt_img->bind_param("i", $id_arsip);
                                    $stmt_img->execute();
                                    $res_img = $stmt_img->get_result();
                                    $cover_img = 'https://via.placeholder.com/80x55?text=No+Image';
                                    if ($res_img->num_rows === 1) {
                                        $img_data = $res_img->fetch_assoc();
                                        $cover_img = 'uploads/' . $img_data['nama_file'];
                                    }
                                    ?>
                                    <img src="<?= $cover_img ?>" class="thumbnail-table border" alt="Thumbnail">
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['judul']) ?></td>
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
                                <td class="text-center">
                                    <a href="edit_arsip.php?id=<?= $row['id_arsip'] ?>" class="btn btn-sm btn-warning text-dark fw-bold w-100">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada data arsip yang Anda buat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JS Bootstrap untuk Menutup Notifikasi -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>