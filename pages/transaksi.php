<?php
$page_title = "Manajemen Transaksi";
$auto_refresh = 0;
include '../includes/header.php';

$message = '';
$error = '';

// Proses tambah transaksi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $barang_id = (int)$_POST['barang_id'];
    $tipe_transaksi = $conn->real_escape_string($_POST['tipe_transaksi']);
    $jumlah = (int)$_POST['jumlah'];
    $keterangan = $conn->real_escape_string($_POST['keterangan']);
    
    // Ambil data barang
    $barang_result = $conn->query("SELECT * FROM barang WHERE id = $barang_id");
    if ($barang_result->num_rows > 0) {
        $barang = $barang_result->fetch_assoc();
        
        // Validasi stok untuk transaksi keluar
        if ($tipe_transaksi == 'keluar' && $barang['stok_akhir'] < $jumlah) {
            $error = "Stok tidak cukup! Stok tersedia: " . $barang['stok_akhir'];
        } else {
            // Tambah transaksi
            $trans_query = "INSERT INTO transaksi (barang_id, tipe_transaksi, jumlah, keterangan) 
                           VALUES ($barang_id, '$tipe_transaksi', $jumlah, '$keterangan')";
            
            if ($conn->query($trans_query)) {
                // Update stok barang
                if ($tipe_transaksi == 'masuk') {
                    $new_stok = $barang['stok_akhir'] + $jumlah;
                    $update_stok = $barang['stok_masuk'] + $jumlah;
                    $update_query = "UPDATE barang SET stok_akhir = $new_stok, stok_masuk = $update_stok WHERE id = $barang_id";
                } else {
                    $new_stok = $barang['stok_akhir'] - $jumlah;
                    $update_stok = $barang['stok_keluar'] + $jumlah;
                    $update_query = "UPDATE barang SET stok_akhir = $new_stok, stok_keluar = $update_stok WHERE id = $barang_id";
                }
                
                if ($conn->query($update_query)) {
                $message = "Transaksi berhasil ditambahkan!";
                safeRedirect("transaksi.php");
                    // Reset form
                    $_POST = [];
                } else {
                    $error = "Error: " . $conn->error;
                }
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    } else {
        $error = "Barang tidak ditemukan!";
    }
}

// Ambil semua barang
$barangs = $conn->query("SELECT b.id, b.nama_barang, b.kode_barang, b.stok_akhir, s.nama_supplier 
                         FROM barang b 
                         LEFT JOIN supplier s ON b.supplier_id = s.id 
                         ORDER BY b.nama_barang");

// Ambil transaksi dengan filter tanggal
$filter_tanggal = isset($_GET['filter_tanggal']) ? $_GET['filter_tanggal'] : date('Y-m-d');
$transaksis = $conn->query("SELECT t.*, b.nama_barang, b.kode_barang, s.nama_supplier 
                           FROM transaksi t
                           LEFT JOIN barang b ON t.barang_id = b.id
                           LEFT JOIN supplier s ON b.supplier_id = s.id
                           WHERE DATE(t.tanggal) = '$filter_tanggal'
                           ORDER BY t.tanggal DESC");
?>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tambah Transaksi</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="barang_id" class="form-label">Pilih Barang *</label>
                        <select class="form-control" id="barang_id" name="barang_id" required onchange="updateStok()">
                            <option value="">-- Pilih Barang --</option>
                            <?php 
                            while ($brg = $barangs->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $brg['id']; ?>" data-stok="<?php echo $brg['stok_akhir']; ?>">
                                    <?php echo $brg['kode_barang'] . ' - ' . $brg['nama_barang']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="stok_info" class="form-label">Stok Tersedia</label>
                        <input type="text" class="form-control" id="stok_info" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipe_transaksi" class="form-label">Tipe Transaksi *</label>
                        <select class="form-control" id="tipe_transaksi" name="tipe_transaksi" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="masuk">Barang Masuk (In)</option>
                            <option value="keluar">Barang Keluar (Out)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="jumlah" class="form-label">Jumlah *</label>
                        <input type="number" class="form-control" id="jumlah" name="jumlah" required min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Catatan transaksi..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Simpan Transaksi</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Data Transaksi</h5>
                    <form method="GET" class="d-flex gap-2">
                        <input type="date" name="filter_tanggal" class="form-control" 
                               value="<?php echo $filter_tanggal; ?>">
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                        <a href="?filter_tanggal=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-secondary">Hari Ini</a>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <?php if ($transaksis->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Waktu</th>
                                    <th>Barang</th>
                                    <th>Supplier</th>
                                    <th>Tipe</th>
                                    <th>Jumlah</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while ($row = $transaksis->fetch_assoc()): 
                                    $tipe_badge = $row['tipe_transaksi'] == 'masuk' ? 
                                        '<span class="badge bg-success"><i class="fas fa-arrow-down"></i> Masuk</span>' : 
                                        '<span class="badge bg-danger"><i class="fas fa-arrow-up"></i> Keluar</span>';
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('H:i', strtotime($row['tanggal'])); ?></td>
                                        <td>
                                            <strong><?php echo $row['nama_barang']; ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $row['kode_barang']; ?></small>
                                        </td>
                                        <td><?php echo $row['nama_supplier']; ?></td>
                                        <td><?php echo $tipe_badge; ?></td>
                                        <td><strong><?php echo $row['jumlah']; ?></strong></td>
                                        <td><?php echo $row['keterangan']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Tidak ada transaksi pada tanggal <?php echo date('d/m/Y', strtotime($filter_tanggal)); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function updateStok() {
    var selected = document.getElementById('barang_id');
    var stok = selected.options[selected.selectedIndex].getAttribute('data-stok');
    document.getElementById('stok_info').value = stok ? stok + ' unit' : '';
}
</script>

<?php include '../includes/footer.php'; ?>
