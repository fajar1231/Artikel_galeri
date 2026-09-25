<?php
// file: edit_arsip.php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$message = '';
$error = '';

// Ambil ID Arsip dari Parameter URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: kelola_arsip.php");
    exit();
}

$id_arsip = intval($_GET['id']);

// Ambil Data Arsip Saat Ini
$query_arsip = "SELECT * FROM arsip WHERE id_arsip = ? AND id_admin = ?";
$stmt = $db->prepare($query_arsip);
$stmt->bind_param("ii", $id_arsip, $user_id);
$stmt->execute();
$res_arsip = $stmt->get_result();

if ($res_arsip->num_rows === 0) {
    header("Location: kelola_arsip.php");
    exit();
}

$arsip = $res_arsip->fetch_assoc();

// Ambil Daftar Kategori untuk Dropdown
$kategori_list = [];
$res_kategori = $db->query("SELECT * FROM kategori");
while ($row = $res_kategori->fetch_assoc()) {
    $kategori_list[] = $row;
}

// PROSES SIMPAN EDIT ARSIP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $id_kategori = intval($_POST['id_kategori']);

    if (!empty($judul) && !empty($deskripsi) && !empty($id_kategori)) {
        
        // 1. Update Data Arsip & Ubah Status Kembali ke 'Pending' untuk Verifikasi Ulang
        $query_update = "UPDATE arsip SET judul = ?, deskripsi = ?, id_kategori = ?, status = 'Pending' WHERE id_arsip = ? AND id_admin = ?";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->bind_param("ssiii", $judul, $deskripsi, $id_kategori, $id_arsip, $user_id);

        if ($stmt_update->execute()) {

            // 2. Jika Ada File Gambar Baru Dilampirkan
            if (!empty($_FILES['gambar']['name'][0])) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $total_files = count($_FILES['gambar']['name']);
                for ($i = 0; $i < $total_files; $i++) {
                    $gambar_name = $_FILES['gambar']['name'][$i];
                    $gambar_tmp = $_FILES['gambar']['tmp_name'][$i];
                    
                    if (!empty($gambar_name)) {
                        $file_extension = pathinfo($gambar_name, PATHINFO_EXTENSION);
                        $unique_gambar_name = time() . '_' . uniqid() . '_' . $i . '.' . $file_extension;
                        $target_file = $upload_dir . $unique_gambar_name;

                        if (move_uploaded_file($gambar_tmp, $target_file)) {
                            $stmt_img = $db->prepare("INSERT INTO arsip_gambar (id_arsip, nama_file) VALUES (?, ?)");
                            $stmt_img->bind_param("is", $id_arsip, $unique_gambar_name);
                            $stmt_img->execute();
                        }
                    }
                }
            }

            // 3. Catat Riwayat ke Tabel riwayat_arsip
            $stmt_riwayat = $db->prepare("INSERT INTO riwayat_arsip (id_arsip, nama_user, role_user, aksi, catatan) VALUES (?, ?, ?, 'Revisi & Pengajuan Ulang', 'Artikel telah diperbarui dan diajukan ulang untuk verifikasi.')");
            $stmt_riwayat->bind_param("iss", $id_arsip, $username, $role);
            $stmt_riwayat->execute();

            // 4. Set Notifikasi & Redirect Kembali ke kelola_arsip.php
            $_SESSION['flash_msg'] = "Artikel '" . $judul . "' berhasil diperbarui dan diajukan kembali ke Kepala Arsiparis!";
            header("Location: kelola_arsip.php");
            exit();

        } else {
            $error = "Gagal memperbarui data artikel di database.";
        }
    } else {
        $error = "Semua kolom input wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Artikel - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar { height: 100vh; background-color: #2c3e50; color: white; position: fixed; width: 250px; padding-top: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 20px; display: block; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { margin-left: 250px; padding: 30px; }
        .img-preview { width: 100px; height: 70px; object-fit: cover; border-radius: 4px; }
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

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Edit Artikel Arsip</h2>
        <a href="kelola_arsip.php" class="btn btn-secondary fw-bold shadow-sm">
            <i class="bi bi-arrow-left"></i> Kembali ke Kelola Data
        </a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card p-4 border-0 shadow-sm bg-white">
        <form action="edit_arsip.php?id=<?= $id_arsip ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="judul" class="form-label fw-bold small">Judul Artikel Tapak Tilas</label>
                <input type="text" name="judul" id="judul" class="form-control" value="<?= htmlspecialchars($arsip['judul']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="id_kategori" class="form-label fw-bold small">Kategori</label>
                <select name="id_kategori" id="id_kategori" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($kategori_list as $kat): ?>
                        <option value="<?= $kat['id_kategori'] ?>" <?= ($kat['id_kategori'] == $arsip['id_kategori']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kat['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label fw-bold small">Deskripsi / Sejarah Singkat</label>
                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="5" required><?= htmlspecialchars($arsip['deskripsi']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Gambar Terpasang Saat Ini:</label>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <?php
                    $stmt_g = $db->prepare("SELECT * FROM arsip_gambar WHERE id_arsip = ?");
                    $stmt_g->bind_param("i", $id_arsip);
                    $stmt_g->execute();
                    $res_g = $stmt_g->get_result();
                    if ($res_g->num_rows > 0):
                        while ($g = $res_g->fetch_assoc()):
                    ?>
                        <img src="uploads/<?= htmlspecialchars($g['nama_file']) ?>" class="img-preview border shadow-sm" alt="Gambar">
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <span class="text-muted small">Tidak ada gambar terlampir.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-4">
                <label for="gambar" class="form-label fw-bold small">Tambah Gambar Lampiran Baru (Opsional)</label>
                <input type="file" name="gambar[]" id="gambar" class="form-control" accept="image/*" multiple>
                <small class="text-muted fs-7">*Biarkan kosong jika tidak ingin menambah gambar baru.</small>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="kelola_arsip.php" class="btn btn-light px-4 fw-bold">Batal</a>
                <button type="submit" class="btn btn-warning px-4 fw-bold text-dark">
                    <i class="bi bi-save"></i> Simpan Perubahan & Ajukan Ulang
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>