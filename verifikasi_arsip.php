<?php
// file: verifikasi_arsip.php
session_start();
require_once 'koneksi.php';
require_once 'ArsipManager.php';

// Proteksi halaman: hanya boleh diakses oleh Kepala Arsiparis
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepala_arsiparis') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$arsipManager = new ArsipManager();

$message = '';
$error = '';

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Kepala Arsiparis';
$role = $_SESSION['role'];

// 1. PROSES HAPUS ARSIP OLEH KEPALA ARSIPARIS
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_arsip_hapus = intval($_GET['id']);

    // Ambil judul arsip untuk dicatat ke riwayat sebelum dihapus
    $query_info = "SELECT judul FROM arsip WHERE id_arsip = ?";
    $stmt_info = $db->prepare($query_info);
    $stmt_info->bind_param("i", $id_arsip_hapus);
    $stmt_info->execute();
    $res_info = $stmt_info->get_result();

    if ($res_info->num_rows > 0) {
        $judul_arsip = $res_info->fetch_assoc()['judul'];

        // LANGKAH A: Simpan riwayat PENGHAPUSAN TERLEBIH DAHULU (Sebelum arsip dihapus)
        $stmt_riwayat = $db->prepare("INSERT INTO riwayat_arsip (id_arsip, nama_user, role_user, aksi, catatan) VALUES (?, ?, ?, 'Penghapusan Arsip', ?)");
        $catatan_hapus = "Arsip '" . $judul_arsip . "' telah dihapus permanen dari sistem.";
        $stmt_riwayat->bind_param("isss", $id_arsip_hapus, $username, $role, $catatan_hapus);
        $stmt_riwayat->execute();

        // LANGKAH B: Hapus berkas fisik gambar di folder uploads
        $query_img = "SELECT nama_file FROM arsip_gambar WHERE id_arsip = ?";
        $stmt_img = $db->prepare($query_img);
        $stmt_img->bind_param("i", $id_arsip_hapus);
        $stmt_img->execute();
        $res_img = $stmt_img->get_result();
        
        $upload_dir = 'uploads/';
        while ($img_data = $res_img->fetch_assoc()) {
            $file_path = $upload_dir . $img_data['nama_file'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // LANGKAH C: Hapus data arsip utama dari database (otomatis CASCADE ke arsip_gambar & riwayat_arsip)
        $query_delete = "DELETE FROM arsip WHERE id_arsip = ?";
        $stmt_delete = $db->prepare($query_delete);
        $stmt_delete->bind_param("i", $id_arsip_hapus);

        if ($stmt_delete->execute()) {
            $message = "Arsip berhasil dihapus secara permanen dari sistem!";
        } else {
            $error = "Gagal menghapus arsip dari database.";
        }
    } else {
        $error = "Data arsip tidak ditemukan.";
    }
}

// 2. PROSES AKSI VERIFIKASI (Setujui atau Tolak)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_arsip = intval($_POST['id_arsip']);
    $id_arsiparis = $_SESSION['user_id'];
    $action = $_POST['action'];

    if ($action === 'setuju') {
        if ($arsipManager->setujuiArsip($id_arsip)) {
            // Update tanggal verifikasi
            $db->query("UPDATE arsip SET tanggal_verifikasi = NOW() WHERE id_arsip = '$id_arsip'");

            // Catat ke tabel riwayat_arsip
            $stmt_riwayat = $db->prepare("INSERT INTO riwayat_arsip (id_arsip, nama_user, role_user, aksi, catatan) VALUES (?, ?, ?, 'Verifikasi - Disetujui', 'Pengajuan arsip telah disetujui dan dipublikasikan.')");
            $stmt_riwayat->bind_param("iss", $id_arsip, $username, $role);
            $stmt_riwayat->execute();

            $message = "Arsip berhasil disetujui dan dipublikasikan!";
        } else {
            $error = "Gagal memperbarui status arsip.";
        }
    } elseif ($action === 'tolak') {
        $catatan = trim($_POST['catatan']);
        if (!empty($catatan)) {
            if ($arsipManager->tolakArsip($id_arsip, $id_arsiparis, $catatan)) {
                // Update tanggal verifikasi
                $db->query("UPDATE arsip SET tanggal_verifikasi = NOW() WHERE id_arsip = '$id_arsip'");

                // Catat ke tabel riwayat_arsip
                $stmt_riwayat = $db->prepare("INSERT INTO riwayat_arsip (id_arsip, nama_user, role_user, aksi, catatan) VALUES (?, ?, ?, 'Verifikasi - Ditolak', ?)");
                $stmt_riwayat->bind_param("isss", $id_arsip, $username, $role, $catatan);
                $stmt_riwayat->execute();

                $message = "Arsip ditolak dan catatan revisi telah dikirim.";
            } else {
                $error = "Gagal menolak pengajuan arsip.";
            }
        } else {
            $error = "Harap berikan catatan revisi jika menolak pengajuan!";
        }
    }
}

// Ambil seluruh arsip yang statusnya masih 'Pending'
$query_pending = "SELECT a.id_arsip, a.judul, a.deskripsi, k.nama_kategori, ad.username AS nama_admin
                  FROM arsip a
                  LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
                  LEFT JOIN admin ad ON a.id_admin = ad.id_admin
                  WHERE a.status = 'Pending'
                  ORDER BY a.id_arsip ASC";
$result_pending = $db->query($query_pending);

