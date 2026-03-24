<?php
/**
 * FILE KONFIGURASI HELPER
 * Berisi fungsi-fungsi helper yang digunakan di seluruh aplikasi
 */

/**
 * Format angka ke format Rupiah
 */
function formatRupiah($value) {
    return 'Rp ' . number_format($value, 0, ',', '.');
}

/**
 * Format tanggal ke format Indonesia
 */
function formatTanggal($tanggal) {
    $bulan = array(
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    );
    
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

/**
 * Hitung sisa hari sampai tanggal
 */
function hitungHariTersisa($tanggal) {
    $now = new DateTime(date('Y-m-d'));
    $date = new DateTime($tanggal);
    $interval = $now->diff($date);
    return $interval->days;
}

/**
 * Cek apakah barang sudah expired
 */
function isExpired($tanggal_expired) {
    return strtotime($tanggal_expired) < strtotime(date('Y-m-d'));
}

/**
 * Get status stok
 */
function getStatusStok($stok) {
    if ($stok > 50) {
        return array('status' => 'Aman', 'color' => 'success');
    } elseif ($stok > 10) {
        return array('status' => 'Terbatas', 'color' => 'warning');
    } else {
        return array('status' => 'Rendah', 'color' => 'danger');
    }
}

/**
 * Redirect aman (fallback JS jika header sudah terkirim)
 */
function safeRedirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    }
    echo "<script>window.location.href=" . json_encode($url) . ";</script>";
    echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url=$url\"></noscript>";
    exit();
}

?>
