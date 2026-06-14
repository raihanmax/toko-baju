<?php
$pageTitle = 'Kelola Kategori';
require_once 'header.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id    = (int)$_POST['id'];
        $count = $db->query("SELECT COUNT(*) as n FROM products WHERE category_id = $id AND is_active = 1")->fetch_assoc()['n'];
        if ($count > 0) {
            setFlash('error', "Tidak bisa menghapus kategori yang masih memiliki $count produk aktif.");
        } else {
            $db->query("DELETE FROM categories WHERE id = $id");
            setFlash('success', 'Kategori berhasil dihapus.');
        }
        redirect(SITE_URL . '/admin/categories.php');
    }

    if (in_array($action, ['add','edit'])) {
        $name = $db->real_escape_string(clean($_POST['name'] ?? ''));
        $desc = $db->real_escape_string(clean($_POST['description'] ?? ''));
        $slug = $db->real_escape_string(makeSlug($_POST['name'] ?? ''));

        if (!$name) {
            setFlash('error', 'Nama kategori wajib diisi.');
        } else {
            if ($action === 'add') {
                $db->query("INSERT INTO categories (name, slug, description) VALUES ('$name', '$slug', '$desc')");
                setFlash('success', 'Kategori berhasil ditambahkan!');
            } else {
                $id = (int)$_POST['id'];
                $db->query("UPDATE categories SET name='$name', slug='$slug', description='$desc' WHERE id=$id");
                setFlash('success', 'Kategori berhasil diperbarui!');
            }
            redirect(SITE_URL . '/admin/categories.php');
        }
    }
}

$editCat    = null;
if (isset($_GET['edit'])) {
    $editId  = (int)$_GET['edit'];
    $editCat = $db->query("SELECT * FROM categories WHERE id = $editId")->fetch_assoc();
}

$categories = $db->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active=1) as product_count FROM categories c ORDER BY c.name");
?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start;">
    <div class="table-card">
        <div class="table-header">
            <h3>Daftar Kategori</h3>
            <a href="categories.php?add=1" class="btn btn-accent">+ Tambah Kategori</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nama Kategori</th>
                    <th>Slug</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Produk</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categories->num_rows > 0): ?>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                <tr>
                    <td class="fw-500"><?= htmlspecialchars($cat['name']) ?></td>
                    <td><code style="font-size:0.78rem;background:#f0ece8;padding:0.1rem 0.4rem;border-radius:3px;"><?= $cat['slug'] ?></code></td>
                    <td class="color-muted fs-sm"><?= htmlspecialchars(substr($cat['description'] ?: '—', 0, 50)) ?></td>
                    <td class="text-center">
                        <span class="color-blue fw-500"><?= $cat['product_count'] ?></span>
                    </td>
                    <td>
                        <a href="?edit=<?= $cat['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Hapus kategori '<?= htmlspecialchars($cat['name']) ?>'?">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr><td colspan="5" class="text-center color-muted" style="padding:2rem;">Belum ada kategori.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($_GET['add']) || $editCat): ?>
    <div class="form-card sticky-admin">
        <h3><?= $editCat ? 'Edit Kategori' : 'Tambah Kategori' ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editCat ? 'edit' : 'add' ?>">
            <?php if ($editCat): ?>
            <input type="hidden" name="id" value="<?= $editCat['id'] ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Nama Kategori *</label>
                <input type="text" name="name" class="form-control" required
                       value="<?= htmlspecialchars($editCat['name'] ?? '') ?>"
                       placeholder="Misal: Atasan, Bawahan, Dress">
            </div>
            <div class="form-group">
                <label>Deskripsi (opsional)</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Deskripsi singkat kategori"><?= htmlspecialchars($editCat['description'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-05">
                <button type="submit" class="btn btn-accent"><?= $editCat ? 'Simpan' : 'Tambah' ?></button>
                <a href="categories.php" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="form-card">
        <h3>ℹ️ Tentang Kategori</h3>
        <p class="cat-info-card">
            Kategori digunakan untuk mengelompokkan produk. Setiap produk hanya bisa memiliki satu kategori.
        </p>
        <ul class="cat-info-list">
            <li>Slug otomatis dibuat dari nama</li>
            <li>Kategori dengan produk aktif tidak bisa dihapus</li>
            <li>Slug digunakan untuk URL filter produk</li>
        </ul>
        <a href="?add=1" class="btn btn-accent mt-1">+ Tambah Kategori</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
