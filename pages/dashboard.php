<?php
$page_title = "Dashboard";
$auto_refresh = 0;
include '../includes/header.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ambil data statistik
$total_barang = $conn->query("SELECT COUNT(*) as count FROM barang")->fetch_assoc()['count'];
$total_supplier = $conn->query("SELECT COUNT(*) as count FROM supplier")->fetch_assoc()['count'];
$total_stok = $conn->query("SELECT SUM(stok_akhir) as total FROM barang")->fetch_assoc()['total'] ?? 0;
$low_stock_count = $conn->query("SELECT COUNT(*) as count FROM barang WHERE stok_akhir <= stok_minimum")->fetch_assoc()['count'];

// Barang yang hampir kadaluarsa (dalam 30 hari)
$barang_expired = $conn->query("
    SELECT * FROM barang 
    WHERE tanggal_kadaluarsa IS NOT NULL 
    AND tanggal_kadaluarsa <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND tanggal_kadaluarsa > CURDATE()
    ORDER BY tanggal_kadaluarsa ASC
    LIMIT 10
");

// Barang dengan stok minimum
$barang_minimum = $conn->query("
    SELECT b.nama_barang, b.stok_akhir, b.stok_minimum, s.nama_supplier
    FROM barang b
    LEFT JOIN supplier s ON b.supplier_id = s.id
    WHERE b.stok_akhir <= b.stok_minimum
    ORDER BY b.stok_akhir ASC
    LIMIT 10
");

// Data barang masuk dan keluar hari ini
$transaksi_hari_ini = $conn->query("
    SELECT 
        t.tipe_transaksi,
        COUNT(*) as jumlah,
        SUM(t.jumlah) as total_qty
    FROM transaksi t
    WHERE DATE(t.tanggal) = CURDATE()
    GROUP BY t.tipe_transaksi
");

$transaksi_data = [];
while ($row = $transaksi_hari_ini->fetch_assoc()) {
    $transaksi_data[$row['tipe_transaksi']] = $row;
}

// Top 10 barang dengan stok terbesar
$top_barang = $conn->query("
    SELECT b.id, b.nama_barang, b.stok_akhir, s.nama_supplier 
    FROM barang b
    LEFT JOIN supplier s ON b.supplier_id = s.id
    ORDER BY b.stok_akhir DESC
    LIMIT 10
");
?>

<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="card text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Barang</h6>
                        <h2 class="mb-0"><?php echo $total_barang; ?></h2>
                    </div>
                    <i class="fas fa-boxes" style="font-size: 40px; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="card text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Supplier</h6>
                        <h2 class="mb-0"><?php echo $total_supplier; ?></h2>
                    </div>
                    <i class="fas fa-truck" style="font-size: 40px; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="card text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Stok</h6>
                        <h2 class="mb-0"><?php echo number_format($total_stok); ?></h2>
                    </div>
                    <i class="fas fa-chart-line" style="font-size: 40px; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="card text-white" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Stok Minimum</h6>
                        <h2 class="mb-0"><?php echo $low_stock_count; ?></h2>
                    </div>
                    <i class="fas fa-exclamation-triangle" style="font-size: 40px; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transaksi Hari Ini</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center p-3" style="background: #f0f8ff; border-radius: 5px;">
                            <i class="fas fa-arrow-down" style="color: #28a745; font-size: 30px;"></i>
                            <h4 class="mt-2 mb-0">
                                <?php echo isset($transaksi_data['masuk']) ? $transaksi_data['masuk']['total_qty'] : 0; ?>
                            </h4>
                            <small class="text-muted">Barang Masuk</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3" style="background: #fff5f5; border-radius: 5px;">
                            <i class="fas fa-arrow-up" style="color: #dc3545; font-size: 30px;"></i>
                            <h4 class="mt-2 mb-0">
                                <?php echo isset($transaksi_data['keluar']) ? $transaksi_data['keluar']['total_qty'] : 0; ?>
                            </h4>
                            <small class="text-muted">Barang Keluar</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Barang Hampir Kadaluarsa (30 hari)</h5>
            </div>
            <div class="card-body">
                <?php if ($barang_expired->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th>Expire Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $barang_expired->fetch_assoc()): 
                                    $days_left = (strtotime($row['tanggal_kadaluarsa']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                                    $badge_color = $days_left <= 7 ? 'danger' : 'warning';
                                ?>
                                    <tr>
                                        <td><?php echo substr($row['nama_barang'], 0, 20); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_kadaluarsa'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $badge_color; ?>">
                                                <?php echo (int)$days_left; ?> hari
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Tidak ada barang yang hampir kadaluarsa</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Peringatan Stok Minimum</h5>
            </div>
            <div class="card-body">
                <?php if ($barang_minimum->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th>Supplier</th>
                                    <th>Stok</th>
                                    <th>Minimum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $barang_minimum->fetch_assoc()): ?>
                                    <tr class="table-danger">
                                        <td><?php echo $row['nama_barang']; ?></td>
                                        <td><?php echo $row['nama_supplier']; ?></td>
                                        <td><strong><?php echo $row['stok_akhir']; ?></strong></td>
                                        <td><?php echo $row['stok_minimum']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Tidak ada barang dengan stok minimum</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top 10 Barang Dengan Stok Terbesar</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Barang</th>
                                <th>Supplier</th>
                                <th>Stok</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = $top_barang->fetch_assoc()): 
                                $status_color = $row['stok_akhir'] > 50 ? 'success' : ($row['stok_akhir'] > 10 ? 'warning' : 'danger');
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['nama_barang']; ?></td>
                                    <td><?php echo $row['nama_supplier']; ?></td>
                                    <td><strong><?php echo $row['stok_akhir']; ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_color; ?>">
                                            <?php 
                                            if ($row['stok_akhir'] > 50) echo 'Stok Aman';
                                            elseif ($row['stok_akhir'] > 10) echo 'Stok Terbatas';
                                            else echo 'Stok Rendah';
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
