<?php
// file: Clustering.php
require_once 'koneksi.php';

class Clustering {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function prosesKMeans() {
        // 1. Ambil data arsip yang statusnya sudah 'Disetujui'
        $query = "SELECT id_arsip, jumlah_view FROM arsip WHERE status = 'Disetujui'";
        $result = $this->db->query($query);
        $dataset = [];
        
        while ($row = $result->fetch_assoc()) {
            $dataset[] = $row;
        }

        // Minimal data harus berjumlah >= K (3 Cluster)
        if (count($dataset) < 3) {
            return false; 
        }

        // 2. Tentukan Centroid Awal (C1, C2, C3) secara dinamis dari min, median, dan max view
        $views = array_column($dataset, 'jumlah_view');
        sort($views);
        
        $c1 = $views[0]; // Centroid Rendah (Min)
        $c2 = $views[intdiv(count($views), 2)]; // Centroid Sedang (Median)
        $c3 = $views[count($views) - 1]; // Centroid Tinggi (Max)

        $konvergen = false;
        $max_iterasi = 100;
        $iterasi = 0;

        // DEKLARASI AWAL VARIABEL $clusters AGAR TERBACA OLEH INTELEPHENSE
        $clusters = [0 => [], 1 => [], 2 => []];

        while (!$konvergen && $iterasi < $max_iterasi) {
            $clusters = [0 => [], 1 => [], 2 => []];
            
            // Hitung Jarak Selisih Nilai Absolut: d = |x - c|
            foreach ($dataset as $data) {
                $d1 = abs($data['jumlah_view'] - $c1);
                $d2 = abs($data['jumlah_view'] - $c2);
                $d3 = abs($data['jumlah_view'] - $c3);

                // Cari jarak terdekat (Minimum)
                $min_jarak = min($d1, $d2, $d3);
                if ($min_jarak == $d1) {
                    $clusters[0][] = $data;
                } elseif ($min_jarak == $d2) {
                    $clusters[1][] = $data;
                } else {
                    $clusters[2][] = $data;
                }
            }

            // Hitung ulang Centroid baru (mencari nilai rata-rata kelompok)
            $new_c1 = count($clusters[0]) > 0 ? array_sum(array_column($clusters[0], 'jumlah_view')) / count($clusters[0]) : $c1;
            $new_c2 = count($clusters[1]) > 0 ? array_sum(array_column($clusters[1], 'jumlah_view')) / count($clusters[1]) : $c2;
            $new_c3 = count($clusters[2]) > 0 ? array_sum(array_column($clusters[2], 'jumlah_view')) / count($clusters[2]) : $c3;

            // Cek Konvergensi (apakah centroid tidak berubah lagi)
            if ($new_c1 == $c1 && $new_c2 == $c2 && $new_c3 == $c3) {
                $konvergen = true;
            } else {
                $c1 = $new_c1;
                $c2 = $new_c2;
                $c3 = $new_c3;
            }
            $iterasi++;
        }

        // 3. Bersihkan tabel clustering lama dan simpan hasil iterasi baru
        $this->db->query("TRUNCATE TABLE clustering");
        
        $labels = [0 => 'Kurang Diminati', 1 => 'Sedang', 2 => 'Populer'];
        
        $stmt = $this->db->prepare("INSERT INTO clustering (id_arsip, jumlah_view, cluster_result) VALUES (?, ?, ?)");

        foreach ($clusters as $key => $cluster_data) {
            $label = $labels[$key];
            foreach ($cluster_data as $row) {
                $id_arsip = $row['id_arsip'];
                $view = $row['jumlah_view'];
                $stmt->bind_param("iis", $id_arsip, $view, $label);
                $stmt->execute();
            }
        }
        $stmt->close();

        return true;
    }
}
?>