<?php
$pageTitle = 'Kelola Produk';
require_once 'header.php';
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("UPDATE products SET is_active = 0 WHERE id = $id");
        setFlash('success', 'Produk berhasil dihapus.');
        redirect(SITE_URL . '/admin/products.php');
    }

    if (in_array($action, ['add', 'edit'])) {
        $name     = $db->real_escape_string(clean($_POST['name'] ?? ''));
        $cat_id   = (int)($_POST['category_id'] ?? 0);
        $desc     = $db->real_escape_string(clean($_POST['description'] ?? ''));
        $price    = (float)($_POST['price'] ?? 0);
        $stock    = (int)($_POST['stock'] ?? 0);
        $sizes    = $db->real_escape_string(clean($_POST['sizes'] ?? ''));
        $colors   = $db->real_escape_string(clean($_POST['colors'] ?? ''));
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $slug     = $db->real_escape_string(makeSlug($_POST['name'] ?? ''));

        if (!$name || !$price) {
            setFlash('error', 'Nama dan harga wajib diisi.');
        } else {
            // Handle image upload
            $image = $db->real_escape_string($_POST['existing_image'] ?? '');
            if (!empty($_FILES['image']['name'])) {
                $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allowed)) {
                    $filename = $slug . '-' . time() . '.' . $ext;
                    $dest     = UPLOAD_DIR . $filename;
                    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                        $image = $db->real_escape_string($filename);
                    }
                }
            }

            if ($action === 'add') {
                $db->query("INSERT INTO products (category_id, name, slug, description, price, stock, image, sizes, colors, is_featured)
                            VALUES ($cat_id, '$name', '$slug', '$desc', $price, $stock, '$image', '$sizes', '$colors', $featured)");
                setFlash('success', 'Produk berhasil ditambahkan!');
            } else {
                $id = (int)$_POST['id'];
                $db->query("UPDATE products SET
                    category_id=$cat_id, name='$name', slug='$slug', description='$desc',
                    price=$price, stock=$stock, image='$image', sizes='$sizes',
                    colors='$colors', is_featured=$featured WHERE id=$id");
                setFlash('success', 'Produk berhasil diperbarui!');
            }
            redirect(SITE_URL . '/admin/products.php');
        }
    }
}

// Edit mode
$editProduct = null;
if (isset($_GET['edit'])) {
    $editId      = (int)$_GET['edit'];
    $editProduct = $db->query("SELECT * FROM products WHERE id = $editId")->fetch_assoc();
}

