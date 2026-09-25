<?php
// file: ArsipManager.php
class ArsipManager {
    
    // Fungsi Admin untuk mengajukan arsip baru
    public function ajukanArsip($judul, $deskripsi, $images, $id_kategori, $id_admin) {
        $database = new Database();
        $db = $database->getConnection();
        
        $db->begin_transaction();
        try {
            // Menyimpan tanggal_pengajuan menggunakan waktu saat ini (NOW())
            $query = "INSERT INTO arsip (judul, deskripsi, id_kategori, id_admin, status, tanggal_pengajuan) VALUES (?, ?, ?, ?, 'Pending', NOW())";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ssii", $judul, $deskripsi, $id_kategori, $id_admin);
            $stmt->execute();
            
            $id_arsip = $db->insert_id;
            
            // Masukkan kumpulan gambar ke tabel relasi
            $query_img = "INSERT INTO arsip_gambar (id_arsip, nama_file) VALUES (?, ?)";
            $stmt_img = $db->prepare($query_img);
            
            foreach ($images as $img) {
                $stmt_img->bind_param("is", $id_arsip, $img);
                $stmt_img->execute();
            }
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollback();
            return false;
        }
    }
    
    // Fungsi Kepala Arsiparis untuk menyetujui pengajuan arsip
    public function setujuiArsip($id_arsip) {
        $database = new Database();
        $db = $database->getConnection();
        
        // Memperbarui status menjadi Disetujui dan mengisi tanggal_verifikasi (NOW())
        $query = "UPDATE arsip SET status = 'Disetujui', tanggal_verifikasi = NOW() WHERE id_arsip = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id_arsip);
        
        if ($stmt->execute()) {
            // Daftarkan ke tabel clustering secara otomatis dengan view default 0 jika belum ada
            $check = $db->query("SELECT id_arsip FROM clustering WHERE id_arsip = $id_arsip");
            if ($check->num_rows === 0) {
                $db->query("INSERT INTO clustering (id_arsip, jumlah_view, cluster_result) VALUES ($id_arsip, 0, 'Kurang Diminati')");
            }
            return true;
        }
        return false;
    }
    
    // Fungsi Kepala Arsiparis untuk menolak pengajuan arsip
    public function tolakArsip($id_arsip, $id_arsiparis, $catatan) {
        $database = new Database();
        $db = $database->getConnection();
        
        $db->begin_transaction();
        try {
            // Memperbarui status menjadi Ditolak dan mengisi tanggal_verifikasi (NOW())
            $query = "UPDATE arsip SET status = 'Ditolak', tanggal_verifikasi = NOW() WHERE id_arsip = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $id_arsip);
            $stmt->execute();
            
            // Simpan riwayat alasan penolakan ke tabel catatan_revisi
            $query_revisi = "INSERT INTO catatan_revisi (id_arsip, id_arsiparis, catatan) VALUES (?, ?, ?)";
            $stmt_revisi = $db->prepare($query_revisi);
            $stmt_revisi->bind_param("iis", $id_arsip, $id_arsiparis, $catatan);
            $stmt_revisi->execute();
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollback();
            return false;
        }
    }
}
?>