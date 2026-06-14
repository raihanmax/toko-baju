<?php
require_once 'includes/config.php';
$pageTitle = 'Checkout';
$db = getDB();

// Wajib login untuk checkout
if (!isLoggedIn()) {
    setFlash('info', 'Silakan masuk terlebih dahulu untuk melanjutkan checkout.');
    redirect(SITE_URL . '/login.php?redirect=/checkout.php');
}

$cart = getCart();
if (empty($cart)) {
    setFlash('error', 'Keranjang kamu kosong.');
    redirect(SITE_URL . '/cart.php');
}

$total      = getCartTotal();
$shipping   = $total >= 300000 ? 0 : 15000;
$grandTotal = $total + $shipping;

$user = null;
if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['customer_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = clean($_POST['shipping_name'] ?? '');
    $phone   = clean($_POST['shipping_phone'] ?? '');
    $address = clean($_POST['shipping_address'] ?? '');
    $city    = clean($_POST['shipping_city'] ?? '');
    $payment = in_array($_POST['payment_method'] ?? '', ['transfer','cod']) ? $_POST['payment_method'] : 'transfer';
    $notes   = clean($_POST['notes'] ?? '');

    if (!$name || !$phone || !$address || !$city) {
        setFlash('error', 'Mohon lengkapi semua data pengiriman.');
    } else {
        $orderNumber = generateOrderNumber();
        $userId      = $_SESSION['customer_id'] ?? null;

        $stmt = $db->prepare("INSERT INTO orders (customer_id, order_number, total_amount, shipping_name, shipping_phone, shipping_address, shipping_city, payment_method, notes) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('isdssssss', $userId, $orderNumber, $grandTotal, $name, $phone, $address, $city, $payment, $notes);
        $stmt->execute();
        $orderId = $db->insert_id;

        foreach ($cart as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $si = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, size, color, subtotal) VALUES (?,?,?,?,?,?,?,?)");
            $si->bind_param('iisdissd', $orderId, $item['id'], $item['name'], $item['price'], $item['quantity'], $item['size'], $item['color'], $subtotal);
            $si->execute();
            $safeQty = (int)$item['quantity'];
            $safeId  = (int)$item['id'];
            $db->query("UPDATE products SET stock = stock - $safeQty WHERE id = $safeId AND stock >= $safeQty");
        }

        $_SESSION['cart']       = [];
        $_SESSION['last_order'] = $orderNumber;
        redirect(SITE_URL . '/order-success.php');
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="grid-checkout">
        <!-- Form kiri -->
        <div>
            <h2 class="mb-15">Informasi Pengiriman</h2>

            <?php if (!isLoggedIn()): ?>
            <div class="flash flash-info mb-15">
                💡 <a href="login.php" class="fw-500">Masuk</a> untuk checkout lebih cepat dan pantau pesananmu.
            </div>
            <?php endif; ?>

            <form method="POST" id="checkoutForm">
                <div class="checkout-section">
                    <h3>Data Penerima</h3>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Nama Lengkap *</label>
                            <input type="text" name="shipping_name" class="form-control" required
                                   value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                                   placeholder="Nama penerima">
                        </div>
                        <div class="form-group">
                            <label>Nomor Telepon *</label>
                            <input type="tel" name="shipping_phone" class="form-control" required
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                   placeholder="08xx-xxxx-xxxx">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alamat Lengkap *</label>
                        <textarea name="shipping_address" class="form-control" required rows="3"
                                  placeholder="Jl. Nama Jalan No. XX, RT/RW, Kelurahan, Kecamatan"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Kota / Kabupaten *</label>
                        <input type="text" name="shipping_city" class="form-control" required
                               placeholder="Kota tujuan pengiriman">
                    </div>
                    <div class="form-group">
                        <label>Catatan (opsional)</label>
                        <input type="text" name="notes" class="form-control"
                               placeholder="Catatan untuk penjual atau kurir">
                    </div>
                </div>

                <div class="checkout-section">
                    <h3>Metode Pembayaran</h3>
                    <div class="d-flex flex-col gap-08">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="transfer" checked>
                            <div>
                                <p class="payment-option-title">Transfer Bank</p>
                                <p class="payment-option-sub">BCA · Mandiri · BNI · BRI</p>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod">
                            <div>
                                <p class="payment-option-title">COD (Bayar di Tempat)</p>
                                <p class="payment-option-sub">Bayar saat paket tiba</p>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-block" style="padding:1.1rem;font-size:1rem;">
                    Buat Pesanan — <?= formatRupiah($grandTotal) ?>
                </button>
            </form>
        </div>

        <!-- Ringkasan kanan -->
        <div>
            <div class="cart-summary sticky-nav">
                <h3>Pesananmu</h3>
                <?php foreach ($cart as $item): ?>
                <div class="cart-item-row">
                    <?php if ($item['image'] && file_exists(UPLOAD_DIR . $item['image'])): ?>
                        <img src="<?= UPLOAD_URL . $item['image'] ?>"
                             style="width:50px;height:50px;object-fit:cover;border-radius:4px;" alt="">
                    <?php else: ?>
                        <div class="cart-img-placeholder">👗</div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="cart-item-name"><?= htmlspecialchars($item['name']) ?></p>
                        <p class="cart-item-meta">
                            <?= $item['size'] ?>
                            <?= ($item['size'] && $item['color']) ? ' · ' : '' ?>
                            <?= $item['color'] ?>
                            × <?= $item['quantity'] ?>
                        </p>
                    </div>
                    <p class="cart-item-price"><?= formatRupiah($item['price'] * $item['quantity']) ?></p>
                </div>
                <?php endforeach; ?>

                <div class="summary-row">
                    <span>Subtotal</span><span><?= formatRupiah($total) ?></span>
                </div>
                <div class="summary-row">
                    <span>Ongkos Kirim</span>
                    <span><?= $shipping === 0 ? '<span class="color-green">Gratis</span>' : formatRupiah($shipping) ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span><span><?= formatRupiah($grandTotal) ?></span>
                </div>
                <p class="secure-note mt-1">🔒 Data kamu aman dan terenkripsi</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>