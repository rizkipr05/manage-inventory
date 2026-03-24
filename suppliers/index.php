<?php
require_once __DIR__ . '/../auth/check.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Kelola Supplier - Management Data Barang';

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $errors[] = 'Nama supplier wajib diisi.';
    }

    if (!$errors) {
        if ($action === 'edit' && $id > 0) {
            $stmt = $pdo->prepare('UPDATE suppliers SET name = ?, phone = ?, address = ? WHERE id = ?');
            $stmt->execute([$name, $phone, $address, $id]);
            $success = 'Data supplier berhasil diubah.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO suppliers (name, phone, address) VALUES (?, ?, ?)');
            $stmt->execute([$name, $phone, $address]);
            $success = 'Data supplier berhasil ditambahkan.';
        }
    }
}

if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: /manage-medical/suppliers/index.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?');
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
    if (!$editData) {
        header('Location: /manage-medical/suppliers/index.php');
        exit;
    }
}

$suppliers = $pdo->query('SELECT * FROM suppliers ORDER BY id DESC')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card-flat bg-white">
            <div class="p-3 border-bottom fw-semibold"><?php echo $editData ? 'Ubah Supplier' : 'Tambah Supplier'; ?></div>
            <div class="p-3">
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars(implode(' ', $errors)); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Nama Supplier</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editData['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editData['phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($editData['address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><?php echo $editData ? 'Simpan Perubahan' : 'Tambah'; ?></button>
                    <?php if ($editData): ?>
                        <a class="btn btn-light w-100 mt-2" href="/manage-medical/suppliers/index.php">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card-flat bg-white">
            <div class="p-3 border-bottom fw-semibold">Daftar Supplier</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>No. Telepon</th>
                            <th>Alamat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$suppliers): ?>
                            <tr><td colspan="4" class="text-center text-muted">Belum ada data</td></tr>
                        <?php else: ?>
                            <?php foreach ($suppliers as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                                    <td><?php echo htmlspecialchars($s['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($s['address']); ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="/manage-medical/suppliers/index.php?action=edit&id=<?php echo $s['id']; ?>">Edit</a>
                                        <a class="btn btn-sm btn-outline-danger" href="/manage-medical/suppliers/index.php?action=delete&id=<?php echo $s['id']; ?>" onclick="return confirm('Hapus supplier ini?');">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
