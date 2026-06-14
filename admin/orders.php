<?php
$pageTitle = 'Kelola Pesanan';
require_once 'header.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = (int)$_POST['id'];
    $status    = clean($_POST['status'] ?? '');
    $payStatus = clean($_POST['payment_status'] ?? '');
    $allowed   = ['pending','processing','shipped','delivered','cancelled'];
    if (in_array($status, $allowed)) {
        $safeStatus = $db->real_escape_string($status);
        $safePay    = $db->real_escape_string($payStatus);
        $db->query("UPDATE orders SET status='$safeStatus', payment_status='$safePay' WHERE id=$id");
        setFlash('success', 'Status pesanan berhasil diperbarui.');
    }
    $back = isset($_GET['view']) ? '?view=' . (int)$_GET['view'] : '';
    redirect(SITE_URL . '/admin/orders.php' . $back);
}

// Single order view
$viewOrder  = null;
$orderItems = null;
if (isset($_GET['view'])) {
    $oid       = (int)$_GET['view'];
    $viewOrder = $db->query("SELECT o.*, u.email as user_email FROM orders o LEFT JOIN customers u ON o.customer_id = u.id WHERE o.id = $oid")->fetch_assoc();
    if ($viewOrder) $orderItems = $db->query("SELECT * FROM order_items WHERE order_id = $oid");
}

