<?php
include '../includes/config.php';
include '../includes/session.php';
redirectIfNotLoggedIn();

$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

$start_esc = $conn->real_escape_string($start);
$end_esc = $conn->real_escape_string($end);
$type_esc = $conn->real_escape_string($type);

$filter_type_sql = $type_esc !== 'all' ? " AND t.tipe_transaksi = '$type_esc'" : "";

// Export CSV
if (isset($_GET['export']) && isset($_GET['report'])) {
    $report = $_GET['report'];

    if ($report === 'transaksi') {
        $query = "SELECT t.tanggal, t.tipe_transaksi, b.kode_barang, b.nama_barang, s.nama_supplier, t.jumlah, t.keterangan
                  FROM transaksi t
                  LEFT JOIN barang b ON t.barang_id = b.id
                  LEFT JOIN supplier s ON b.supplier_id = s.id
                  WHERE DATE(t.tanggal) BETWEEN '$start_esc' AND '$end_esc' $filter_type_sql
                  ORDER BY t.tanggal DESC";
        $result = $conn->query($query);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="laporan_transaksi.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Tanggal', 'Tipe', 'Kode Barang', 'Nama Barang', 'Supplier', 'Jumlah', 'Keterangan']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [$row['tanggal'], $row['tipe_transaksi'], $row['kode_barang'], $row['nama_barang'], $row['nama_supplier'], $row['jumlah'], $row['keterangan']]);
        }
        fclose($output);
        exit();
    }

    if ($report === 'stok') {
        $query = "SELECT b.kode_barang, b.nama_barang, s.nama_supplier, b.stok_akhir, b.stok_minimum, b.satuan
                  FROM barang b
                  LEFT JOIN supplier s ON b.supplier_id = s.id
                  ORDER BY b.nama_barang";
        $result = $conn->query($query);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="laporan_stok.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Kode Barang', 'Nama Barang', 'Supplier', 'Stok', 'Stok Minimum', 'Satuan']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [$row['kode_barang'], $row['nama_barang'], $row['nama_supplier'], $row['stok_akhir'], $row['stok_minimum'], $row['satuan']]);
        }
        fclose($output);
        exit();
    }

    if ($report === 'penggunaan') {
        $query = "SELECT b.kode_barang, b.nama_barang, s.nama_supplier, SUM(t.jumlah) AS total_keluar
                  FROM transaksi t
                  LEFT JOIN barang b ON t.barang_id = b.id
                  LEFT JOIN supplier s ON b.supplier_id = s.id
                  WHERE t.tipe_transaksi = 'keluar' AND DATE(t.tanggal) BETWEEN '$start_esc' AND '$end_esc'
                  GROUP BY b.id
                  ORDER BY total_keluar DESC";
        $result = $conn->query($query);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="laporan_penggunaan.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Kode Barang', 'Nama Barang', 'Supplier', 'Total Keluar']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [$row['kode_barang'], $row['nama_barang'], $row['nama_supplier'], $row['total_keluar']]);
        }
        fclose($output);
        exit();
    }
}

