<?php
// file: laporan.php
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

// Fitur Export Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_Rekapitulasi_Arsip_" . date('Y-m-d') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $query = "SELECT a.id_arsip, a.judul, a.status, a.jumlah_view, a.tanggal_pengajuan, a.tanggal_verifikasi, k.nama_kategori, c.cluster_result
              FROM arsip a
              LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
              LEFT JOIN clustering c ON a.id_arsip = c.id_arsip
              ORDER BY a.id_arsip DESC";
    $result = $db->query($query);
    ?>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
    </style>
    <h2>LAPORAN REKAPITULASI DATA ARSIP</h2>
    <p>Dinas Perpustakaan dan Arsip Kabupaten Tangerang</p>
    <br>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Judul Dokumen Artikel</th>
                <th>Kategori</th>
                <th>Tgl Pengajuan</th>
                <th>Tgl Verifikasi</th>
                <th>Status</th>
                <th>Views</th>
                <th>Hasil</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['judul']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kategori'] ? $row['nama_kategori'] : 'Tanpa Kategori') ?></td>
                        <td class="text-center"><?= $row['tanggal_pengajuan'] ? date('d/m/Y H:i', strtotime($row['tanggal_pengajuan'])) : '-' ?></td>
                        <td class="text-center"><?= $row['tanggal_verifikasi'] ? date('d/m/Y H:i', strtotime($row['tanggal_verifikasi'])) : 'Belum diperiksa' ?></td>
                        <td class="text-center"><?= $row['status'] ?></td>
                        <td class="text-center"><?= $row['jumlah_view'] ?></td>
                        <td class="text-center">
                            <?php 
                            if ($row['status'] !== 'Disetujui') {
                                echo 'Belum Masuk Klaster';
                            } else {
                                echo $row['cluster_result'] ? $row['cluster_result'] : 'Menunggu Iterasi';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">Belum ada rekaman data artikel.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <br><br>
    <table>
        <tr>
            <td colspan="5" style="border:none;"></td>
            <td colspan="3" style="border:none; text-align:center;">
                Tangerang, <?= date('d F Y') ?><br>
                <?= $role === 'admin' ? 'Staf Administrasi' : 'Kepala Arsiparis' ?>,<br><br><br><br>
                <b><u><?= htmlspecialchars($username) ?></u></b>
            </td>
        </tr>
    </table>
    <?php
    exit();
}

// Pengambilan Data Utama Web Tampilan
$query = "SELECT a.id_arsip, a.judul, a.status, a.jumlah_view, a.tanggal_pengajuan, a.tanggal_verifikasi, k.nama_kategori, c.cluster_result
          FROM arsip a
          LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
          LEFT JOIN clustering c ON a.id_arsip = c.id_arsip
          ORDER BY a.id_arsip DESC";
