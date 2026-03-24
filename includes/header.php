<?php
if (ob_get_level() === 0) {
    ob_start();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/manage-medical/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/manage-medical/includes/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/manage-medical/includes/helpers.php';

redirectIfNotLoggedIn();
$auto_refresh = isset($auto_refresh) ? (int)$auto_refresh : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistem Stok Barang'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f6f7fb;
            --ink: #0f172a;
            --muted: #6b7280;
            --sidebar: #0b1220;
            --sidebar-2: #111827;
            --accent: #d1a954;
            --accent-2: #4f46e5;
            --card: #ffffff;
            --border: #e5e7eb;
        }
        body {
            background: radial-gradient(1200px 600px at 10% -10%, #eef2ff 0, #f6f7fb 40%, #f6f7fb 100%);
            color: var(--ink);
            font-family: "Manrope", sans-serif;
        }
        .sidebar {
            background: linear-gradient(180deg, var(--sidebar) 0%, var(--sidebar-2) 100%);
            min-height: 100vh;
            padding: 24px 0;
            position: fixed;
            width: 260px;
            left: 0;
            top: 0;
            box-shadow: 8px 0 24px rgba(15, 23, 42, 0.18);
        }
        .sidebar-logo {
            color: #f8fafc;
            text-align: center;
            padding: 18px 20px 22px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
            margin-bottom: 16px;
        }
        .sidebar-logo h4 {
            margin: 0;
            font-weight: 700;
            font-family: "Playfair Display", serif;
            letter-spacing: 0.5px;
        }
        .sidebar-logo i {
            font-size: 26px;
            margin-bottom: 8px;
            color: var(--accent);
        }
        .sidebar-logo small {
            color: rgba(226, 232, 240, 0.7);
        }
        .sidebar-menu a {
            display: block;
            color: #cbd5e1;
            padding: 12px 22px;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
            font-weight: 600;
            font-size: 14px;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(148, 163, 184, 0.12);
            border-left-color: var(--accent);
            color: #ffffff;
        }
        .sidebar-menu i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            margin-left: 260px;
            padding: 28px;
        }
        .navbar-top {
            background: var(--card);
            border: 1px solid var(--border);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
            margin-bottom: 24px;
            border-radius: 14px;
        }
        .navbar-top .navbar-brand {
            color: var(--ink) !important;
            font-weight: 700;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            margin-bottom: 22px;
        }
        .card-header {
            background: #fbfbfd;
            color: var(--ink);
            border-radius: 14px 14px 0 0 !important;
            border: none;
            border-bottom: 1px solid var(--border);
            font-weight: 700;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, #e2c275 100%);
            border: none;
            color: #1f2937;
            font-weight: 700;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #e2c275 0%, var(--accent) 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(209, 169, 84, 0.35);
        }
        .table thead {
            background-color: #f3f4f6;
        }
        .table tbody tr:hover {
            background-color: #f8fafc;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 5px;
        }
        .sidebar-divider {
            border-color: rgba(148, 163, 184, 0.2);
            margin: 18px 0;
        }
        .top-title {
            font-family: "Playfair Display", serif;
            letter-spacing: 0.3px;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body data-refresh="<?php echo $auto_refresh; ?>">
    <div class="sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-box-open"></i>
            <h4>Stok Barang</h4>
            <small>Inventory Management</small>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>Dashboard
            </a>
            <a href="barang.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'barang.php' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i>Data Barang
            </a>
            <a href="supplier.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'supplier.php' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i>Supplier
            </a>
            <a href="transaksi.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i>Transaksi
            </a>
            <a href="laporan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>Laporan
            </a>
            <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i>Users
            </a>
            <hr class="sidebar-divider">
            <a href="/manage-medical/logout.php" onclick="return confirm('Yakin ingin logout?')">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="navbar-top">
            <div class="d-flex justify-content-between align-items-center p-3">
                <h5 class="mb-0 top-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h5>
                <div>
                    <span class="me-3">Halo, <strong><?php echo $_SESSION['username']; ?></strong></span>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>" 
                         alt="Avatar" style="width: 35px; height: 35px; border-radius: 50%;">
                </div>
            </div>
        </div>
