<?php
// Koneksi ke database MySQL
$koneksi = mysqli_connect("localhost", "root", "", "db_galeri_arsip");

// Cek koneksi
if (!$koneksi) {
    die("Koneksi ke basis data gagal: " . mysqli_connect_error());
}

// 1. Ambil ID Arsip dari URL
$id_arsip = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_arsip <= 0) {
    header("Location: index.php");
    exit();
}

// 2. OTOMATIS TAMBAH VIEW COUNT (+1) UNTUK ALGORITMA K-MEANS
$update_view = "UPDATE arsip SET jumlah_view = jumlah_view + 1 WHERE id_arsip = '$id_arsip'";
mysqli_query($koneksi, $update_view);

// 3. Query Ambil Detail Arsip & Kategori
$query_detail = "SELECT a.*, k.nama_kategori 
                 FROM arsip a 
                 LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
                 WHERE a.id_arsip = '$id_arsip' AND a.status = 'Disetujui'";
$result_detail = mysqli_query($koneksi, $query_detail);
$data_arsip = mysqli_fetch_assoc($result_detail);

// Jika arsip tidak ditemukan atau belum disetujui
if (!$data_arsip) {
    echo "<script>alert('Arsip tidak ditemukan atau belum dipublikasi!'); window.location='index.php';</script>";
    exit();
}

// 4. Query Ambil Seluruh Foto/Gambar Arsip Terkait
$query_gambar = "SELECT * FROM arsip_gambar WHERE id_arsip = '$id_arsip'";
$result_gambar = mysqli_query($koneksi, $query_gambar);

$daftar_gambar = [];
while ($img = mysqli_fetch_assoc($result_gambar)) {
    $daftar_gambar[] = $img['nama_file'];
}
$total_gambar = count($daftar_gambar);

// 5. QUERY DOKUMEN ARSIP SEBELUMNYA DAN SELANJUTNYA
// Arsip Sebelumnya (ID lebih kecil dari ID saat ini)
$query_prev = "SELECT id_arsip FROM arsip WHERE id_arsip < '$id_arsip' AND status = 'Disetujui' ORDER BY id_arsip DESC LIMIT 1";
$res_prev = mysqli_query($koneksi, $query_prev);
$data_prev = mysqli_fetch_assoc($res_prev);

