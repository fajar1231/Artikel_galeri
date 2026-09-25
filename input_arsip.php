<?php
// file: input_arsip.php
session_start();
require_once 'koneksi.php';
require_once 'ArsipManager.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$arsipManager = new ArsipManager();

$message = '';
$error = '';

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
$role = $_SESSION['role'];

$kategori_list = [];
$res_kategori = $db->query("SELECT * FROM kategori");
while ($row = $res_kategori->fetch_assoc()) {
    $kategori_list[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $id_kategori = $_POST['id_kategori'];
    $id_admin = $_SESSION['user_id'];

    $uploaded_images = [];
    $upload_dir = 'uploads/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($judul) && !empty($deskripsi) && !empty($id_kategori) && !empty($_FILES['gambar']['name'][0])) {
        $total_files = count($_FILES['gambar']['name']);
        $upload_ok = true;

        for ($i = 0; $i < $total_files; $i++) {
            $gambar_name = $_FILES['gambar']['name'][$i];
            $gambar_tmp = $_FILES['gambar']['tmp_name'][$i];
            
            $file_extension = pathinfo($gambar_name, PATHINFO_EXTENSION);
            $unique_gambar_name = time() . '_' . uniqid() . '_' . $i . '.' . $file_extension;
            $target_file = $upload_dir . $unique_gambar_name;

            if (move_uploaded_file($gambar_tmp, $target_file)) {
                $uploaded_images[] = $unique_gambar_name;
            } else {
                $upload_ok = false;
                break;
            }
        }

        if ($upload_ok) {
            $sukses = $arsipManager->ajukanArsip($judul, $deskripsi, $uploaded_images, $id_kategori, $id_admin);
            if ($sukses) {
                // Ambil ID arsip yang baru saja ditambahkan
                $id_arsip_baru = $db->insert_id;

                // Jika insert_id tidak tertangkap langsung dari $db, ambil ID terbaru berdasarkan id_admin
                if (!$id_arsip_baru) {
                    $res_last = $db->query("SELECT id_arsip FROM arsip WHERE id_admin = '$id_admin' ORDER BY id_arsip DESC LIMIT 1");
                    if ($res_last && $res_last->num_rows > 0) {
                        $id_arsip_baru = $res_last->fetch_assoc()['id_arsip'];
                    }
                }

                // Simpan pencatatan riwayat ke tabel riwayat_arsip
                if ($id_arsip_baru) {
                    $stmt_riwayat = $db->prepare("INSERT INTO riwayat_arsip (id_arsip, nama_user, role_user, aksi, catatan) VALUES (?, ?, ?, 'Pengajuan Artikel Baru', 'Artikel telah diajukan dan menunggu verifikasi Kepala Arsiparis.')");
                    $stmt_riwayat->bind_param("iss", $id_arsip_baru, $username, $role);
                    $stmt_riwayat->execute();
                }

                $message = "Arsip baru beserta " . count($uploaded_images) . " gambar dokumentasi berhasil diajukan!";
            } else {
                $error = "Gagal menyimpan data arsip ke database.";
            }
        } else {
            $error = "Terjadi kesalahan saat mengunggah salah satu gambar.";
        }
    } else {
        $error = "Semua kolom input beserta minimal 1 file gambar wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah & Ajukan Arsip - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <h5 class="fw-bold">GALERI ARTIKEL</h5>
        <span class="badge bg-primary text-capitalize"><?= htmlspecialchars($_SESSION['role']) ?></span>
    </div>
    <hr class="mx-3">
    <a href="dashboard.php">Dashboard</a>
    <a href="input_arsip.php" class="active">Tambah & Ajukan Artikel</a>
    <a href="kelola_arsip.php">Kelola Data Artikel</a>
    <!-- <a href="status_pengajuan.php">Lihat Status Pengajuan</a> -->
    <a href="laporan.php">Laporan Rekapitulasi</a>
    <hr class="mx-3">
    <a href="logout.php" class="text-danger fw-bold">Logout</a>
</div>

<div class="main-content">
    <h2 class="mb-4">Form Pengajuan Artikel Baru (Multi-Gambar)</h2>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success border-0 shadow-sm"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card p-4 border-0 shadow-sm bg-white">
        <form action="input_arsip.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="judul" class="form-label fw-bold small">Judul Artikel Tapak Tilas</label>
                <input type="text" name="judul" id="judul" class="form-control" required placeholder="Contoh: Kompleks Candi Tigaraksa">
            </div>

            <div class="mb-3">
                <label for="id_kategori" class="form-label fw-bold small">Kategori</label>
                <select name="id_kategori" id="id_kategori" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($kategori_list as $kat): ?>
                        <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label fw-bold small">Deskripsi/Sejarah Singkat</label>
                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="5" required placeholder="Tuliskan cerita sejarah tapak tilas daerah..."></textarea>
            </div>

            <div class="mb-4">
                <label for="gambar" class="form-label fw-bold small">Foto-Foto Dokumentasi (Bisa Pilih Lebih dari 1 Gambar)</label>
                <input type="file" name="gambar[]" id="gambar" class="form-control" accept="image/*" multiple required>
            </div>

            <button type="submit" class="btn btn-primary px-4 fw-bold">Kirim Pengajuan</button>
        </form>
    </div>
</div>

</body>
</html>