<?php
// file: login.php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'koneksi.php';

// Jika sudah login, langsung lempar ke dashboard
if (isset($_SESSION['role'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if ($username !== '' && $password !== '') {
        // 1. Cek pertama kali ke tabel Admin
        $stmt_admin = $db->prepare("SELECT id_admin, username, password FROM admin WHERE username = ?");
        $stmt_admin->bind_param("s", $username);
        $stmt_admin->execute();
        $res_admin = $stmt_admin->get_result();

        if ($res_admin->num_rows === 1) {
            $user = $res_admin->fetch_assoc();
            if ($password === $user['password'] || password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id_admin'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'admin';
                
                header("Location: dashboard.php");
                exit();
            }
        }

        // 2. Jika bukan Admin, cek ke tabel Kepala Arsiparis
        $stmt_arsiparis = $db->prepare("SELECT id_arsiparis, username, password FROM kepala_arsiparis WHERE username = ?");
        $stmt_arsiparis->bind_param("s", $username);
        $stmt_arsiparis->execute();
        $res_arsiparis = $stmt_arsiparis->get_result();

        if ($res_arsiparis->num_rows === 1) {
            $user = $res_arsiparis->fetch_assoc();
            if ($password === $user['password'] || password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id_arsiparis'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'kepala_arsiparis';
                
                header("Location: dashboard.php");
                exit();
            }
        }

        $error = "Username atau password yang Anda masukkan salah!";
    } else {
        $error = "Harap isi semua kolom login!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Galeri Artikel Digital Kabupaten Tangerang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            background-color: #ffffff;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-primary">Sistem Galeri Artikel</h4>
        <small class="text-muted">Kabupaten Tangerang</small>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger text-center p-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="mb-3">
            <label for="username" class="form-label small fw-bold">Username</label>
            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label small fw-bold">Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Masuk</button>
    </form>
</div>

</body>
</html>