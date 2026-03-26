<?php
$page_title = "Manajemen Data Bahan Baku";
$auto_refresh = 0;
include '../includes/header.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';
$form = [];

function excelSerialToDate($serial) {
    if ($serial === '' || $serial === null) {
        return '';
    }
    $serial = (int)floor((float)$serial);
    if ($serial <= 0) {
        return '';
    }
    $base = new DateTime('1899-12-30');
    $base->modify("+{$serial} days");
    return $base->format('Y-m-d');
}

function readXlsxRows($filePath, &$errMsg) {
    $errMsg = '';
    if (!class_exists('ZipArchive')) {
        $errMsg = 'Ekstensi ZipArchive tidak tersedia di server.';
        return [];
    }
    if (!file_exists($filePath)) {
        $errMsg = 'File tidak ditemukan.';
        return [];
    }
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        $errMsg = 'Gagal membuka file Excel.';
        return [];
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $shared = simplexml_load_string($sharedXml);
        if ($shared && isset($shared->si)) {
            $i = 0;
            foreach ($shared->si as $si) {
                $texts = [];
                foreach ($si->t as $t) {
                    $texts[] = (string)$t;
                }
                foreach ($si->r as $r) {
                    if (isset($r->t)) {
                        $texts[] = (string)$r->t;
                    }
                }
                $sharedStrings[$i] = implode('', $texts);
                $i++;
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        $zip->close();
        $errMsg = 'Sheet1 tidak ditemukan di file Excel.';
        return [];
    }

    $sheet = simplexml_load_string($sheetXml);
    $rows = [];
    if ($sheet && isset($sheet->sheetData->row)) {
        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $cellRef = (string)$c['r'];
                $col = preg_replace('/[^A-Z]/', '', $cellRef);
                $type = (string)$c['t'];
                $v = isset($c->v) ? (string)$c->v : '';
                if ($type === 's') {
                    $v = $sharedStrings[(int)$v] ?? '';
                }
                $rowData[$col] = $v;
            }
            $rows[] = $rowData;
        }
    }
    $zip->close();
    return $rows;
}

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
    if ($action === 'import_excel') {
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $error = "File Excel gagal diunggah.";
        } else {
            $tmpPath = $_FILES['excel_file']['tmp_name'];
            $import_error = '';
            $rows = readXlsxRows($tmpPath, $import_error);
            if ($import_error) {
                $error = $import_error;
            } else {
                // Header ada di baris ke-2 (index 1)
                $data_rows = [];
                foreach ($rows as $idx => $row) {
                    if ($idx <= 1) {
                        continue;
                    }
                    if (empty($row['A']) || empty($row['B'])) {
                        continue;
                    }
                    $data_rows[] = $row;
                }

                if (isset($_POST['hapus_sebelum_import']) && $_POST['hapus_sebelum_import'] === '1') {
                    $conn->query("DELETE FROM barang");
                }

                $imported = 0;
                foreach ($data_rows as $row) {
                    $kode_barang = $conn->real_escape_string(trim($row['A'] ?? ''));
                    $nama_barang = $conn->real_escape_string(trim($row['B'] ?? ''));
                    $supplier_nama = trim($row['C'] ?? '');
                    $tanggal_masuk = excelSerialToDate($row['D'] ?? '');
                    $tanggal_kadaluarsa = excelSerialToDate($row['E'] ?? '');
                    $stok_awal = (int)floor((float)($row['F'] ?? 0));
                    $stok_akhir = (int)floor((float)($row['G'] ?? 0));
                    $satuan = $conn->real_escape_string(trim($row['H'] ?? ''));
                    $harga_unit = (float)($row['I'] ?? 0);
                    $kategori = 'Bahan Baku';

                    // Supplier: ambil atau buat
                    $supplier_id = 0;
                    if ($supplier_nama !== '') {
                        $safe_supplier = $conn->real_escape_string($supplier_nama);
                        $sup_res = $conn->query("SELECT id FROM supplier WHERE nama_supplier = '$safe_supplier' LIMIT 1");
                        if ($sup_res && $sup_res->num_rows > 0) {
                            $supplier_id = (int)$sup_res->fetch_assoc()['id'];
                        } else {
                            $conn->query("INSERT INTO supplier (nama_supplier) VALUES ('$safe_supplier')");
                            $supplier_id = (int)$conn->insert_id;
                        }
                    }
                    if ($supplier_id <= 0) {
                        $sup_res = $conn->query("SELECT id FROM supplier ORDER BY id ASC LIMIT 1");
                        if ($sup_res && $sup_res->num_rows > 0) {
                            $supplier_id = (int)$sup_res->fetch_assoc()['id'];
                        } else {
                            $conn->query("INSERT INTO supplier (nama_supplier) VALUES ('Supplier Umum')");
                            $supplier_id = (int)$conn->insert_id;
                        }
                    }

                    $stok_masuk = 0;
                    $stok_keluar = max(0, $stok_awal - $stok_akhir);
                    $stok_minimum = 10;

                    $cek = $conn->query("SELECT id FROM barang WHERE kode_barang = '$kode_barang' LIMIT 1");
                    if ($cek && $cek->num_rows > 0) {
                        $existing_id = (int)$cek->fetch_assoc()['id'];
                        $query = "UPDATE barang SET
                                  nama_barang = '$nama_barang',
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
                                  WHERE id = $existing_id";
                        $conn->query($query);
                        $imported++;
                    } else {
                        $query = "INSERT INTO barang (nama_barang, kode_barang, supplier_id, kategori, stok_awal, stok_masuk, stok_keluar, stok_akhir, stok_minimum, harga_unit, tanggal_masuk, tanggal_kadaluarsa, satuan)
                                  VALUES ('$nama_barang', '$kode_barang', $supplier_id, '$kategori', $stok_awal, $stok_masuk, $stok_keluar, $stok_akhir, $stok_minimum, $harga_unit, '$tanggal_masuk', '$tanggal_kadaluarsa', '$satuan')";
                        if ($conn->query($query)) {
                            $imported++;
                        }
                    }
                }

                $message = "Import selesai. Data diproses: " . $imported . " baris.";
                safeRedirect("barang.php");
            }
        }
    } else {

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
                $message = "Bahan berhasil ditambahkan!";
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
                $message = "Bahan berhasil diperbarui!";
                safeRedirect("barang.php");
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

// Proses hapus
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $query = "DELETE FROM barang WHERE id = $delete_id";
    if ($conn->query($query)) {
        $message = "Bahan berhasil dihapus!";
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
                    <?php echo ($action == 'edit' && $edit_data) ? 'Edit Bahan' : 'Tambah Bahan Baru'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo ($action == 'edit' && $edit_data) ? 'edit' : 'add'; ?>">
                    <div class="mb-3">
                        <label for="nama_barang" class="form-label">Nama Bahan *</label>
                        <input type="text" class="form-control" id="nama_barang" name="nama_barang" 
                               value="<?php echo $edit_data ? $edit_data['nama_barang'] : ($form['nama_barang'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="kode_barang" class="form-label">Kode Bahan *</label>
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
                               placeholder="e.g: Bahan Baku, Bumbu, Kemasan">
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
                               placeholder="e.g: kg, liter, pcs, pack">
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
                        <?php echo ($action == 'edit' && $edit_data) ? 'Update Bahan' : 'Simpan Bahan'; ?>
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
                    <h5 class="mb-0">Daftar Bahan (<?php echo $barangs->num_rows; ?>)</h5>
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Cari bahan..." 
                               value="<?php echo $search; ?>">
                        <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                        <?php if ($search): ?>
                            <a href="barang.php" class="btn btn-sm btn-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="border rounded p-3 mb-3">
                    <h6 class="mb-2">Import Excel (Inventory Bahan Baku)</h6>
                    <form method="POST" enctype="multipart/form-data" class="row g-2 align-items-center">
                        <input type="hidden" name="action" value="import_excel">
                        <div class="col-md-7">
                            <input type="file" class="form-control" name="excel_file" accept=".xlsx" required>
                            <small class="text-muted">Gunakan format file: "Pencatatan_Inventory_Bahan_Baku".</small>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="hapus_sebelum_import" value="1" id="hapus_sebelum_import">
                                <label class="form-check-label" for="hapus_sebelum_import">Hapus data lama</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">Import</button>
                        </div>
                    </form>
                </div>

                <?php if ($barangs->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Kode</th>
                                    <th>Nama Bahan</th>
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
                                        } else {
                                            $expired_status = '<span class="badge bg-success">AMAN</span>';
                                        }
                                    } else {
                                        $expired_status = '<small class="text-muted">-</small>';
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
                                            <?php echo $expired_status; ?>
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
                    <p class="text-center text-muted">Tidak ada data bahan</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
