<?php
$pageTitle = 'Dashboard';
require_once 'header.php';
$db = getDB();

$totalOrders   = $db->query("SELECT COUNT(*) as n FROM orders")->fetch_assoc()['n'];
$totalRevenue  = $db->query("SELECT SUM(total_amount) as n FROM orders WHERE status != 'cancelled'")->fetch_assoc()['n'] ?? 0;
$totalProducts = $db->query("SELECT COUNT(*) as n FROM products WHERE is_active = 1")->fetch_assoc()['n'];
$totalUsers    = $db->query("SELECT COUNT(*) as n FROM customers ")->fetch_assoc()['n'];
$pendingOrders = $db->query("SELECT COUNT(*) as n FROM orders WHERE status = 'pending'")->fetch_assoc()['n'];

$recentOrders = $db->query("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN customers u ON o.customer_id = u.id ORDER BY o.created_at DESC LIMIT 8");
$lowStock     = $db->query("SELECT * FROM products WHERE stock <= 5 AND is_active = 1 ORDER BY stock ASC LIMIT 5");
?>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon">Order</span>
        <div class="stat-label">Total Pesanan</div>
        <div class="stat-value"><?= $totalOrders ?></div>
        <div class="stat-trend"><?= $pendingOrders ?> menunggu konfirmasi</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">Perkiraan</span>
        <div class="stat-label">Total Pendapatan</div>
        <div class="stat-value" style="font-size:1.3rem;"><?= formatRupiah($totalRevenue) ?></div>
        <div class="stat-trend">Semua waktu</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">Produk</span>
        <div class="stat-label">Total Produk</div>
        <div class="stat-value"><?= $totalProducts ?></div>
        <div class="stat-trend">Produk aktif</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">👤</span>
        <div class="stat-label">Total Pelanggan</div>
        <div class="stat-value"><?= $totalUsers ?></div>
        <div class="stat-trend">Terdaftar</div>
    </div>
</div>

<div class="stat-layout">
    <!-- Recent orders -->
    <div class="table-card">
        <div class="table-header">
            <h3>Pesanan Terbaru</h3>
            <a href="orders.php" class="btn btn-outline btn-sm">Semua Pesanan →</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $recentOrders->fetch_assoc()): ?>
                <tr>
                    <td class="fw-500 fs-sm"><?= $order['order_number'] ?></td>
                    <td><?= htmlspecialchars($order['shipping_name']) ?></td>
                    <td><?= formatRupiah($order['total_amount']) ?></td>
                    <td><span class="badge badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                    <td class="color-muted fs-sm"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                    <td><a href="orders.php?view=<?= $order['id'] ?>" class="btn btn-outline btn-sm">Detail</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Low stock -->
    <div class="table-card">
        <div class="table-header"><h3>⚠️ Stok Menipis</h3></div>
        <?php if ($lowStock->num_rows > 0): ?>
        <table class="admin-table">
            <thead><tr><th>Produk</th><th>Stok</th></tr></thead>
            <tbody>
                <?php while ($p = $lowStock->fetch_assoc()): ?>
                <tr>
                    <td class="fs-sm"><?= htmlspecialchars(substr($p['name'], 0, 28)) ?>...</td>
                    <td><span class="badge <?= $p['stock'] === 0 ? 'badge-danger' : 'badge-warning' ?>"><?= $p['stock'] ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state fs-sm">✅ Semua stok aman</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>