// Data laporan
$transaksi = $conn->query("SELECT t.*, b.nama_barang, b.kode_barang, s.nama_supplier
                           FROM transaksi t
                           LEFT JOIN barang b ON t.barang_id = b.id
                           LEFT JOIN supplier s ON b.supplier_id = s.id
                           WHERE DATE(t.tanggal) BETWEEN '$start_esc' AND '$end_esc' $filter_type_sql
                           ORDER BY t.tanggal DESC");

$stok = $conn->query("SELECT b.*, s.nama_supplier FROM barang b LEFT JOIN supplier s ON b.supplier_id = s.id ORDER BY b.nama_barang");

$penggunaan = $conn->query("SELECT b.nama_barang, b.kode_barang, s.nama_supplier, SUM(t.jumlah) AS total_keluar
                            FROM transaksi t
                            LEFT JOIN barang b ON t.barang_id = b.id
                            LEFT JOIN supplier s ON b.supplier_id = s.id
                            WHERE t.tipe_transaksi = 'keluar' AND DATE(t.tanggal) BETWEEN '$start_esc' AND '$end_esc'
                            GROUP BY b.id
                            ORDER BY total_keluar DESC");

$total_masuk = $conn->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE tipe_transaksi = 'masuk' AND DATE(tanggal) BETWEEN '$start_esc' AND '$end_esc'")->fetch_assoc()['total'] ?? 0;
$total_keluar = $conn->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE tipe_transaksi = 'keluar' AND DATE(tanggal) BETWEEN '$start_esc' AND '$end_esc'")->fetch_assoc()['total'] ?? 0;

$page_title = "Laporan";
include '../includes/header.php';
?>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: #fff !important;
    }
    .sidebar,
    .navbar-top {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .card {
        box-shadow: none !important;
        border: none !important;
    }
    .card-header {
        background: #fff !important;
        color: #000 !important;
        border-bottom: 1px solid #000 !important;
    }
    .print-header {
        display: block !important;
    }
    .print-header .title {
        font-size: 16pt;
        font-weight: 700;
        text-align: center;
    }
    .print-header .subtitle {
        font-size: 10pt;
        text-align: center;
        margin-top: 4px;
    }
    .print-header .period {
        font-size: 10pt;
        text-align: center;
        margin-top: 6px;
    }
    .print-header hr {
        border: none;
        border-top: 2px solid #000;
        margin: 12px 0 18px;
    }
}
.print-header {
    display: none;
}
</style>

<div class="print-header">
    <div class="title">CV. Stok Barang Mandiri</div>
    <div class="subtitle">Laporan Stok & Transaksi</div>
    <div class="period">Periode: <?php echo date('d/m/Y', strtotime($start)); ?> - <?php echo date('d/m/Y', strtotime($end)); ?></div>
    <hr>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Filter Laporan</h5>
        <div class="no-print">
            <button class="btn btn-sm btn-secondary" onclick="window.print()">Print</button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="start" class="form-control" value="<?php echo $start; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="end" class="form-control" value="<?php echo $end; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipe Transaksi</label>
                <select name="type" class="form-control">
                    <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>Semua</option>
                    <option value="masuk" <?php echo $type === 'masuk' ? 'selected' : ''; ?>>Masuk</option>
                    <option value="keluar" <?php echo $type === 'keluar' ? 'selected' : ''; ?>>Keluar</option>
                </select>
            </div>
            <div class="col-md-3 no-print">
                <button type="submit" class="btn btn-primary w-100">Terapkan</button>
            </div>
        </form>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ringkasan Transaksi</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted">Total Masuk</div>
                        <h4><?php echo number_format($total_masuk); ?></h4>
                    </div>
                    <div>
                        <div class="text-muted">Total Keluar</div>
                        <h4><?php echo number_format($total_keluar); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Laporan Bahan Masuk/Keluar</h5>
        <div class="no-print">
            <a class="btn btn-sm btn-success" href="laporan.php?start=<?php echo $start; ?>&end=<?php echo $end; ?>&type=<?php echo $type; ?>&export=csv&report=transaksi">Export CSV</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Kode</th>
                        <th>Barang</th>
                        <th>Supplier</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transaksi->num_rows > 0): ?>
                        <?php while ($row = $transaksi->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                <td><?php echo ucfirst($row['tipe_transaksi']); ?></td>
                                <td><?php echo $row['kode_barang']; ?></td>
                                <td><?php echo $row['nama_barang']; ?></td>
                                <td><?php echo $row['nama_supplier']; ?></td>
                                <td><?php echo $row['jumlah']; ?></td>
                                <td><?php echo $row['keterangan']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Laporan Stok</h5>
        <div class="no-print">
            <a class="btn btn-sm btn-success" href="laporan.php?export=csv&report=stok">Export CSV</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Barang</th>
                        <th>Supplier</th>
                        <th>Stok</th>
                        <th>Min</th>
                        <th>Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stok->num_rows > 0): ?>
                        <?php while ($row = $stok->fetch_assoc()): ?>
                            <tr class="<?php echo $row['stok_akhir'] <= $row['stok_minimum'] ? 'table-danger' : ''; ?>">
                                <td><?php echo $row['kode_barang']; ?></td>
                                <td><?php echo $row['nama_barang']; ?></td>
                                <td><?php echo $row['nama_supplier']; ?></td>
                                <td><?php echo $row['stok_akhir']; ?></td>
                                <td><?php echo $row['stok_minimum']; ?></td>
                                <td><?php echo $row['satuan']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted">Tidak ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Laporan Penggunaan Bahan Baku (Keluar)</h5>
        <div class="no-print">
            <a class="btn btn-sm btn-success" href="laporan.php?start=<?php echo $start; ?>&end=<?php echo $end; ?>&export=csv&report=penggunaan">Export CSV</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Barang</th>
                        <th>Supplier</th>
                        <th>Total Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($penggunaan->num_rows > 0): ?>
                        <?php while ($row = $penggunaan->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['kode_barang']; ?></td>
                                <td><?php echo $row['nama_barang']; ?></td>
                                <td><?php echo $row['nama_supplier']; ?></td>
                                <td><?php echo $row['total_keluar']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">Tidak ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
