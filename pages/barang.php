<?php
$page_title = "Manajemen Data Barang";
$auto_refresh = 0;
include '../includes/header.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';
$form = [];

// Pastikan kolom stok_minimum ada (migrasi ringan)
$check_col = $conn->query("SHOW COLUMNS FROM barang LIKE 'stok_minimum'");
if ($check_col && $check_col->num_rows === 0) {
    $conn->query("ALTER TABLE barang ADD COLUMN stok_minimum INT DEFAULT 10 AFTER stok_akhir");
}

// Ambil semua supplier
$suppliers = $conn->query("SELECT id, nama_supplier FROM supplier ORDER BY nama_supplier");
$suppliers_count = $suppliers ? $suppliers->num_rows : 0;

// Proses tambah/edit/hapus barang
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form = $_POST;
    if ($action === '') {
        $action = 'add';
    }
    $nama_barang = $conn->real_escape_string($_POST['nama_barang']);
    $kode_barang = $conn->real_escape_string($_POST['kode_barang']);
    $supplier_id = (int)$_POST['supplier_id'];
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $stok_awal = (int)$_POST['stok_awal'];
    $stok_masuk = (int)$_POST['stok_masuk'];
    $stok_keluar = (int)$_POST['stok_keluar'];
    $harga_unit = (float)$_POST['harga_unit'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_kadaluarsa = $_POST['tanggal_kadaluarsa'];
    $satuan = $conn->real_escape_string($_POST['satuan']);
    
    $stok_minimum = (int)$_POST['stok_minimum'];
    $stok_akhir = $stok_awal + $stok_masuk - $stok_keluar;
    
    if ($suppliers_count === 0) {
        $error = "Tambah supplier terlebih dahulu sebelum menambah barang.";
    } elseif ($supplier_id <= 0) {
        $error = "Supplier wajib dipilih.";
    } elseif ($action == 'add') {
        $query = "INSERT INTO barang (nama_barang, kode_barang, supplier_id, kategori, stok_awal, stok_masuk, stok_keluar, stok_akhir, stok_minimum, harga_unit, tanggal_masuk, tanggal_kadaluarsa, satuan) 
                  VALUES ('$nama_barang', '$kode_barang', $supplier_id, '$kategori', $stok_awal, $stok_masuk, $stok_keluar, $stok_akhir, $stok_minimum, $harga_unit, '$tanggal_masuk', '$tanggal_kadaluarsa', '$satuan')";
        if ($conn->query($query)) {
            $message = "Barang berhasil ditambahkan!";
            safeRedirect("barang.php");
        } else {
            $error = "Error: " . $conn->error;
        }
    } elseif ($action == 'edit') {
        $query = "UPDATE barang SET 
                  nama_barang = '$nama_barang',
                  kode_barang = '$kode_barang',
                  supplier_id = $supplier_id,
                  kategori = '$kategori',
                  stok_awal = $stok_awal,
                  stok_masuk = $stok_masuk,
                  stok_keluar = $stok_keluar,
                  stok_akhir = $stok_akhir,
                  stok_minimum = $stok_minimum,
                  harga_unit = $harga_unit,
                  tanggal_masuk = '$tanggal_masuk',
                  tanggal_kadaluarsa = '$tanggal_kadaluarsa',
                  satuan = '$satuan'
                  WHERE id = $id";
        if ($conn->query($query)) {
            $message = "Barang berhasil diperbarui!";
            safeRedirect("barang.php");
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Proses hapus
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $query = "DELETE FROM barang WHERE id = $delete_id";
    if ($conn->query($query)) {
        $message = "Barang berhasil dihapus!";
        safeRedirect("barang.php");
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Ambil data barang untuk edit
$edit_data = null;
if ($action == 'edit' && $id > 0) {
    $result = $conn->query("SELECT * FROM barang WHERE id = $id");
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

// Ambil semua barang
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_query = $search ? " WHERE nama_barang LIKE '%$search%' OR kode_barang LIKE '%$search%'" : "";
$barangs = $conn->query("SELECT b.*, s.nama_supplier FROM barang b LEFT JOIN supplier s ON b.supplier_id = s.id $search_query ORDER BY b.created_at DESC");
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
                <h5 class="mb-0">
                    <?php echo ($action == 'edit' && $edit_data) ? 'Edit Barang' : 'Tambah Barang Baru'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo ($action == 'edit' && $edit_data) ? 'edit' : 'add'; ?>">
                    <div class="mb-3">
                        <label for="nama_barang" class="form-label">Nama Barang *</label>
                        <input type="text" class="form-control" id="nama_barang" name="nama_barang" 
                               value="<?php echo $edit_data ? $edit_data['nama_barang'] : ($form['nama_barang'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="kode_barang" class="form-label">Kode Barang *</label>
                        <input type="text" class="form-control" id="kode_barang" name="kode_barang" 
                               value="<?php echo $edit_data ? $edit_data['kode_barang'] : ($form['kode_barang'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Supplier *</label>
                        <select class="form-control" id="supplier_id" name="supplier_id" required <?php echo $suppliers_count === 0 ? 'disabled' : ''; ?>>
                            <option value="">-- Pilih Supplier --</option>
                            <?php 
                            $suppliers->data_seek(0);
                            while ($sup = $suppliers->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $sup['id']; ?>" 
                                    <?php
                                    $selected_id = $edit_data ? $edit_data['supplier_id'] : ($form['supplier_id'] ?? '');
                                    echo ($selected_id == $sup['id']) ? 'selected' : '';
                                    ?>>
                                    <?php echo $sup['nama_supplier']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if ($suppliers_count === 0): ?>
                            <small class="text-danger">Belum ada supplier. Tambahkan supplier dulu.</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="kategori" class="form-label">Kategori</label>
                        <input type="text" class="form-control" id="kategori" name="kategori" 
                               value="<?php echo $edit_data ? $edit_data['kategori'] : ($form['kategori'] ?? ''); ?>"
                               placeholder="e.g: Obat, Alat Medis, Vitamin">
                    </div>
                    
                    <div class="mb-3">
                        <label for="stok_awal" class="form-label">Stok Awal</label>
                        <input type="number" class="form-control" id="stok_awal" name="stok_awal" 
                               value="<?php echo $edit_data ? $edit_data['stok_awal'] : ($form['stok_awal'] ?? '0'); ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="stok_masuk" class="form-label">Stok Masuk</label>
                        <input type="number" class="form-control" id="stok_masuk" name="stok_masuk" 
                               value="<?php echo $edit_data ? $edit_data['stok_masuk'] : ($form['stok_masuk'] ?? '0'); ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="stok_keluar" class="form-label">Stok Keluar</label>
                        <input type="number" class="form-control" id="stok_keluar" name="stok_keluar" 
                               value="<?php echo $edit_data ? $edit_data['stok_keluar'] : ($form['stok_keluar'] ?? '0'); ?>" min="0">
                    </div>

                    <div class="mb-3">
                        <label for="stok_minimum" class="form-label">Stok Minimum (Alert)</label>
                        <input type="number" class="form-control" id="stok_minimum" name="stok_minimum" 
                               value="<?php echo $edit_data ? $edit_data['stok_minimum'] : ($form['stok_minimum'] ?? '10'); ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="harga_unit" class="form-label">Harga Unit (Rp)</label>
                        <input type="number" class="form-control" id="harga_unit" name="harga_unit" 
                               value="<?php echo $edit_data ? $edit_data['harga_unit'] : ($form['harga_unit'] ?? '0'); ?>" min="0" step="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label for="satuan" class="form-label">Satuan</label>
                        <input type="text" class="form-control" id="satuan" name="satuan" 
                               value="<?php echo $edit_data ? $edit_data['satuan'] : ($form['satuan'] ?? ''); ?>"
                               placeholder="e.g: Box, Strip, Pcs, Botol">
                    </div>
                    
                    <div class="mb-3">
                        <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
                        <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk" 
                               value="<?php echo $edit_data ? $edit_data['tanggal_masuk'] : ($form['tanggal_masuk'] ?? date('Y-m-d')); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="tanggal_kadaluarsa" class="form-label">Tanggal Kadaluarsa</label>
                        <input type="date" class="form-control" id="tanggal_kadaluarsa" name="tanggal_kadaluarsa" 
                               value="<?php echo $edit_data ? $edit_data['tanggal_kadaluarsa'] : ($form['tanggal_kadaluarsa'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <?php echo ($action == 'edit' && $edit_data) ? 'Update Barang' : 'Simpan Barang'; ?>
                    </button>
                    <?php if ($action == 'edit' && $edit_data): ?>
                        <a href="barang.php" class="btn btn-secondary w-100 mt-2">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Barang (<?php echo $barangs->num_rows; ?>)</h5>
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Cari barang..." 
                               value="<?php echo $search; ?>">
                        <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                        <?php if ($search): ?>
                            <a href="barang.php" class="btn btn-sm btn-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <?php if ($barangs->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Kode</th>
                                    <th>Nama Barang</th>
                                    <th>Supplier</th>
                                    <th>Stok</th>
                                    <th>Min</th>
                                    <th>Harga</th>
                                    <th>Expired</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while ($row = $barangs->fetch_assoc()): 
                                    $min_stock = isset($row['stok_minimum']) ? (int)$row['stok_minimum'] : 0;
                                    $is_low_stock = $row['stok_akhir'] <= $min_stock;
                                    $status_color = $is_low_stock ? 'danger' : ($row['stok_akhir'] <= $min_stock + 10 ? 'warning' : 'success');
                                    $expired_status = '';
                                    if ($row['tanggal_kadaluarsa']) {
                                        $days_left = (strtotime($row['tanggal_kadaluarsa']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                                        if ($days_left < 0) {
                                            $expired_status = '<span class="badge bg-danger">EXPIRED</span>';
                                        } elseif ($days_left <= 30) {
                                            $expired_status = '<span class="badge bg-warning">' . (int)$days_left . ' hari</span>';
                                        }
                                    }
                                ?>
                                    <tr class="<?php echo $is_low_stock ? 'table-danger' : ''; ?>">
                                        <td><?php echo $no++; ?></td>
                                        <td><small class="text-muted"><?php echo $row['kode_barang']; ?></small></td>
                                        <td><?php echo $row['nama_barang']; ?></td>
                                        <td><?php echo $row['nama_supplier']; ?></td>
                                        <td><strong><?php echo $row['stok_akhir']; ?> <?php echo $row['satuan']; ?></strong></td>
                                        <td><?php echo $min_stock; ?></td>
                                        <td><?php echo number_format($row['harga_unit'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php echo $expired_status ? $expired_status : '<small class="text-muted">-</small>'; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_color; ?>">
                                                <?php 
                                                if ($is_low_stock) echo 'Minimum';
                                                elseif ($row['stok_akhir'] <= $min_stock + 10) echo 'Terbatas';
                                                else echo 'Aman';
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="barang.php?action=edit&id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="barang.php?delete=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-danger" title="Hapus"
                                               onclick="return confirmDelete(<?php echo $row['id']; ?>, '<?php echo $row['nama_barang']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Tidak ada data barang</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
