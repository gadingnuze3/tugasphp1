<?php
session_start();
require_once 'config.php';

// Jika sudah login, langsung lempar ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Pastikan akun default tersedia dengan password '123456'
$check_user = $conn->query("SELECT id FROM users LIMIT 1");
if ($check_user && $check_user->num_rows == 0) {
    $pass_default = password_hash('123456', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, full_name, role) VALUES 
        ('owner', '$pass_default', 'Juragan FrostMart', 'Owner'),
        ('kasir', '$pass_default', 'Admin Kasir 1', 'Kasir')");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username = '$username' LIMIT 1");
    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            // Simpan data user ke session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            header("Location: index.php");
            exit();
        } else {
            $error = "Password salah! Silakan coba lagi.";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KKS FROZEN FOOD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0B2545 0%, #134074 100%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .login-header {
            background-color: #F0F6FC;
            padding: 32px 30px 24px;
            text-align: center;
            border-bottom: 1px solid #D9E6F2;
        }
        .brand-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #00A8E8, #134074);
            color: #fff;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 12px;
            box-shadow: 0 6px 15px rgba(0, 168, 232, 0.3);
        }
        .btn-ice {
            background-color: #00A8E8;
            color: #fff;
            font-weight: 700;
            border: none;
            padding: 12px;
            border-radius: 10px;
        }
        .btn-ice:hover {
            background-color: #008ec4;
            color: #fff;
        }
        .form-control {
            padding: 11px 14px;
            border-radius: 10px;
            border: 1.5px solid #D9E6F2;
        }
        .form-control:focus {
            border-color: #00A8E8;
            box-shadow: 0 0 0 0.2rem rgba(0, 168, 232, 0.15);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="brand-icon">
            <i class="bi bi-snow2"></i>
        </div>
        <h4 class="fw-bold mb-1" style="color: #0B2545;">KKS Mart Inventory</h4>
        <p class="text-muted small mb-0">Masuk untuk mengelola stok & penjualan freezer</p>
    </div>

    <div class="p-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small border-0 mb-3">
                <i class="bi bi-exclamation-circle-fill me-1"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-info py-2 small border-0 mb-3">
                <i class="bi bi-check-circle-fill me-1"></i> Anda telah berhasil keluar (logout).
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label small fw-bold" style="color: #0B2545;">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold" style="color: #0B2545;">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" id="passInput" class="form-control border-start-0 border-end-0" placeholder="Masukkan password" required>
                    <button class="btn btn-light border border-start-0" type="button" onclick="togglePass()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-ice w-100 shadow-sm mb-3">
                Masuk ke Dashboard <i class="bi bi-box-arrow-in-right ms-1"></i>
            </button>
        </form>

        
    </div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('passInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
</body>
</html>