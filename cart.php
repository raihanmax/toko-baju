<?php
require_once 'includes/config.php';
$pageTitle = 'Keranjang Belanja';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $cartKey = $_POST['cart_key'] ?? '';
    $cart    = getCart();

    if ($action === 'remove' && isset($cart[$cartKey])) {
        unset($cart[$cartKey]);
        $_SESSION['cart'] = $cart;
        setFlash('success', 'Produk dihapus dari keranjang.');
    } elseif ($action === 'update') {
        foreach ($_POST['qty'] as $key => $qty) {
            if (isset($cart[$key])) $cart[$key]['quantity'] = max(1, (int)$qty);
        }
        $_SESSION['cart'] = $cart;
        setFlash('success', 'Keranjang diperbarui.');
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        setFlash('info', 'Keranjang dikosongkan.');
    }
    redirect(SITE_URL . '/cart.php');
}

$cart  = getCart();
$total = getCartTotal();
?>
<?php include 'includes/header.php'; ?>

<div class="container cart-page">
    <h1 class="mb-2">Keranjang Belanja</h1>

    <?php if (empty($cart)): ?>
    <div class="empty-state">
        <div class="icon">🛒</div>
        <h3>Keranjangmu masih kosong</h3>
        <p>Yuk, temukan produk favorit kamu!</p>
        <a href="products.php" class="btn-primary">Mulai Belanja</a>
    </div>

    <?php else: ?>
    <div class="cart-layout">
        <!-- Items -->
        <div>
            <form method="POST" id="cartForm">
                <input type="hidden" name="action" value="update">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $key => $item): ?>
                        <tr>
                            <td>
                                <div class="cart-product-info">
                                    <?php if ($item['image'] && file_exists(UPLOAD_DIR . $item['image'])): ?>
                                        <img src="<?= UPLOAD_URL . $item['image'] ?>" alt="" class="cart-product-img">
                                    <?php else: ?>
                                        <div class="cart-product-img d-flex align-center justify-center" style="font-size:2rem;">👗</div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="cart-product-name">
                                            <a href="product.php?slug=<?= $item['slug'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                                        </p>
                                        <p class="cart-product-meta">
                                            <?= $item['size'] ? 'Ukuran: ' . $item['size'] : '' ?>
                                            <?= ($item['size'] && $item['color']) ? ' · ' : '' ?>
                                            <?= $item['color'] ? 'Warna: ' . $item['color'] : '' ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td><?= formatRupiah($item['price']) ?></td>
                            <td>
                                <div class="qty-control">
                                    <button type="button" onclick="this.nextElementSibling.stepDown(); this.closest('form').submit()">−</button>
                                    <input type="number" name="qty[<?= htmlspecialchars($key) ?>]"
                                           value="<?= $item['quantity'] ?>" min="1"
                                           class="form-control" style="width:55px;padding:0.3rem;text-align:center;">
                                    <button type="button" onclick="this.previousElementSibling.stepUp(); this.closest('form').submit()">+</button>
                                </div>
                            </td>
                            <td class="fw-500"><?= formatRupiah($item['price'] * $item['quantity']) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_key" value="<?= htmlspecialchars($key) ?>">
                                    <button type="submit" class="btn-ghost" onclick="return confirm('Hapus produk ini?')">✕</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>

            <div class="cart-actions">
                <a href="products.php" class="btn-ghost">← Lanjut Belanja</a>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn-ghost color-danger"
                            onclick="return confirm('Kosongkan keranjang?')">🗑 Kosongkan</button>
                </form>
            </div>
        </div>

        <!-- Summary -->
        <div class="cart-summary">
            <h3>Ringkasan Pesanan</h3>
            <div class="summary-row">
                <span>Subtotal (<?= getCartCount() ?> produk)</span>
                <span><?= formatRupiah($total) ?></span>
            </div>
            <div class="summary-row">
                <span>Ongkos Kirim</span>
                <span><?= $total >= 300000 ? '<span class="color-green">Gratis</span>' : formatRupiah(15000) ?></span>
            </div>
            <?php if ($total < 300000): ?>
            <p class="fs-xs color-muted mb-08">
                Belanja <?= formatRupiah(300000 - $total) ?> lagi untuk gratis ongkir!
            </p>
            <?php endif; ?>
            <div class="summary-row total">
                <span>Total</span>
                <span><?= formatRupiah($total + ($total >= 300000 ? 0 : 15000)) ?></span>
            </div>
            <a href="checkout.php" class="btn-primary btn-block mt-1" style="padding:1rem;">
                Lanjut ke Checkout →
            </a>
            <p class="secure-note">Pembayaran aman & terenkripsi</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
