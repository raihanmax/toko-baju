<?php
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php?redirect=/account.php');
$pageTitle = 'Akun Saya';
$db = getDB();

$tab = clean($_GET['tab'] ?? 'orders');

// Get user
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param('i', $_SESSION['customer_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = clean($_POST['name'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $addr  = clean($_POST['address'] ?? '');
    $stmt  = $db->prepare("UPDATE customers SET name=?, phone=?, address=? WHERE id=?");
    $stmt->bind_param('sssi', $name, $phone, $addr, $_SESSION['customer_id']);
    $stmt->execute();
    $_SESSION['customer_name'] = $name;
    setFlash('success', 'Profil berhasil diperbarui.');
    redirect(SITE_URL . '/account.php?tab=profile');
}

// Get orders
$orders = $db->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
$orders->bind_param('i', $_SESSION['customer_id']);
$orders->execute();
$ordersList = $orders->get_result();
?>
<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="account-layout">
        <!-- Sidebar -->
        <div class="account-sidebar">
            <h4>👤 <?= htmlspecialchars($user['name']) ?></h4>
            <p style="font-size:0.8rem;color:#888;margin-bottom:1rem;"><?= htmlspecialchars($user['email']) ?></p>
            <nav class="account-nav">
                <a href="?tab=orders" <?= $tab === 'orders' ? 'style="color:#a88848;font-weight:500"' : '' ?>>📦 Pesanan Saya</a>
                <a href="?tab=profile" <?= $tab === 'profile' ? 'style="color:#a88848;font-weight:500"' : '' ?>>✏️ Edit Profil</a>
                <a href="logout.php" style="color:#c0392b;">↩ Keluar</a>
            </nav>
        </div>

        <!-- Content -->
        <div>
            <?php if ($tab === 'orders'): ?>
            <h2 style="margin-bottom:1.5rem;">Pesanan Saya</h2>
            <?php if ($ordersList->num_rows > 0): ?>
                <?php while ($order = $ordersList->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <p style="font-weight:500;"><?= $order['order_number'] ?></p>
                            <p style="font-size:0.82rem;color:#888;"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></p>
                        </div>
                        <div style="text-align:right;">
                            <span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                            <p style="font-size:0.9rem;font-weight:500;margin-top:0.3rem;"><?= formatRupiah($order['total_amount']) ?></p>
                        </div>
                    </div>
                    <!-- Items preview -->
                    <?php
                    $items = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    $items->bind_param('i', $order['id']);
                    $items->execute();
                    $itemsList = $items->get_result();
                    ?>
                    <div style="font-size:0.85rem;color:#666;">
                        <?php while ($item = $itemsList->fetch_assoc()): ?>
                        <span><?= htmlspecialchars($item['product_name']) ?> ×<?= $item['quantity'] ?></span>
                        <?php endwhile; ?>
                    </div>
                    <div style="margin-top:0.8rem;font-size:0.82rem;color:#888;">
                        📍 <?= htmlspecialchars($order['shipping_city']) ?> &nbsp;·&nbsp;
                        💳 <?= $order['payment_method'] === 'cod' ? 'COD' : 'Transfer Bank' ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📦</div>
                    <h3>Belum ada pesanan</h3>
                    <p>Yuk belanja sekarang!</p>
                    <a href="products.php" class="btn-primary">Mulai Belanja</a>
                </div>
            <?php endif; ?>

            <?php elseif ($tab === 'profile'): ?>
            <h2 style="margin-bottom:1.5rem;">Edit Profil</h2>
            <form method="POST" style="max-width:500px;">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email (tidak bisa diubah/hubungi admin jika ingin Mengubah)</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:0.6;">
                </div>
                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08xx-xxxx-xxxx">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Alamat lengkap"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>