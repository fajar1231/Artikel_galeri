<?php
// file: koneksi.php
// [source: 1]

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $db_name = "db_galeri_arsip";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            if ($this->conn->connect_error) {
                die("Koneksi database gagal: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        return $this->conn;
    }
}
?>