// List products
$search = clean($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 15;
$offset = ($page - 1) * $per;

$where = "WHERE p.is_active = 1";
if ($search) $where .= " AND p.name LIKE '%" . $db->real_escape_string($search) . "%'";

$total      = $db->query("SELECT COUNT(*) as n FROM products p $where")->fetch_assoc()['n'];
$products   = $db->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.created_at DESC LIMIT $per OFFSET $offset");
$totalPages = ceil($total / $per);
$categories = $db->query("SELECT * FROM categories ORDER BY name");
?>

<div style="display:grid;grid-template-columns:1fr <?= (isset($_GET['add']) || $editProduct) ? '380px' : '0' ?>;gap:1.5rem;align-items:start;">
    <!-- Products list -->
    <div>
        <div class="table-card">
            <div class="table-header">
                <h3>Daftar Produk (<?= $total ?>)</h3>
                <a href="products.php?add=1" class="btn btn-accent">+ Tambah Produk</a>
            </div>

            <div style="padding:0.8rem 1.2rem;border-bottom:1px solid var(--border);">
                <form method="GET" style="display:flex;gap:0.5rem;">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                           class="form-control" placeholder="Cari produk..." style="max-width:280px;">
                    <button type="submit" class="btn btn-outline">Cari</button>
                    <?php if ($search): ?>
                    <a href="products.php" class="btn btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Unggulan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $products->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:0.7rem;">
                                <?php if ($p['image'] && file_exists(UPLOAD_DIR . $p['image'])): ?>
                                    <img src="<?= UPLOAD_URL . $p['image'] ?>" alt=""
                                         style="width:36px;height:36px;object-fit:cover;border-radius:4px;">
                                <?php else: ?>
                                    <div style="width:36px;height:36px;background:#f0ece8;border-radius:4px;display:flex;align-items:center;justify-content:center;">???</div>
                                <?php endif; ?>
                                <span style="font-weight:500;font-size:0.85rem;"><?= htmlspecialchars($p['name']) ?></span>
                            </div>
                        </td>
                        <td style="color:#888;font-size:0.82rem;"><?= htmlspecialchars($p['cat_name'] ?? '—') ?></td>
                        <td><?= formatRupiah($p['price']) ?></td>
                        <td>
                            <span class="badge <?= $p['stock'] == 0 ? 'badge-danger' : ($p['stock'] <= 5 ? 'badge-warning' : 'badge-success') ?>">
                                <?= $p['stock'] ?>
                            </span>
                        </td>
                        <td><?= $p['is_featured'] ? '⭐' : '—' ?></td>
                        <td>
                            <a href="?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Hapus produk '<?= htmlspecialchars($p['name']) ?>'?">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
            <div style="padding:1rem 1.2rem;">
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++):
                        $url = '?' . http_build_query(array_merge($_GET, ['page' => $i]));
                    ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $url ?>"><?= $i ?></a>
                    <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add / Edit Form -->
    <?php if (isset($_GET['add']) || $editProduct): ?>
    <div class="form-card" style="position:sticky;top:70px;">
        <h3><?= $editProduct ? 'Edit Produk' : 'Tambah Produk Baru' ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $editProduct ? 'edit' : 'add' ?>">
            <?php if ($editProduct): ?>
            <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editProduct['image']) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nama Produk *</label>
                <input type="text" name="name" class="form-control" required
                       value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="category_id" class="form-control">
                    <option value="">-- Pilih Kategori --</option>
                    <?php $categories->data_seek(0); while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>"
                        <?= ($editProduct['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Harga (Rp) *</label>
                    <input type="number" name="price" class="form-control" required min="0"
                           value="<?= $editProduct['price'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stock" class="form-control" min="0"
                           value="<?= $editProduct['stock'] ?? 0 ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Ukuran (pisahkan dengan koma)</label>
                <input type="text" name="sizes" class="form-control"
                       value="<?= htmlspecialchars($editProduct['sizes'] ?? '') ?>"
                       placeholder="S,M,L,XL,XXL">
            </div>
            <div class="form-group">
                <label>Warna (pisahkan dengan koma)</label>
                <input type="text" name="colors" class="form-control"
                       value="<?= htmlspecialchars($editProduct['colors'] ?? '') ?>"
                       placeholder="Hitam,Putih,Navy">
            </div>
            <div class="form-group">
                <label>Foto Produk</label>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <?php if (!empty($editProduct['image'])): ?>
                <p style="font-size:0.78rem;color:#888;margin-top:0.3rem;">
                    Foto saat ini: <em><?= htmlspecialchars($editProduct['image']) ?></em>.
                    Upload baru untuk mengganti.
                </p>
                <?php endif; ?>
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:0.5rem;">
                <input type="checkbox" name="is_featured" id="featured"
                       <?= !empty($editProduct['is_featured']) ? 'checked' : '' ?>
                       style="width:auto;cursor:pointer;">
                <label for="featured" style="text-transform:none;font-size:0.88rem;letter-spacing:0;cursor:pointer;">
                    Tampilkan sebagai produk unggulan
                </label>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button type="submit" class="btn btn-accent">
                    <?= $editProduct ? 'Simpan Perubahan' : 'Tambah Produk' ?>
                </button>
                <a href="products.php" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>