// Ambil seluruh arsip yang statusnya sudah 'Disetujui' atau 'Ditolak'
$query_all = "SELECT a.id_arsip, a.judul, a.status, k.nama_kategori, ad.username AS nama_admin
              FROM arsip a
              LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
              LEFT JOIN admin ad ON a.id_admin = ad.id_admin
              WHERE a.status != 'Pending'
              ORDER BY a.id_arsip DESC";
$result_all = $db->query($query_all);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi & Kelola Artikel - Kepala Arsiparis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar { height: 100vh; background-color: #2c3e50; color: white; position: fixed; width: 250px; padding-top: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 20px; display: block; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { margin-left: 250px; padding: 30px; }
        .thumbnail-table { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-4">
        <h5 class="fw-bold">GALERI ARTIKEL</h5>
        <span class="badge bg-primary text-capitalize"><?= htmlspecialchars($_SESSION['role']) ?></span>
    </div>
    <hr class="mx-3">
    <a href="dashboard.php">Dashboard</a>
    <a href="verifikasi_arsip.php" class="active">Verifikasi & Approval</a>
    <a href="hasil_clustering.php">Proses K-Means Clustering</a>
    <a href="laporan.php">Laporan Rekapitulasi</a>

    <hr class="mx-3">
    <a href="logout.php" class="text-danger fw-bold">Logout</a>
</div>

<div class="main-content">
    <h2 class="mb-4">Panel Kontrol Kepala Arsiparis</h2>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success border-0 shadow-sm"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- BAGIAN 1: VERIFIKASI ARSIP PENDING -->
    <div class="card p-4 border-0 shadow-sm bg-white mb-5">
        <h5 class="fw-bold mb-4 text-warning"><i class="bi bi-clock-history"></i> Menunggu Verifikasi (Pending)</h5>
        <?php if ($result_pending && $result_pending->num_rows > 0): ?>
            <?php while ($row = $result_pending->fetch_assoc()): ?>
                <div class="card p-3 border mb-3 bg-light">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Dokumentasi Gambar:</label>
                            <div class="row g-2">
                                <?php
                                $id_arsip = $row['id_arsip'];
                                $query_gambar = "SELECT nama_file FROM arsip_gambar WHERE id_arsip = ?";
                                $stmt_gambar = $db->prepare($query_gambar);
                                $stmt_gambar->bind_param("i", $id_arsip);
                                $stmt_gambar->execute();
                                $res_gambar = $stmt_gambar->get_result();
                                
                                if ($res_gambar->num_rows > 0): 
                                    while ($img = $res_gambar->fetch_assoc()): 
                                ?>
                                        <div class="col-6">
                                            <img src="uploads/<?= htmlspecialchars($img['nama_file']) ?>" class="img-fluid rounded border" style="height: 60px; width: 100%; object-fit: cover;" alt="Dokumen">
                                        </div>
                                <?php 
                                    endwhile; 
                                else: 
                                ?>
                                    <div class="col-12 text-center py-2 bg-white border rounded">
                                        <small class="text-muted">Tidak ada gambar</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="fw-bold"><?= htmlspecialchars($row['judul']) ?></h5>
                                <span class="badge bg-secondary"><?= htmlspecialchars($row['nama_kategori']) ?></span>
                            </div>
                            <small class="text-muted d-block mb-2">Diajukan oleh: <strong><?= htmlspecialchars($row['nama_admin']) ?></strong></small>
                            <p class="text-secondary small" style="text-align: justify;"><?= nl2br(htmlspecialchars($row['deskripsi'])) ?></p>
                            
                            <hr>
                            
                            <form action="verifikasi_arsip.php" method="POST" class="row g-2 align-items-center">
                                <input type="hidden" name="id_arsip" value="<?= $row['id_arsip'] ?>">
                                <div class="col-md-6">
                                    <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Tulis catatan revisi jika menolak..." autocomplete="off">
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="submit" name="action" value="tolak" class="btn btn-sm btn-danger px-3">Tolak</button>
                                    <button type="submit" name="action" value="setuju" class="btn btn-sm btn-success px-4 ms-2 fw-bold">Setujui & Publikasikan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted mb-0">Tidak ada pengajuan yang perlu diverifikasi.</p>
        <?php endif; ?>
    </div>

    <!-- BAGIAN 2: DAFTAR SEMUA ARSIP & TOMBOL HAPUS -->
    <div class="card p-4 border-0 shadow-sm bg-white">
        <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-folder2-open"></i> Daftar Arsip Terproses & Fitur Hapus</h5>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th style="width: 80px;">Foto</th>
                        <th>Judul Artikel</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th style="width: 150px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_all && $result_all->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $result_all->fetch_assoc()): ?>
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
                                    $cover_img = 'https://via.placeholder.com/60x40?text=No';
                                    if ($res_img->num_rows === 1) {
                                        $img_data = $res_img->fetch_assoc();
                                        $cover_img = 'uploads/' . $img_data['nama_file'];
                                    }
                                    ?>
                                    <img src="<?= $cover_img ?>" class="thumbnail-table border" alt="Cover">
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($row['judul']) ?></td>
                                <td><?= htmlspecialchars($row['nama_kategori'] ? $row['nama_kategori'] : 'Tanpa Kategori') ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Disetujui'): ?>
                                        <span class="badge bg-success">Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="verifikasi_arsip.php?action=delete&id=<?= $row['id_arsip'] ?>" 
                                       class="btn btn-sm btn-danger fw-bold w-100" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus permanen arsip ini? Tindakan ini tidak dapat dibatalkan.');">
                                        <i class="bi bi-trash"></i> Hapus Artikel
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada Artikel yang disetujui atau ditolak.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>