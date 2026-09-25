<?php
// Koneksi ke database MySQL
$koneksi = mysqli_connect("localhost", "root", "", "db_galeri_arsip");

// Cek koneksi
if (!$koneksi) {
    die("Koneksi ke basis data gagal: " . mysqli_connect_error());
}

// AMBIL KEYWORD DARI FORM FORM SUBMIT GET
$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($koneksi, trim($_GET['keyword'])) : '';

$where_clause = "WHERE a.status = 'Disetujui'";
if (!empty($keyword)) {
    $where_clause .= " AND (a.judul LIKE '%$keyword%' OR a.deskripsi LIKE '%$keyword%' OR k.nama_kategori LIKE '%$keyword%')";
}

// QUERY UTAMA UNTUK MENGAMBIL DATA ARSIP
$query_arsip = "SELECT a.*, k.nama_kategori, 
               (SELECT nama_file FROM arsip_gambar WHERE id_arsip = a.id_arsip LIMIT 1) as foto_sampul
                FROM arsip a
                LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
                $where_clause
                ORDER BY a.id_arsip DESC";

$result_arsip = mysqli_query($koneksi, $query_arsip);
$total_arsip = mysqli_num_rows($result_arsip);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri Artikel Digital - Kabupaten Tangerang</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 70px;
        }
        body {
            position: relative;
        }
        
        /* INDIKATOR MENU NAVIGASI AKTIF */
        .navbar-dark .navbar-nav .nav-link.active {
            color: #ffffff !important;
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }
        .navbar-dark .navbar-nav .nav-link {
            transition: all 0.2s ease-in-out;
            padding-bottom: 8px;
            border-bottom: 3px solid transparent;
        }

        .hero-section {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .card-arsip {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
        }
        .card-arsip:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        .img-cover {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        /* STYLING KOTAK PAMERAN VIRTUAL ARTSTEPS */
        .virtual-box {
            background: linear-gradient(135deg, #ffffff 0%, #f0f4f9 100%);
            border: 2px dashed #0d6efd;
            transition: all 0.3s ease;
        }
        .virtual-box:hover {
            border-style: solid;
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(13, 110, 253, 0.15) !important;
        }
        .virtual-title {
            color: #1a252f;
        }
        .virtual-text {
            color: #5a6578;
        }
        .virtual-button {
            background-color: #0d6efd;
            color: #ffffff !important;
            border-radius: 50px;
            font-weight: 600;
            display: inline-block;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .virtual-button:hover {
            background-color: #0b5ed7;
            transform: scale(1.05);
            color: #ffffff !important;
        }

        /* MEDIA SOSIAL */
        .btn-social {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #ffffff !important;
            text-decoration: none;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .btn-social:hover {
            transform: translateY(-3px);
            opacity: 0.85;
        }
        .btn-instagram { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%,#d6249f 60%,#285AEB 90%); }
        .btn-youtube { background-color: #FF0000; }
    </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#navbarUtama" data-bs-offset="100" tabindex="0">

    <!-- 1. BARIS NAVIGASI UTAMA (NAVBAR) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm" id="navbarUtama">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#beranda">
                <i class="fa-solid fa-landmark me-2"></i>Galeri Artikel Tapak Tilas
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#beranda">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#koleksi-arsip">Koleksi Artikel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#visi-misi">Visi & Misi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#struktur-organisasi">Struktur Organisasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-lg-2" href="#kontak">Kontak & Lokasi</a>
                    </li>
                </ul>

                <!-- SEARCH BAR BIASA (TANPA AJAX) -->
                <form class="d-flex mt-2 mt-lg-0" action="#koleksi-arsip" method="GET">
                    <div class="input-group">
                        <input type="text" name="keyword" class="form-control form-control-sm border-0" placeholder="Cari Artikel..." value="<?= htmlspecialchars($keyword) ?>" aria-label="Cari Artikel">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </nav>

    <!-- 2. SPANDUK PROFIL INSTANSI & GEDUNG (BERANDA) -->
    <section class="py-5 hero-section" id="beranda">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-4 mb-lg-0 text-center">
                    <img src="assets/img/gedung_dinas.jpg" alt="Gedung Dinas Perpustakaan dan Arsip Kabupaten Tangerang" class="img-fluid rounded shadow-sm border" onerror="this.src='https://via.placeholder.com/500x350?text=Gedung+Dinas+Perpustakaan+dan+Arsip'">
                </div>
                <div class="col-lg-7">
                    <span class="badge bg-primary mb-2">Profil Instansi</span>
                    <h2 class="fw-bold mb-3">Dinas Perpustakaan dan Arsip Kabupaten Tangerang</h2>
                    <p class="text-secondary" style="text-align: justify;">
                        Dinas Perpustakaan dan Arsip Kabupaten Tangerang merupakan perangkat daerah yang dibentuk berdasarkan Peraturan Daerah Kabupaten Tangerang Nomor 9 Tahun 2016 tentang Pembentukan dan Susunan Perangkat Daerah. Instansi ini bertugas menyelenggarakan urusan pemerintahan di bidang kearsipan dan perpustakaan guna mewujudkan pelestarian dokumen bersejarah daerah serta meningkatkan literasi masyarakat.
                    </p>
                    <p class="text-secondary" style="text-align: justify;">
                        Melalui platform Sistem Informasi Galeri Artikel Tapak Tilas Digital ini, dokumen dan rekam sejarah Kabupaten Tangerang disajikan secara terbuka untuk mempermudah akses informasi publik sekaligus mendukung penguatan identitas dan citra daerah secara digital.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. KOLEKSI ARSIP TERPUBLIKASI (GRID DOKUMEN) -->
    <section class="py-5 bg-light" id="koleksi-arsip">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0">Koleksi Artikel Terpublikasi</h3>
                    <p class="text-muted small mb-0">
                        <?php if(!empty($keyword)): ?>
                            Hasil pencarian untuk kata kunci: <strong>"<?= htmlspecialchars($keyword) ?>"</strong> (<a href="index.php#koleksi-arsip">Reset Filter</a>)
                        <?php else: ?>
                            Dokumentasi sejarah yang telah diverifikasi dan siap diakses publik.
                        <?php endif; ?>
                    </p>
                </div>
                <span class="badge bg-secondary fs-6">
                    Ditemukan: <?= $total_arsip ?> artikel
                </span>
            </div>

            <div class="row g-4">
                <?php if ($total_arsip > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_arsip)): ?>
                        <div class="col-md-6 col-lg-4 card-item-arsip">
                            <div class="card card-arsip border-0 shadow-sm rounded overflow-hidden">
                                <?php if (!empty($row['foto_sampul'])): ?>
                                    <img src="uploads/<?= $row['foto_sampul'] ?>" class="card-img-top img-cover" alt="<?= htmlspecialchars($row['judul']) ?>">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/400x200?text=Tidak+Ada+Gambar" class="card-img-top img-cover" alt="Tidak ada gambar">
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                        <span class="badge bg-info text-dark">
                                            <?= htmlspecialchars($row['nama_kategori'] ?? 'Umum') ?>
                                        </span>
                                    </div>

                                    <h5 class="card-title fw-bold text-dark text-truncate">
                                        <?= htmlspecialchars($row['judul']) ?>
                                    </h5>

                                    <p class="card-text text-muted small flex-grow-1">
                                        <?= htmlspecialchars(substr($row['deskripsi'], 0, 100)) ?>...
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                        <small class="text-muted">
                                            <i class="fa-regular fa-eye me-1"></i> <?= $row['jumlah_view'] ?> Dilihat
                                        </small>
                                        <a href="detail_arsip.php?id=<?= $row['id_arsip'] ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                                            Selengkapnya
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fa-solid fa-folder-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada data arsip yang ditemukan.</h5>
                    </div>
                <?php endif; ?>
            </div>

            <!-- LINK KE PAMERAN VIRTUAL ARTSTEPS -->
            <div class="text-center mt-5 p-4 rounded shadow-sm virtual-box">
                <h4 class="mb-2 fw-semibold virtual-title">🎨 Jelajahi Pameran Virtual 3D</h4>
                <p class="virtual-text mb-3">Masuki ruang pameran interaktif dan temukan arsip dalam dunia virtual!</p>
                <a href="https://www.artsteps.com/view/684fe6a8b90357f7169cb2d7" target="_blank" class="virtual-button px-4 py-2 shadow-sm">
                    <i class="fa-solid fa-vr-cardboard me-2"></i> Lihat Pameran Virtual
                </a>
            </div>

        </div>
    </section>

    <!-- 4. SECTION VISI & MISI -->
    <section class="py-5 bg-white border-top" id="visi-misi">
        <div class="container">
            <div class="text-center mb-4">
                <span class="badge bg-primary mb-2">Pedoman Kerja</span>
                <h3 class="fw-bold">Visi & Misi Instansi</h3>
                <p class="text-muted small">Komitmen Dinas Perpustakaan dan Arsip Kabupaten Tangerang dalam melayani masyarakat.</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-5">
                    <div class="p-4 rounded shadow-sm border bg-light h-100">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fa-solid fa-bullseye fa-2x text-primary me-3"></i>
                            <h4 class="fw-bold mb-0">Visi</h4>
                        </div>
                        <p class="text-secondary mb-0" style="text-align: justify;">
                            "Terwujudnya tata kelola kearsipan yang akuntabel, efisien, dan modern serta terwujudnya masyarakat Kabupaten Tangerang yang cerdas dan gemar membaca berbasis teknologi informasi."
                        </p>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="p-4 rounded shadow-sm border bg-light h-100">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fa-solid fa-list-check fa-2x text-primary me-3"></i>
                            <h4 class="fw-bold mb-0">Misi</h4>
                        </div>
                        <ul class="text-secondary ps-3 mb-0" style="text-align: justify;">
                            <li class="mb-2">Meningkatkan kualitas penyelamatan, pelestarian, dan pengelolaan dokumen arsip sejarah daerah.</li>
                            <li class="mb-2">Mengembangkan sistem kearsipan berbasis digital guna mempermudah akses informasi publik secara transparan.</li>
                            <li class="mb-2">Meningkatkan mutu pelayanan perpustakaan dan budaya literasi di lingkungan masyarakat Kabupaten Tangerang.</li>
                            <li class="mb-0">Meningkatkan kompetensi serta profesionalisme sumber daya manusia di bidang kearsipan dan perpustakaan.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. SECTION STRUKTUR ORGANISASI -->
    <section class="py-5 bg-light border-top" id="struktur-organisasi">
        <div class="container">
            <div class="text-center mb-4">
                <span class="badge bg-primary mb-2">Tata Kelola</span>
                <h3 class="fw-bold">Struktur Organisasi</h3>
                <p class="text-muted small">Bagan susunan organisasi Dinas Perpustakaan dan Arsip Kabupaten Tangerang.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <div class="p-3 bg-white rounded shadow-sm border">
                        <img src="assets/img/struktur_organisasi.jpg" alt="Struktur Organisasi Dinas Perpustakaan dan Arsip Kabupaten Tangerang" class="img-fluid rounded" onerror="this.src='https://via.placeholder.com/900x500?text=Bagan+Struktur+Organisasi+Dinas'">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. FOOTER (KONTAK KAMI & MAPS LOKASI DINAS) -->
    <footer class="bg-dark text-white pt-5 pb-4" id="kontak">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6">
                    <h5 class="fw-bold mb-3 text-warning">
                        <i class="fa-solid fa-building-columns me-2"></i>Dinas Perpustakaan dan Arsip
                    </h5>
                    <p class="text-light small mb-3">
                        Pusat pengelolaan dokumentasi sejarah dan layanan informasi publik kearsipan daerah Kabupaten Tangerang.
                    </p>
                    
                    <ul class="list-unstyled text-secondary small">
                        <li class="mb-2 text-white">
                            <i class="fa-solid fa-location-dot me-2 text-warning"></i>
                            <strong>Alamat:</strong> Jl. H. Abdul Hamid No.9, Kadu Agung, Kec. Tigaraksa, Kabupaten Tangerang, Banten 15720
                        </li>
                        <li class="mb-2 text-white">
                            <i class="fa-solid fa-phone me-2 text-warning"></i>
                            <strong>Telepon:</strong> (021) 5990234
                        </li>
                        <li class="mb-2 text-white">
                            <i class="fa-solid fa-envelope me-2 text-warning"></i>
                            <strong>Email:</strong> dpad@tangerangkab.go.id
                        </li>
                        <li class="mb-2 text-white">
                            <i class="fa-solid fa-clock me-2 text-warning"></i>
                            <strong>Jam Operasional:</strong> Senin – Jumat (08.00 – 16.00 WIB)
                        </li>
                        <li class="mt-3 text-white">
                            <i class="fa-solid fa-share-nodes me-2 text-warning"></i>
                            <strong>Media Sosial:</strong>
                            <div class="mt-2 d-flex gap-2">
                                <a href="https://www.instagram.com/officialperpusiptngkab" target="_blank" class="btn-social btn-instagram" title="Instagram">
                                    <i class="fa-brands fa-instagram"></i>
                                </a>
                                <a href="https://youtube.com/@perpusipkab.tangerang" target="_blank" class="btn-social btn-youtube" title="YouTube">
                                    <i class="fa-brands fa-youtube"></i>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="col-lg-6">
                    <h5 class="fw-bold mb-3 text-warning">
                        <i class="fa-solid fa-map-location-dot me-2"></i>Lokasi Kantor
                    </h5>
                    <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.929963056281!2d106.47988697499078!3d-6.272940093715804!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e4206543cced7a5%3A0xd5887cd51019fe3c!2sGedung%20Pepustakaan%20Daerah%20Kabupaten%20Tangerang!5e0!3m2!1sen!2sid!4v1784804941864!5m2!1sen!2sid" 
                            style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>

            <hr class="mt-4 border-secondary">
            
            <div class="text-center text-secondary small">
                <p class="mb-0">&copy; 2026 Sistem Informasi Galeri Arsip Tapak Tilas Digital - Kabupaten Tangerang.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Inisialisasi ScrollSpy Bootstrap
            var scrollSpy = new bootstrap.ScrollSpy(document.body, {
                target: '#navbarUtama',
                offset: 100
            });
        });
    </script>

</body>
</html>