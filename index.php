<?php
require_once __DIR__ . '/auth/check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Dashboard';

$totalStock = (int)$pdo->query('SELECT COALESCE(SUM(stock),0) FROM items')->fetchColumn();
$lowStock = (int)$pdo->query('SELECT COUNT(*) FROM items WHERE stock <= min_stock')->fetchColumn();

$inStock = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock_movements WHERE type='IN'")->fetchColumn();
$outStock = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock_movements WHERE type='OUT'")->fetchColumn();

$recentMoves = $pdo->query('SELECT sm.id, sm.type, sm.qty, sm.notes, sm.created_at, i.name AS item_name FROM stock_movements sm JOIN items i ON i.id = sm.item_id ORDER BY sm.created_at DESC LIMIT 5')->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>
<div class="row g-3">
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="label">Total Stok</div>
                <div class="value"><?php echo number_format($totalStock); ?></div>
            </div>
            <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="label">Hampir Habis</div>
                <div class="value"><?php echo number_format($lowStock); ?></div>
            </div>
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="label">Total Masuk</div>
                <div class="value"><?php echo number_format($inStock); ?></div>
            </div>
            <div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="label">Total Keluar</div>
                <div class="value"><?php echo number_format($outStock); ?></div>
            </div>
            <div class="stat-icon"><i class="bi bi-arrow-up-circle"></i></div>
        </div>
    </div>
</div>

<div class="card-flat bg-white mt-4">
    <div class="p-3 border-bottom fw-semibold">Pergerakan Stok Terbaru</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Item</th>
                    <th>Tipe</th>
                    <th>Qty</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$recentMoves): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada transaksi</td></tr>
                <?php else: ?>
                    <?php foreach ($recentMoves as $m): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($m['item_name']); ?></td>
                            <td><?php echo $m['type'] === 'IN' ? 'Masuk' : 'Keluar'; ?></td>
                            <td><?php echo number_format($m['qty']); ?></td>
                            <td><?php echo htmlspecialchars($m['notes']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
