<?php
$page_title = "Manajemen Supplier";
include '../includes/header.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Proses tambah/edit/hapus supplier
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action === '') {
        $action = 'add';
    }
    $nama_supplier = $conn->real_escape_string($_POST['nama_supplier']);
    $contact_person = $conn->real_escape_string($_POST['contact_person']);
    $telepon = $conn->real_escape_string($_POST['telepon']);
    $email = $conn->real_escape_string($_POST['email']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    $kota = $conn->real_escape_string($_POST['kota']);
    $provinsi = $conn->real_escape_string($_POST['provinsi']);
    
    if ($action == 'add') {
        $query = "INSERT INTO supplier (nama_supplier, contact_person, telepon, email, alamat, kota, provinsi) 
                  VALUES ('$nama_supplier', '$contact_person', '$telepon', '$email', '$alamat', '$kota', '$provinsi')";
        if ($conn->query($query)) {
            $message = "Supplier berhasil ditambahkan!";
            safeRedirect("supplier.php");
        } else {
            $error = "Error: " . $conn->error;
        }
    } elseif ($action == 'edit') {
        $query = "UPDATE supplier SET 
                  nama_supplier = '$nama_supplier',
                  contact_person = '$contact_person',
                  telepon = '$telepon',
                  email = '$email',
                  alamat = '$alamat',
                  kota = '$kota',
                  provinsi = '$provinsi'
                  WHERE id = $id";
        if ($conn->query($query)) {
            $message = "Supplier berhasil diperbarui!";
            safeRedirect("supplier.php");
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Proses hapus
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $query = "DELETE FROM supplier WHERE id = $delete_id";
    if ($conn->query($query)) {
        $message = "Supplier berhasil dihapus!";
        safeRedirect("supplier.php");
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Ambil data supplier untuk edit
$edit_data = null;
if ($action == 'edit' && $id > 0) {
    $result = $conn->query("SELECT * FROM supplier WHERE id = $id");
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

// Ambil semua supplier
$suppliers = $conn->query("SELECT * FROM supplier ORDER BY created_at DESC");
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
                    <?php echo ($action == 'edit' && $edit_data) ? 'Edit Supplier' : 'Tambah Supplier Baru'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo ($action == 'edit' && $edit_data) ? 'edit' : 'add'; ?>">
                    <div class="mb-3">
                        <label for="nama_supplier" class="form-label">Nama Supplier *</label>
                        <input type="text" class="form-control" id="nama_supplier" name="nama_supplier" 
                               value="<?php echo $edit_data ? $edit_data['nama_supplier'] : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" 
                               value="<?php echo $edit_data ? $edit_data['contact_person'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="telepon" class="form-label">Telepon</label>
                        <input type="tel" class="form-control" id="telepon" name="telepon" 
                               value="<?php echo $edit_data ? $edit_data['telepon'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo $edit_data ? $edit_data['email'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3"><?php echo $edit_data ? $edit_data['alamat'] : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="kota" class="form-label">Kota</label>
                        <input type="text" class="form-control" id="kota" name="kota" 
                               value="<?php echo $edit_data ? $edit_data['kota'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="provinsi" class="form-label">Provinsi</label>
                        <input type="text" class="form-control" id="provinsi" name="provinsi" 
                               value="<?php echo $edit_data ? $edit_data['provinsi'] : ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <?php echo ($action == 'edit' && $edit_data) ? 'Update Supplier' : 'Simpan Supplier'; ?>
                    </button>
                    <?php if ($action == 'edit' && $edit_data): ?>
                        <a href="supplier.php" class="btn btn-secondary w-100 mt-2">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Daftar Supplier (<?php echo $suppliers->num_rows; ?>)</h5>
            </div>
            <div class="card-body">
                <?php if ($suppliers->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Supplier</th>
                                    <th>Contact Person</th>
                                    <th>Telepon</th>
                                    <th>Email</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $suppliers->data_seek(0);
                                while ($row = $suppliers->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <strong><?php echo $row['nama_supplier']; ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $row['kota'] . ', ' . $row['provinsi']; ?></small>
                                        </td>
                                        <td><?php echo $row['contact_person']; ?></td>
                                        <td><?php echo $row['telepon']; ?></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td>
                                            <a href="supplier.php?action=edit&id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="supplier.php?delete=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirmDelete(<?php echo $row['id']; ?>, '<?php echo $row['nama_supplier']; ?>')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Tidak ada data supplier</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
