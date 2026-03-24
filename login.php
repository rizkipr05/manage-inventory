<?php
include 'includes/config.php';
include 'includes/session.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    // Query untuk cek admin
    $query = "SELECT * FROM admin WHERE username = '$username'";
    $result = $conn->query($query);
    
    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        
        // Verifikasi password menggunakan SHA2
        if (hash('sha256', $password) === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            header("Location: pages/dashboard.php");
            exit();
        } else {
            $error = "Username atau password salah!";
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
    <title>Login - Sistem Stok Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(1200px 600px at 10% -10%, #eef2ff 0, #f6f7fb 40%, #f6f7fb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Manrope", sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-container h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-family: "Playfair Display", serif;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #d1a954 0%, #e2c275 100%);
            color: #1f2937;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(209, 169, 84, 0.35);
        }
        .alert {
            margin-bottom: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo i {
            font-size: 50px;
            color: #d1a954;
        }
        .logo h4 {
            font-family: "Playfair Display", serif;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h2>📦</h2>
            <h4>Sistem Stok Barang</h4>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <h1>Login</h1>
        
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>
        
        <hr>
        <p style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
            Demo Credentials:<br>
            Username: <strong>admin</strong><br>
            Password: <strong>admin123</strong>
        </p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
