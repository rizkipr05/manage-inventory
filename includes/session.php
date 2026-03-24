<?php
// Mulai session
session_start();

// Fungsi untuk cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Fungsi untuk redirect jika tidak login
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Fungsi untuk logout
function logout() {
    session_destroy();
    header("Location: /manage-medical/login.php");
    exit();
}

// Cek apakah user admin
function isAdmin() {
    return isLoggedIn();
}

?>
