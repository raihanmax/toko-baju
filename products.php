<?php
require_once 'includes/config.php';
$db = getDB();

// Filter & search
$category = clean($_GET['category'] ?? '');
$search   = clean($_GET['q'] ?? '');
$sort     = clean($_GET['sort'] ?? 'newest');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// Build query
$where = ['p.is_active = 1'];
$params = [];
$types  = '';

if ($category) {
    $where[] = 'c.slug = ?';
    $params[] = $category;
    $types .= 's';
}
if ($search) {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$orderBy = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
    default      => 'p.created_at DESC',
};

// Count total
$countSQL = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id $whereSQL";
$stmt = $db->prepare($countSQL);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $perPage);

// Fetch products
$productSQL = "SELECT p.*, c.name as cat_name, c.slug as cat_slug
               FROM products p
               LEFT JOIN categories c ON p.category_id = c.id
               $whereSQL
               ORDER BY $orderBy
               LIMIT ? OFFSET ?";
$stmt = $db->prepare($productSQL);
$allParams = $params;
$allParams[] = $perPage;
$allParams[] = $offset;
$allTypes = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$products = $stmt->get_result();

// Categories for sidebar
$cats = $db->query("SELECT *, (SELECT COUNT(*) FROM products WHERE category_id = categories.id AND is_active=1) as count FROM categories ORDER BY name");

$pageTitle = $search ? "Hasil: $search" : ($category ? ucfirst($category) : 'Semua Produk');
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <div class="container">
        <h1><?= $search ? "Hasil pencarian: \"$search\"" : ($category ? ucfirst($category) : 'Semua Produk') ?></h1>
        <p><?= $total ?> produk ditemukan</p>
    </div>
</div>

<div class="container">
    <!-- Search bar -->
    <form method="GET" style="margin-bottom:1.5rem; display:flex; gap:0.5rem; max-width:500px;">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
               class="form-control" placeholder="Cari produk..." style="flex:1">
        <?php if ($category): ?><input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>"><?php endif; ?>
        <button type="submit" class="btn-primary">Cari</button>
    </form>

    <div class="products-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h4>Kategori</h4>
            <ul>
                <li><a href="products.php" <?= !$category ? 'class="active"' : '' ?>>Semua Produk (<?= $db->query("SELECT COUNT(*) as n FROM products WHERE is_active=1")->fetch_assoc()['n'] ?>)</a></li>
                <?php while ($cat = $cats->fetch_assoc()): ?>
                <li>
                    <a href="products.php?category=<?= $cat['slug'] ?>"
                       <?= $category === $cat['slug'] ? 'class="active"' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?> (<?= $cat['count'] ?>)
                    </a>
                </li>
                <?php endwhile; ?>
            </ul>

            <hr style="margin: 1.2rem 0; border-color: #d4d4d4;">

            <h4>Urutkan</h4>
            <ul>
                <?php
                $sorts = ['newest' => 'Terbaru', 'price_asc' => 'Harga Terendah', 'price_desc' => 'Harga Tertinggi', 'name' => 'A-Z'];
                foreach ($sorts as $key => $label):
                    $url = '?' . http_build_query(array_merge($_GET, ['sort' => $key]));
                ?>
                <li><a href="<?= $url ?>" <?= $sort === $key ? 'class="active"' : '' ?>><?= $label ?></a></li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Products grid -->
        <div>
            <?php if ($products->num_rows > 0): ?>
            <div class="product-grid">
                <?php while ($p = $products->fetch_assoc()): ?>
                <a href="product.php?slug=<?= $p['slug'] ?>" class="product-card">
                    <div class="product-card-img">
                        <?php if ($p['image'] && file_exists(UPLOAD_DIR . $p['image'])): ?>
                            <img src="<?= UPLOAD_URL . $p['image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php else: ?>
                            <div class="img-placeholder">??</div>
                        <?php endif; ?>
                        <?php if ($p['stock'] < 5 && $p['stock'] > 0): ?>
                        <span class="product-badge" style="background:#c0392b">Hampir Habis</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-card-body">
                        <p class="product-card-category"><?= htmlspecialchars($p['cat_name'] ?? '') ?></p>
                        <h3 class="product-card-name"><?= htmlspecialchars($p['name']) ?></h3>
                        <p class="product-card-price"><?= formatRupiah($p['price']) ?></p>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++):
                    $url = '?' . http_build_query(array_merge($_GET, ['page' => $i]));
                ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $url ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="empty-state">
                <div class="icon">🔍</div>
                <h3>Produk tidak ditemukan</h3>
                <p>Coba kata kunci lain atau jelajahi kategori yang berbeda.</p>
                <a href="products.php" class="btn-primary">Lihat Semua Produk</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>