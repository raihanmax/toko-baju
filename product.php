<?php
require_once 'includes/config.php';
$db = getDB();

$slug = clean($_GET['slug'] ?? '');
if (!$slug) { redirect(SITE_URL . '/products.php'); }

$stmt = $db->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND p.is_active = 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    setFlash('error', 'Produk tidak ditemukan.');
    redirect(SITE_URL . '/products.php');
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $size  = clean($_POST['size'] ?? '');
    $color = clean($_POST['color'] ?? '');
    $qty   = max(1, (int)($_POST['quantity'] ?? 1));

    if ($product['sizes'] && !$size) {
        setFlash('error', 'Pilih ukuran terlebih dahulu.');
        redirect(SITE_URL . '/product.php?slug=' . $slug);
    }
    if ($product['colors'] && !$color) {
        setFlash('error', 'Pilih warna terlebih dahulu.');
        redirect(SITE_URL . '/product.php?slug=' . $slug);
    }

    $cartKey = $product['id'] . '_' . $size . '_' . $color;
    $cart    = getCart();

    if (isset($cart[$cartKey])) {
        $cart[$cartKey]['quantity'] += $qty;
    } else {
        $cart[$cartKey] = [
            'id'       => $product['id'],
            'name'     => $product['name'],
            'price'    => $product['price'],
            'image'    => $product['image'],
            'size'     => $size,
            'color'    => $color,
            'quantity' => $qty,
            'slug'     => $product['slug'],
        ];
    }
    $_SESSION['cart'] = $cart;
    setFlash('success', '✓ Produk ditambahkan ke keranjang!');
    redirect(SITE_URL . '/product.php?slug=' . $slug);
}

$sizes  = $product['sizes']  ? explode(',', $product['sizes'])  : [];
$colors = $product['colors'] ? explode(',', $product['colors']) : [];

$related = $db->prepare("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 LIMIT 4");
$related->bind_param('ii', $product['category_id'], $product['id']);
$related->execute();
$relatedProducts = $related->get_result();

$pageTitle = $product['name'];
?>
<?php include 'includes/header.php'; ?>

<div class="container product-detail">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Beranda</a>
        <span class="sep">/</span>
        <a href="<?= SITE_URL ?>/products.php">Produk</a>
        <?php if ($product['cat_name']): ?>
        <span class="sep">/</span>
        <a href="<?= SITE_URL ?>/products.php?category=<?= $product['cat_slug'] ?>"><?= htmlspecialchars($product['cat_name']) ?></a>
        <?php endif; ?>
        <span class="sep">/</span>
        <span><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div class="product-detail-grid">
        <!-- Image -->
        <div>
            <?php if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])): ?>
                <img src="<?= UPLOAD_URL . $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>"
                     class="w-100" style="height:500px;object-fit:cover;border-radius:8px;">
            <?php else: ?>
                <div class="product-img-placeholder">?</div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div>
            <p class="product-detail-category"><?= htmlspecialchars($product['cat_name'] ?? '') ?></p>
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            <p class="product-price-lg"><?= formatRupiah($product['price']) ?></p>

            <?php if ($product['description']): ?>
            <p class="color-dark fs-md mb-15" style="line-height:1.8;">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>
            <?php endif; ?>

            <p class="stock-info">
                <?php if ($product['stock'] > 10): ?>
                    ✅ Stok tersedia (<?= $product['stock'] ?> pcs)
                <?php elseif ($product['stock'] > 0): ?>
                    ⚠️ Stok terbatas (sisa <?= $product['stock'] ?> pcs)
                <?php else: ?>
                    ❌ Stok habis
                <?php endif; ?>
            </p>

            <?php if ($product['stock'] > 0): ?>
            <form method="POST">
                <input type="hidden" name="add_to_cart" value="1">

                <?php if ($sizes): ?>
                <div class="mb-12">
                    <p class="variant-label">
                        Ukuran <span id="size-error" style="color:#c0392b;font-size:0.78rem;display:none;">* wajib dipilih</span>
                    </p>
                    <div class="variant-options">
                        <?php foreach ($sizes as $s): ?>
                        <button type="button" class="variant-btn"
                                onclick="selectVariant(this, 'selected_size', 'size-error')"
                                data-value="<?= trim($s) ?>">
                            <?= trim($s) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="size" id="selected_size">
                </div>
                <?php endif; ?>

                <?php if ($colors): ?>
                <div class="mb-12">
                    <p class="variant-label">
                        Warna <span id="color-error" style="color:#c0392b;font-size:0.78rem;display:none;">* wajib dipilih</span>
                    </p>
                    <div class="variant-options">
                        <?php foreach ($colors as $c): ?>
                        <button type="button" class="variant-btn"
                                onclick="selectVariant(this, 'selected_color', 'color-error')"
                                data-value="<?= trim($c) ?>">
                            <?= trim($c) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="color" id="selected_color">
                </div>
                <?php endif; ?>

                <div class="mb-15">
                    <p class="variant-label">Jumlah</p>
                    <div class="qty-control">
                        <button type="button" onclick="changeQty('dec','main')">−</button>
                        <input type="number" name="quantity" id="qty_main" value="1"
                               min="1" max="<?= $product['stock'] ?>"
                               class="qty-control input">
                        <button type="button" onclick="changeQty('inc','main')">+</button>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-block" style="padding:1rem;"
                        onclick="return validateVariants(<?= $sizes ? 'true' : 'false' ?>, <?= $colors ? 'true' : 'false' ?>)">
                    Tambah ke Keranjang 🛒
                </button>
            </form>
            <?php else: ?>
            <button class="btn-outline btn-block" disabled style="opacity:0.4;cursor:not-allowed;padding:1rem;">
                Stok Habis
            </button>
            <?php endif; ?>

            <div class="info-pills">
                <span class="info-pill">-</span>
                <span class="info-pill">-</span>
                <span class="info-pill">✅ Produk original</span>
            </div>
        </div>
    </div>

    <!-- Related products -->
    <?php if ($relatedProducts->num_rows > 0): ?>
    <div class="related-section">
        <div class="section-header">
            <div>
                <p class="section-tag">Mungkin Kamu Suka</p>
                <h2>Produk Serupa</h2>
            </div>
        </div>
        <div class="product-grid">
            <?php while ($rp = $relatedProducts->fetch_assoc()): ?>
            <a href="product.php?slug=<?= $rp['slug'] ?>" class="product-card">
                <div class="product-card-img">
                    <?php if ($rp['image'] && file_exists(UPLOAD_DIR . $rp['image'])): ?>
                        <img src="<?= UPLOAD_URL . $rp['image'] ?>" alt="<?= htmlspecialchars($rp['name']) ?>">
                    <?php else: ?>
                        <div class="img-placeholder">??</div>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <p class="product-card-category"><?= htmlspecialchars($rp['cat_name'] ?? '') ?></p>
                    <h3 class="product-card-name"><?= htmlspecialchars($rp['name']) ?></h3>
                    <p class="product-card-price"><?= formatRupiah($rp['price']) ?></p>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>