<?php
// file: logout.php
session_start();

// Kosongkan semua variabel array session
session_unset();

// Hancurkan session yang tersimpan di server lokal XAMPP
session_destroy();

// Pindahkan pengguna secara bersih kembali ke halaman depan publik
header("Location: index.php");
exit();
?>