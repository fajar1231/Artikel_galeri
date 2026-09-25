<?php
session_start();
require_once 'koneksi.php';
require_once 'Clustering.php';

// Mengambil role dan username dari session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : (isset($_SESSION['level']) ? $_SESSION['level'] : 'Kepala Arsiparis');

// Jalankan proses K-Means dari kelas Clustering.php
$clustering = new Clustering();
$clustering->prosesKMeans();

// Ambil data hasil clustering dari database menggunakan koneksi Database class
$database = new Database();
$db = $database->getConnection();

$query_hasil = "SELECT c.*, a.judul 
                FROM clustering c 
                JOIN arsip a ON c.id_arsip = a.id_arsip 
                ORDER BY c.jumlah_view DESC";
$result_hasil = $db->query($query_hasil);

$data_arsip = [];
$count_populer = 0;
$count_sedang = 0;
$count_kurang = 0;

if ($result_hasil) {
    while ($row = $result_hasil->fetch_assoc()) {
        $label = $row['cluster_result'];
        if ($label == 'Populer') $count_populer++;
        elseif ($label == 'Sedang') $count_sedang++;
        elseif ($label == 'Kurang Diminati') $count_kurang++;

        $data_arsip[] = $row;
    }
}
$total_data = count($data_arsip);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses K-Means Clustering - Kepala Arsiparis</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js Pustaka Diagram Visualisasi -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f6f9; }
        
        /* STYLE SIDEBAR PERSIS DENGAN LAPORAN.PHP */
        .sidebar { 
            height: 100vh; 
            background-color: #2c3e50; 
            color: white; 
            position: fixed; 
            width: 250px; 
            padding-top: 20px; 
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .sidebar a { 
            color: #bdc3c7; 
            text-decoration: none; 
            padding: 12px 20px; 
            display: block; 
            font-size: 14px; 
            transition: all 0.2s ease;
        }
        .sidebar a:hover, .sidebar a.active { 
            background-color: #34495e; 
            color: white; 
        }

        /* AREA KONTEN UTAMA PERSIS DENGAN LAPORAN.PHP */
        .main-content { 
            margin-left: 250px; 
            padding: 30px; 
        }

        .card-stat {
            border: none;
            border-radius: 12px;
            transition: transform 0.2s ease;
        }
        .card-stat:hover {
            transform: translateY(-3px);
        }
        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR DASHBOARD -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <h5 class="fw-bold">GALERI ARTIKEL</h5>
            <span class="badge bg-primary text-capitalize"><?= htmlspecialchars($role) ?></span>
        </div>
        <hr class="mx-3">
        <a href="dashboard.php">Dashboard</a>
        
        <?php if ($role === 'admin'): ?>
            <a href="input_arsip.php">Tambah & Ajukan Arsip</a>
            <a href="kelola_arsip.php">Kelola Data Arsip</a>
        <?php else: ?>
            <a href="verifikasi_arsip.php">Verifikasi & Approval</a>
            <a href="hasil_clustering.php" class="active">Proses K-Means Clustering</a>
        <?php endif; ?>
        
        <a href="laporan.php">Laporan Rekapitulasi</a>
        <hr class="mx-3">
        <a href="logout.php" class="text-danger fw-bold">Logout</a>
    </div>

    <!-- AREA KONTEN UTAMA -->
    <div class="main-content">
        <div class="container-fluid p-0">
            
            <!-- HEADER HALAMAN -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>Komputasi K-Means Clustering</h3>
                    <p class="text-muted small mb-0">Perhitungan iterasi interaksi pengunjung berdasarkan algoritma K-Means dinamis.</p>
                </div>
                <button onclick="window.location.reload();" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="fa-solid fa-rotate me-1"></i> Hitung Ulang Iterasi
                </button>
            </div>

            <!-- 1. RINGKASAN KARTU STATISTIK (KPI CARDS) -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-stat bg-white p-3 shadow-sm border-start border-4 border-primary">
                        <div class="text-muted small">Total Arsip Terklaster</div>
                        <h2 class="fw-bold text-dark my-1"><?= $total_data ?></h2>
                        <span class="badge bg-light text-primary">Data Berstatus Disetujui</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat bg-white p-3 shadow-sm border-start border-4 border-success">
                        <div class="text-muted small">Cluster Populer</div>
                        <h2 class="fw-bold text-success my-1"><?= $count_populer ?></h2>
                        <span class="badge bg-success-subtle text-success"><?= $total_data > 0 ? round(($count_populer/$total_data)*100, 1) : 0 ?>% dari Total</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat bg-white p-3 shadow-sm border-start border-4 border-warning">
                        <div class="text-muted small">Cluster Sedang</div>
                        <h2 class="fw-bold text-warning my-1"><?= $count_sedang ?></h2>
                        <span class="badge bg-warning-subtle text-warning"><?= $total_data > 0 ? round(($count_sedang/$total_data)*100, 1) : 0 ?>% dari Total</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat bg-white p-3 shadow-sm border-start border-4 border-danger">
                        <div class="text-muted small">Cluster Kurang Diminati</div>
                        <h2 class="fw-bold text-danger my-1"><?= $count_kurang ?></h2>
                        <span class="badge bg-danger-subtle text-danger"><?= $total_data > 0 ? round(($count_kurang/$total_data)*100, 1) : 0 ?>% dari Total</span>
                    </div>
                </div>
            </div>

            <!-- 2. DIAGRAM VISUALISASI CHART.JS (BAR CHART ONLY) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3 p-4">
                        <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-chart-column me-2 text-primary"></i>Sebaran Jumlah Artikel Per Cluster</h5>
                        <div class="chart-container">
                            <canvas id="barChartCluster"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. TABEL DETAIL INTEGRASI KOMPUTASI K-MEANS -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-table-list me-2 text-primary"></i>Hasil Penentuan Cluster (Tabel Clustering)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">No</th>
                                <th>Judul Dokumen Artikel</th>
                                <th class="text-center">Jumlah Akses</th>
                                <th class="text-center">Hasil Cluster</th>
                                <th class="text-center">Rekomendasi Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_data > 0): ?>
                                <?php $no = 1; foreach ($data_arsip as $item): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted"><?= $no++ ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($item['judul']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary px-3 py-2"><i class="fa-regular fa-eye me-1"></i> <?= $item['jumlah_view'] ?> Dilihat</span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($item['cluster_result'] == 'Populer'): ?>
                                                <span class="badge bg-success px-3 py-2"><i class="fa-solid fa-fire me-1"></i> Populer</span>
                                            <?php elseif ($item['cluster_result'] == 'Sedang'): ?>
                                                <span class="badge bg-warning text-dark px-3 py-2"><i class="fa-solid fa-check me-1"></i> Sedang</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger px-3 py-2"><i class="fa-solid fa-circle-exclamation me-1"></i> Kurang Diminati</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($item['cluster_result'] == 'Populer'): ?>
                                                <span class="text-success small fw-bold">Unggulkan di Halaman Utama</span>
                                            <?php elseif ($item['cluster_result'] == 'Sedang'): ?>
                                                <span class="text-muted small">Pertahankan Publikasi</span>
                                            <?php else: ?>
                                                <span class="text-danger small">Evaluasi Deskripsi / Promosi</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data arsip yang dikelompokkan (Minimal 3 data disetujui).</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- SCRIPT CHART.JS -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const countPopuler = <?= $count_populer ?>;
            const countSedang = <?= $count_sedang ?>;
            const countKurang = <?= $count_kurang ?>;

            // Diagram Batang (Bar Chart)
            const ctxBar = document.getElementById('barChartCluster').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['Populer', 'Sedang', 'Kurang Diminati'],
                    datasets: [{
                        label: 'Jumlah Arsip',
                        data: [countPopuler, countSedang, countKurang],
                        backgroundColor: [
                            'rgba(25, 135, 84, 0.85)',
                            'rgba(255, 193, 7, 0.85)',
                            'rgba(220, 53, 69, 0.85)'
                        ],
                        borderColor: [
                            '#198754',
                            '#ffc107',
                            '#dc3545'
                        ],
                        borderWidth: 1.5,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>