// Arsip Selanjutnya (ID lebih besar dari ID saat ini)
$query_next = "SELECT id_arsip FROM arsip WHERE id_arsip > '$id_arsip' AND status = 'Disetujui' ORDER BY id_arsip ASC LIMIT 1";
$res_next = mysqli_query($koneksi, $query_next);
$data_next = mysqli_fetch_assoc($res_next);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data_arsip['judul']) ?> - Galeri Arsip Tapak Tilas</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-gallery-box {
            position: relative;
            background-color: #121212;
            height: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            overflow: hidden;
        }
        .main-gallery-img {
            max-height: 480px;
            max-width: 100%;
            object-fit: contain;
            cursor: zoom-in;
            transition: transform 0.2s ease;
        }
        .main-gallery-img:hover {
            opacity: 0.95;
        }
        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.65);
            color: #ffffff;
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10;
        }
        .nav-btn:hover {
            background: #0d6efd;
            color: #ffffff;
        }
        .nav-btn.prev { left: 15px; }
        .nav-btn.next { right: 15px; }
        
        .thumbnail-box {
            height: 85px;
            object-fit: cover;
            width: 100%;
            cursor: pointer;
            border: 3px solid transparent;
            border-radius: 6px;
            transition: all 0.2s ease;
            opacity: 0.7;
        }
        .thumbnail-box:hover, .thumbnail-box.active-thumb {
            border-color: #0d6efd;
            opacity: 1;
            transform: translateY(-2px);
        }
        .zoom-badge {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.75);
            color: white;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            pointer-events: none;
        }

        /* LIGHTBOX MODAL ZOOM */
        .custom-lightbox {
            display: none;
            position: fixed;
            z-index: 99999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .custom-lightbox-img {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(255,255,255,0.2);
        }
        .close-lightbox {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #ffffff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 100000;
        }
        .close-lightbox:hover {
            color: #ff4d4d;
        }
    </style>
</head>
<body class="bg-light">

    <!-- NAVIGASI UTAMA -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fa-solid fa-landmark me-2"></i>Galeri Arsip Tapak Tilas
            </a>
        </div>
    </nav>

    <!-- KONTEN DETAIL ARSIP -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <div class="card border-0 shadow-sm rounded overflow-hidden">
                    <div class="card-body p-4 p-md-5">
                        
                        <!-- Badge Kategori & Jumlah View -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-info text-dark fs-6">
                                <i class="fa-solid fa-tag me-1"></i> <?= htmlspecialchars($data_arsip['nama_kategori'] ?? 'Umum') ?>
                            </span>
                            <span class="text-muted small">
                                <i class="fa-regular fa-eye me-1"></i> <?= number_format($data_arsip['jumlah_view']) ?> Kali Dilihat
                            </span>
                        </div>

                        <!-- Judul Arsip -->
                        <h2 class="fw-bold mb-3 text-dark"><?= htmlspecialchars($data_arsip['judul']) ?></h2>
                        
                        <p class="text-muted small mb-4">
                            <i class="fa-regular fa-calendar-check me-1"></i> Diverifikasi pada: <?= date('d F Y', strtotime($data_arsip['tanggal_verifikasi'] ?? date('Y-m-d'))) ?>
                        </p>

                        <hr class="mb-4">

                        <!-- GALERI FOTO LENGKAP -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0"><i class="fa-solid fa-images me-2 text-primary"></i>Dokumentasi Visual (<?= $total_gambar ?> Foto)</h5>
                            </div>
                            
                            <!-- BINGKAI FOTO UTAMA -->
                            <div class="main-gallery-box shadow-sm">
                                <?php if ($total_gambar > 0): ?>
                                    <img id="fotoUtama" 
                                         src="uploads/<?= $daftar_gambar[0] ?>" 
                                         class="main-gallery-img" 
                                         alt="Dokumentasi Arsip"
                                         onclick="perbesarFoto()">

                                    <!-- Tombol Geser Foto Kiri & Kanan -->
                                    <?php if ($total_gambar > 1): ?>
                                        <button type="button" class="nav-btn prev" onclick="geserFoto(-1, event)">
                                            <i class="fa-solid fa-chevron-left"></i>
                                        </button>
                                        <button type="button" class="nav-btn next" onclick="geserFoto(1, event)">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </button>
                                    <?php endif; ?>

                                    
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/800x450?text=Foto+Tidak+Tersedia" class="main-gallery-img" alt="Foto Tidak Tersedia">
                                <?php endif; ?>
                            </div>

                            <!-- KISI THUMBNAIL FOTO -->
                            <?php if ($total_gambar > 1): ?>
                                <div class="row g-2 mt-2">
                                    <?php foreach ($daftar_gambar as $idx => $nama_file): ?>
                                        <div class="col-3 col-sm-2">
                                            <img src="uploads/<?= $nama_file ?>" 
                                                 class="thumbnail-box <?= ($idx == 0) ? 'active-thumb' : '' ?>" 
                                                 alt="Thumbnail"
                                                 onclick="pilihFoto(<?= $idx ?>)">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- DESKRIPSI LENGKAP ARSIP -->
                        <div class="mt-4">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-file-lines me-2 text-primary"></i>Ulasan & Deskripsi Sejarah</h5>
                            <div class="text-secondary lh-lg" style="text-align: justify;">
                                <?= nl2br(htmlspecialchars($data_arsip['deskripsi'])) ?>
                            </div>
                        </div>

                        <!-- TOMBOL NAVIGASI HALAMAN (SEBELUMNYA, KEMBALI, SELANJUTNYA) -->
                        <div class="mt-5 pt-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <!-- Tombol Arsip Sebelumnya -->
                            <?php if ($data_prev): ?>
                                <a href="detail_arsip.php?id=<?= $data_prev['id_arsip'] ?>" class="btn btn-outline-primary rounded-pill px-3">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Arsip Sebelumnya
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary rounded-pill px-3" disabled>
                                    <i class="fa-solid fa-arrow-left me-1"></i> Arsip Sebelumnya
                                </button>
                            <?php endif; ?>

                            <!-- Tombol Kembali ke Galeri Utama -->
                            <a href="index.php#koleksi-arsip" class="btn btn-secondary rounded-pill px-4">
                                <i class="fa-solid fa-grid-2 me-1"></i> Kembali ke Galeri
                            </a>

                            <!-- Tombol Arsip Selanjutnya -->
                            <?php if ($data_next): ?>
                                <a href="detail_arsip.php?id=<?= $data_next['id_arsip'] ?>" class="btn btn-outline-primary rounded-pill px-3">
                                    Arsip Selanjutnya <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary rounded-pill px-3" disabled>
                                    Arsip Selanjutnya <i class="fa-solid fa-arrow-right ms-1"></i>
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- LIGHTBOX MODAL ZOOM MURNI -->
    <div id="customLightbox" class="custom-lightbox" onclick="tutupZoom()">
        <span class="close-lightbox" onclick="tutupZoom()">&times;</span>
        <img id="imgZoomTarget" class="custom-lightbox-img" src="" onclick="event.stopPropagation();">
    </div>

    <!-- FOOTER -->
    <footer class="bg-dark text-white pt-4 pb-3 mt-5">
        <div class="container text-center text-secondary small">
            <p class="mb-0">&copy; 2026 Sistem Informasi Galeri Arsip Tapak Tilas Digital - Kabupaten Tangerang.</p>
        </div>
    </footer>

    <!-- SCRIPT INTERAKTIF JAVASCRIPT MURNI -->
    <script>
        // Array daftar nama file foto dari PHP
        const listFoto = <?= json_encode($daftar_gambar) ?>;
        let indexFotoSekarang = 0;

        // Fungsi memilih foto lewat thumbnail
        function pilihFoto(index) {
            if (index < 0 || index >= listFoto.length) return;
            indexFotoSekarang = index;

            const fotoUtama = document.getElementById('fotoUtama');
            if (fotoUtama) {
                fotoUtama.src = 'uploads/' + listFoto[indexFotoSekarang];
            }

            // Update penanda border biru di thumbnail
            const allThumbs = document.querySelectorAll('.thumbnail-box');
            allThumbs.forEach((thumb, idx) => {
                if (idx === index) {
                    thumb.classList.add('active-thumb');
                } else {
                    thumb.classList.remove('active-thumb');
                }
            });
        }

        // Fungsi geser foto lewat panah
        function geserFoto(arah, e) {
            if (e) e.stopPropagation();
            
            let nextIndex = indexFotoSekarang + arah;
            if (nextIndex < 0) {
                nextIndex = listFoto.length - 1;
            } else if (nextIndex >= listFoto.length) {
                nextIndex = 0;
            }
            pilihFoto(nextIndex);
        }

        // Fungsi memperbesar foto (Pop-Up Zoom)
        function perbesarFoto() {
            if (listFoto.length === 0) return;
            const lightbox = document.getElementById('customLightbox');
            const imgZoomTarget = document.getElementById('imgZoomTarget');
            
            imgZoomTarget.src = 'uploads/' + listFoto[indexFotoSekarang];
            lightbox.style.display = 'flex';
        }

        // Fungsi menutup foto zoom
        function tutupZoom() {
            const lightbox = document.getElementById('customLightbox');
            lightbox.style.display = 'none';
        }
    </script>
</body>
</html>