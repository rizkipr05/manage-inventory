<?php
$page_title = "Manajemen User";
include '../includes/header.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action === '') {
        $action = 'add';
    }
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    if ($action == 'add') {
        if (!$username || !$password) {
            $error = 'Username dan password wajib diisi.';
        } else {
            $query = "INSERT INTO admin (username, password, email) VALUES ('$username', SHA2('$password', 256), '$email')";
            if ($conn->query($query)) {
                $message = 'User berhasil ditambahkan!';
                safeRedirect("users.php");
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    } elseif ($action == 'edit' && $id > 0) {
        $set_password = '';
        if (!empty($password)) {
            $set_password = ", password = SHA2('$password', 256)";
        }
        $query = "UPDATE admin SET username = '$username', email = '$email' $set_password WHERE id = $id";
        if ($conn->query($query)) {
            $message = 'User berhasil diperbarui!';
            safeRedirect("users.php");
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    if ($delete_id == $_SESSION['admin_id']) {
        $error = 'Tidak bisa menghapus akun sendiri.';
    } else {
        $query = "DELETE FROM admin WHERE id = $delete_id";
        if ($conn->query($query)) {
            $message = 'User berhasil dihapus!';
            safeRedirect("users.php");
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

$edit_data = null;
if ($action == 'edit' && $id > 0) {
    $result = $conn->query("SELECT * FROM admin WHERE id = $id");
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

$users = $conn->query("SELECT * FROM admin ORDER BY created_at DESC");
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
                <h5 class="mb-0"><?php echo ($action == 'edit' && $edit_data) ? 'Edit User' : 'Tambah User'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo ($action == 'edit' && $edit_data) ? 'edit' : 'add'; ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo $edit_data ? $edit_data['username'] : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo $edit_data ? $edit_data['email'] : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password <?php echo $edit_data ? '(kosongkan jika tidak diubah)' : '*'; ?></label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo $edit_data ? '' : 'required'; ?>>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <?php echo ($action == 'edit' && $edit_data) ? 'Update User' : 'Simpan User'; ?>
                    </button>
                    <?php if ($action == 'edit' && $edit_data): ?>
                        <a href="users.php" class="btn btn-secondary w-100 mt-2">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Daftar User (<?php echo $users->num_rows; ?>)</h5>
            </div>
            <div class="card-body">
                <?php if ($users->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php while ($row = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $row['username']; ?></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <a href="users.php?action=edit&id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="users.php?delete=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-danger" title="Hapus"
                                               onclick="return confirmDelete(<?php echo $row['id']; ?>, '<?php echo $row['username']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Tidak ada data user</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
