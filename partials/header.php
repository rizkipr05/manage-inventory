<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPath = $_SERVER['PHP_SELF'] ?? '';
$isDashboard = $currentPath === '/manage-medical/index.php' || substr($currentPath, -9) === '/index.php';
$isSupplier = strpos($currentPath, '/suppliers/') !== false;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Management Data Barang'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/manage-medical/assets/app.css" rel="stylesheet">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">Management Barang</div>
        <div class="user-block">
            <div class="name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
            <div class="role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'admin'); ?></div>
        </div>
        <div class="menu-title">Menu</div>
        <nav class="menu">
            <a class="<?php echo $isDashboard ? 'active' : ''; ?>" href="/manage-medical/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a class="<?php echo $isSupplier ? 'active' : ''; ?>" href="/manage-medical/suppliers/index.php"><i class="bi bi-truck"></i> Supplier</a>
        </nav>
    </aside>
    <div class="main">
        <div class="topbar">
            <div class="page-title"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></div>
            <a class="btn btn-outline-secondary btn-sm" href="/manage-medical/auth/logout.php">Logout</a>
        </div>
        <main class="content">