$result = $db->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Arsip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar { height: 100vh; background-color: #2c3e50; color: white; position: fixed; width: 250px; padding-top: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 12px 20px; display: block; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { margin-left: 250px; padding: 30px; }
        
        .kop-surat { display: none; }
        .print-title { display: none; }
        
        /* Pengaturan Cetak / Print PDF */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            body {
                background-color: #fff !important;
                padding: 15mm;
                font-size: 11pt;
            }

            .sidebar, .btn-action, hr, .text-muted-dashboard { 
                display: none !important; 
            }
            
            .main-content { 
                margin-left: 0 !important; 
                padding: 0 !important; 
            }
            
            .card { 
                border: none !important; 
                box-shadow: none !important; 
                padding: 0 !important;
            }

            /* Kop Surat Layout */
            .kop-surat {
                display: flex !important;
                align-items: center;
                border-bottom: 3px double #000;
                padding-bottom: 8px;
                margin-bottom: 15px;
            }
            .kop-logo { width: 85px; height: auto; margin-right: 15px; }
            .kop-text { text-align: center; flex-grow: 1; }
            .kop-text h4 { margin: 0; font-weight: bold; font-size: 14pt; text-transform: uppercase; }
            .kop-text h3 { margin: 0; font-weight: bold; font-size: 16pt; text-transform: uppercase; }
            .kop-text p { margin: 2px 0 0 0; font-size: 9pt; line-height: 1.2; }

            .print-title {
                display: block !important;
                text-align: center;
                font-weight: bold;
                font-size: 12pt;
                text-transform: uppercase;
                margin-bottom: 15px;
                text-decoration: underline;
            }

            /* Perapihan Tabel Cetak PDF */
            .table-responsive { overflow: visible !important; }
            .table { 
                width: 100% !important; 
                border-collapse: collapse !important;
                font-size: 10pt !important;
            }
            .table th, .table td { 
                border: 1px solid #000 !important; 
                padding: 6px 8px !important;
                color: #000 !important;
            }
            .table thead th { 
                background-color: #e9ecef !important; 
                color: #000 !important;
                text-align: center !important;
            }

            /* Penandatangan */
            .print-footer { 
                display: flex !important; 
                margin-top: 30px; 
                page-break-inside: avoid;
            }
        }
        
        .print-footer { display: none; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-4">
        <h5 class="fw-bold">GALERI ARTIKEL</h5>
        <span class="badge bg-primary text-capitalize"><?= htmlspecialchars($role) ?></span>
    </div>
    <hr class="mx-3">
    <a href="dashboard.php">Dashboard</a>
    
    <?php if ($role === 'admin'): ?>
        <a href="input_arsip.php">Tambah & Ajukan Artikel</a>
        <a href="kelola_arsip.php">Kelola Data Artikel</a>
    <?php else: ?>
        <a href="verifikasi_arsip.php">Verifikasi & Approval</a>
        <a href="hasil_clustering.php">Proses K-Means Clustering</a>
    <?php endif; ?>
    
    <a href="laporan.php" class="active">Laporan Rekapitulasi</a>
    <hr class="mx-3">
    <a href="logout.php" class="text-danger fw-bold">Logout</a>
</div>

<div class="main-content">

    <!-- Header Kop Surat Instansi (Hanya Tampil Saat Cetak / Print) -->
    <div class="kop-surat">
        <!-- Ubah path src="assets/img/logo.png" sesuai lokasi file gambar logo Anda -->
        <img src="assets/img/logo.png" alt="Logo Instansi" class="kop-logo">
        <div class="kop-text">
            <h4>Pemerintah Kabupaten Tangerang</h4>
            <h3>Dinas Perpustakaan Dan Arsip</h3>
            <p>Jl. H. Abdul Hamid No.9, Kadu Agung, Kec. Tigaraksa, Kabupaten Tangerang, Banten 15720</p>
            <p>Email: dipusip@tangerangkab.go.id | Telepon: (021) 5990234</p>
        </div>
    </div>

    <!-- Judul Dokumen Resmi Cetak -->
    <div class="print-title">
        LAPORAN REKAPITULASI DATA ARTIKEL
    </div>

    <!-- Action Bar (Tombol Cetak & Ekspor) -->
    <div class="d-flex justify-content-end align-items-center mb-4">
        <div class="btn-action">
            <button onclick="window.print();" class="btn btn-success fw-bold shadow-sm me-2">
                <i class="bi bi-printer"></i> Cetak / Ekspor PDF
            </button>
            <a href="laporan.php?export=excel" class="btn btn-outline-success fw-bold shadow-sm">
                <i class="bi bi-file-earmark-excel"></i> Ekspor Excel
            </a>
        </div>
    </div>

    <div class="card p-4 border-0 shadow-sm bg-white">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 35px;" class="text-center">No</th>
                        <th>Judul Dokumen Artikel</th>
                        <th style="width: 120px;">Kategori</th>
                        <th style="width: 110px;" class="text-center">Tgl Pengajuan</th>
                        <th style="width: 110px;" class="text-center">Tgl Verifikasi</th>
                        <th style="width: 80px;" class="text-center">Status</th>
                        <th style="width: 60px;" class="text-center">Views</th>
                        <th style="width: 120px;" class="text-center">Hasil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['judul']) ?></td>
                                <td><?= htmlspecialchars($row['nama_kategori'] ? $row['nama_kategori'] : 'Tanpa Kategori') ?></td>
                                <td class="text-center small">
                                    <?= $row['tanggal_pengajuan'] ? date('d/m/Y H:i', strtotime($row['tanggal_pengajuan'])) : '-' ?>
                                </td>
                                <td class="text-center small">
                                    <?= $row['tanggal_verifikasi'] ? date('d/m/Y H:i', strtotime($row['tanggal_verifikasi'])) : '<span class="text-muted italic">Belum diperiksa</span>' ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <span class="text-warning fw-bold">Pending</span>
                                    <?php elseif ($row['status'] === 'Disetujui'): ?>
                                        <span class="text-success fw-bold">Disetujui</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold"><?= number_format($row['jumlah_view']) ?></td>
                                <td class="text-center">
                                    <?php 
                                    if ($row['status'] !== 'Disetujui') {
                                        echo '<span class="text-muted small italic">Belum Masuk Klaster</span>';
                                    } else {
                                        if ($row['cluster_result'] === 'Populer') {
                                            echo '<span class="text-success fw-bold">Populer</span>';
                                        } elseif ($row['cluster_result'] === 'Sedang') {
                                            echo '<span class="text-warning fw-bold text-dark">Sedang</span>';
                                        } elseif ($row['cluster_result'] === 'Kurang Diminati') {
                                            echo '<span class="text-danger fw-bold">Kurang Diminati</span>';
                                        } else {
                                            echo '<span class="text-secondary small">Menunggu Iterasi</span>';
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada rekaman data Artikel di dalam sistem.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Tanda Tangan Laporan Saat Dicetak -->
        <div class="row print-footer">
            <div class="col-7"></div>
            <div class="col-5 text-center">
                <p class="mb-5">Tangerang, <?= date('d F Y') ?><br><?= $role === 'admin' ? 'Staf Administrasi' : 'Kepala Arsiparis' ?>,</p>
                <p class="fw-bold text-decoration-underline"><?= htmlspecialchars($username) ?></p>
            </div>
        </div>

    </div>
</div>

</body>
</html>