// List
$statusFilter = clean($_GET['status'] ?? '');
$search       = clean($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$per          = 15;
$offset       = ($page - 1) * $per;

$where = ['1=1'];
if ($statusFilter) $where[] = "o.status = '" . $db->real_escape_string($statusFilter) . "'";
if ($search)       $where[] = "(o.order_number LIKE '%" . $db->real_escape_string($search) . "%' OR o.shipping_name LIKE '%" . $db->real_escape_string($search) . "%')";
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total      = $db->query("SELECT COUNT(*) as n FROM orders o $whereSQL")->fetch_assoc()['n'];
$orders     = $db->query("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN customers u ON o.customer_id = u.id $whereSQL ORDER BY o.created_at DESC LIMIT $per OFFSET $offset");
$totalPages = ceil($total / $per);

$statusCounts = [];
$sc = $db->query("SELECT status, COUNT(*) as n FROM orders GROUP BY status");
while ($row = $sc->fetch_assoc()) $statusCounts[$row['status']] = $row['n'];
?>

<?php if ($viewOrder): ?>
<div class="mb-1">
    <a href="orders.php" class="btn btn-outline btn-sm">← Kembali ke Daftar</a>
</div>

<div class="order-detail-grid">
    <div>
        <div class="form-card">
            <h3>Detail Pesanan — <?= $viewOrder['order_number'] ?></h3>
            <div class="order-info-grid">
                <div>
                    <p class="detail-label">Penerima</p>
                    <p class="detail-value"><?= htmlspecialchars($viewOrder['shipping_name']) ?></p>
                    <p class="detail-sub"><?= htmlspecialchars($viewOrder['shipping_phone']) ?></p>
                    <?php if ($viewOrder['user_email']): ?>
                    <p class="detail-sub-light"><?= htmlspecialchars($viewOrder['user_email']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="detail-label">Alamat Pengiriman</p>
                    <p class="detail-address"><?= nl2br(htmlspecialchars($viewOrder['shipping_address'])) ?></p>
                    <p class="detail-address"><?= htmlspecialchars($viewOrder['shipping_city']) ?></p>
                </div>
                <div>
                    <p class="detail-label">Pembayaran</p>
                    <p class="fs-sm"><?= $viewOrder['payment_method'] === 'cod' ? 'COD (Bayar di Tempat)' : 'Transfer Bank' ?></p>
                    <span class="badge <?= $viewOrder['payment_status'] === 'paid' ? 'badge-success' : 'badge-warning' ?>">
                        <?= $viewOrder['payment_status'] === 'paid' ? 'Lunas' : 'Belum Bayar' ?>
                    </span>
                </div>
                <div>
                    <p class="detail-label">Tanggal Order</p>
                    <p class="fs-sm"><?= date('d F Y, H:i', strtotime($viewOrder['created_at'])) ?></p>
                </div>
            </div>
            <?php if ($viewOrder['notes']): ?>
            <div class="detail-note-box">
                <p class="detail-note-label">Catatan pembeli:</p>
                <p class="fs-sm"><?= htmlspecialchars($viewOrder['notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="table-card">
            <div class="table-header"><h3>Item Pesanan</h3></div>
            <table class="admin-table">
                <thead>
                    <tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Ukuran</th><th>Warna</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php while ($item = $orderItems->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-500"><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= formatRupiah($item['product_price']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= htmlspecialchars($item['size'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($item['color'] ?: '—') ?></td>
                        <td class="fw-500"><?= formatRupiah($item['subtotal']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right fw-500" style="padding:0.8rem 1.2rem;">Total Pesanan:</td>
                        <td class="fw-600 fs-lg"><?= formatRupiah($viewOrder['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Update status -->
    <div class="form-card sticky-admin">
        <h3>Update Status</h3>
        <p class="mb-1">Status saat ini: <span class="badge badge-<?= $viewOrder['status'] ?>"><?= ucfirst($viewOrder['status']) ?></span></p>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $viewOrder['id'] ?>">
            <div class="form-group">
                <label>Status Pesanan</label>
                <select name="status" class="form-control">
                    <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $viewOrder['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status Pembayaran</label>
                <select name="payment_status" class="form-control">
                    <option value="unpaid" <?= $viewOrder['payment_status'] === 'unpaid' ? 'selected' : '' ?>>Belum Bayar</option>
                    <option value="paid"   <?= $viewOrder['payment_status'] === 'paid'   ? 'selected' : '' ?>>Sudah Bayar</option>
                </select>
            </div>
            <button type="submit" class="btn btn-accent w-100">Simpan Perubahan</button>
        </form>
    </div>
</div>

<?php else: ?>
<div class="status-tabs">
    <?php
    $tabs = ['' => 'Semua', 'pending' => 'Pending', 'processing' => 'Diproses', 'shipped' => 'Dikirim', 'delivered' => 'Selesai', 'cancelled' => 'Dibatalkan'];
    foreach ($tabs as $key => $label):
        $count  = $key ? ($statusCounts[$key] ?? 0) : array_sum($statusCounts);
        $active = $statusFilter === $key;
    ?>
    <a href="?status=<?= $key ?><?= $search ? '&q='.urlencode($search) : '' ?>"
       class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline' ?>">
        <?= $label ?> (<?= $count ?>)
    </a>
    <?php endforeach; ?>
</div>

<div class="table-card">
    <div class="table-header">
        <h3>Daftar Pesanan (<?= $total ?>)</h3>
        <form method="GET" class="d-flex gap-05">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= $statusFilter ?>"><?php endif; ?>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   class="form-control" placeholder="Cari no. pesanan / nama..." style="width:230px;">
            <button type="submit" class="btn btn-outline">Cari</button>
        </form>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>No. Pesanan</th>
                <th>Pelanggan</th>
                <th>Kota</th>
                <th>Total</th>
                <th>Pembayaran</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($orders->num_rows > 0): ?>
            <?php while ($o = $orders->fetch_assoc()): ?>
            <tr>
                <td class="fw-500 fs-sm"><?= $o['order_number'] ?></td>
                <td><?= htmlspecialchars($o['shipping_name']) ?></td>
                <td class="color-muted"><?= htmlspecialchars($o['shipping_city']) ?></td>
                <td class="fw-500"><?= formatRupiah($o['total_amount']) ?></td>
                <td>
                    <span class="badge <?= $o['payment_status'] === 'paid' ? 'badge-success' : 'badge-warning' ?>">
                        <?= $o['payment_status'] === 'paid' ? 'Lunas' : 'Belum Bayar' ?>
                    </span>
                </td>
                <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                <td class="color-muted fs-sm"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td><a href="?view=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Detail</a></td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="8" class="text-center color-muted" style="padding:2rem;">Tidak ada pesanan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.2rem;">
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++):
                $url = '?' . http_build_query(array_merge($_GET, ['page' => $i])); ?>
            <?php if ($i == $page): ?><span class="active"><?= $i ?></span>
            <?php else: ?><a href="<?= $url ?>"><?= $i ?></a><?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>