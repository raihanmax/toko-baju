<?php
$pageTitle      = 'Kelola Admin & Staff';
$superadminOnly = true;
require_once 'header.php';
$db = getDB();

// Handle tambah admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name    = $db->real_escape_string(clean($_POST['name'] ?? ''));
        $email   = $db->real_escape_string(clean($_POST['email'] ?? ''));
        $pass    = $_POST['password'] ?? '';
        $level   = in_array($_POST['level'] ?? '', ['superadmin','staff']) ? $_POST['level'] : 'staff';
        $jabatan = $db->real_escape_string(clean($_POST['jabatan'] ?? ''));

        if (!$name || !$email || !$pass) {
            setFlash('error', 'Nama, email, dan password wajib diisi.');
        } elseif (strlen($pass) < 6) {
            setFlash('error', 'Password minimal 6 karakter.');
        } else {
            $check = $db->query("SELECT id FROM admins WHERE email='$email'");
            if ($check->num_rows > 0) {
                setFlash('error', 'Email sudah terdaftar sebagai admin.');
            } else {
                $hashed = password_hash($pass, PASSWORD_DEFAULT);
                $db->query("INSERT INTO admins (name, email, password, level, jabatan) VALUES ('$name','$email','$hashed','$level','$jabatan')");
                setFlash('success', 'Admin/Staff baru berhasil ditambahkan!');
                redirect(SITE_URL . '/admin/admins.php');
            }
        }
    }

    if ($action === 'edit') {
        $id      = (int)$_POST['id'];
        $name    = $db->real_escape_string(clean($_POST['name'] ?? ''));
        $level   = in_array($_POST['level'] ?? '', ['superadmin','staff']) ? $_POST['level'] : 'staff';
        $jabatan = $db->real_escape_string(clean($_POST['jabatan'] ?? ''));

        if ($id === (int)$_SESSION['admin_id'] && $level !== 'superadmin') {
            setFlash('error', 'Tidak bisa menurunkan level diri sendiri.');
        } else {
            $db->query("UPDATE admins SET name='$name', level='$level', jabatan='$jabatan' WHERE id=$id");
            // Ganti password hanya jika diisi
            if (!empty($_POST['password'])) {
                $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $db->query("UPDATE admins SET password='$hashed' WHERE id=$id");
            }
            setFlash('success', 'Data admin berhasil diperbarui.');
            redirect(SITE_URL . '/admin/admins.php');
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === (int)$_SESSION['admin_id']) {
            setFlash('error', 'Tidak bisa menghapus akun sendiri.');
        } else {
            $db->query("DELETE FROM admins WHERE id=$id");
            setFlash('success', 'Admin/Staff berhasil dihapus.');
            redirect(SITE_URL . '/admin/admins.php');
        }
    }
}

// Edit mode
$editAdmin = null;
if (isset($_GET['edit'])) {
    $eid       = (int)$_GET['edit'];
    $editAdmin = $db->query("SELECT * FROM admins WHERE id=$eid")->fetch_assoc();
}

$admins = $db->query("SELECT * FROM admins ORDER BY level ASC, created_at ASC");
?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start;">

    <!-- Tabel admin -->
    <div class="table-card">
        <div class="table-header">
            <h3>Daftar Admin & Staff</h3>
            <a href="admins.php?add=1" class="btn btn-accent">+ Tambah Admin</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Level</th>
                    <th>Jabatan</th>
                    <th>Terdaftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($a = $admins->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="user-row">
                            <div class="user-avatar <?= $a['level'] === 'superadmin' ? 'admin-avatar' : '' ?>">
                                <?= strtoupper(substr($a['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <span class="fw-500"><?= htmlspecialchars($a['name']) ?></span>
                                <?php if ($a['id'] === (int)$_SESSION['admin_id']): ?>
                                <span style="font-size:0.7rem;background:#c8a96e22;color:#a88848;padding:1px 6px;border-radius:10px;margin-left:4px;">Anda</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="color-muted fs-sm"><?= htmlspecialchars($a['email']) ?></td>
                    <td>
                        <span class="badge <?= $a['level'] === 'superadmin' ? '' : 'badge-success' ?>"
                              style="<?= $a['level'] === 'superadmin' ? 'background:#c8a96e22;color:#a88848;' : '' ?>">
                            <?= strtoupper($a['level']) ?>
                        </span>
                    </td>
                    <td class="fs-sm"><?= htmlspecialchars($a['jabatan'] ?: '—') ?></td>
                    <td class="color-muted fs-sm"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                    <td>
                        <a href="?edit=<?= $a['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                        <?php if ($a['id'] !== (int)$_SESSION['admin_id']): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Hapus admin '<?= htmlspecialchars($a['name']) ?>'?">Hapus</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Form tambah / edit -->
    <?php if (isset($_GET['add']) || $editAdmin): ?>
    <div class="form-card sticky-admin">
        <h3><?= $editAdmin ? 'Edit Admin/Staff' : 'Tambah Admin/Staff Baru' ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editAdmin ? 'edit' : 'add' ?>">
            <?php if ($editAdmin): ?>
            <input type="hidden" name="id" value="<?= $editAdmin['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nama Lengkap *</label>
                <input type="text" name="name" class="form-control" required
                       value="<?= htmlspecialchars($editAdmin['name'] ?? '') ?>">
            </div>
            <?php if (!$editAdmin): ?>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" required placeholder="admin@email.com">
            </div>
            <?php else: ?>
            <div class="form-group">
                <label>Email (tidak bisa diubah)</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($editAdmin['email']) ?>" disabled style="opacity:0.5;">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label><?= $editAdmin ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password *' ?></label>
                <input type="password" name="password" class="form-control"
                       <?= $editAdmin ? '' : 'required' ?> placeholder="Minimal 6 karakter">
            </div>
            <div class="form-group">
                <label>Level *</label>
                <select name="level" class="form-control">
                    <option value="staff" <?= ($editAdmin['level'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="superadmin" <?= ($editAdmin['level'] ?? '') === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Jabatan</label>
                <input type="text" name="jabatan" class="form-control"
                       value="<?= htmlspecialchars($editAdmin['jabatan'] ?? '') ?>"
                       placeholder="Misal: Manajer Toko, Admin Produk">
            </div>
            <div class="d-flex gap-05">
                <button type="submit" class="btn btn-accent"><?= $editAdmin ? 'Simpan' : 'Tambah' ?></button>
                <a href="admins.php" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="form-card">
        <h3>ℹ️ Level Akses</h3>
        <div style="margin-bottom:1rem;">
            <p class="fw-500 fs-sm" style="color:#c8a96e;margin-bottom:4px;">👤 SUPERADMIN</p>
            <p class="fs-sm color-muted">Akses penuh — kelola produk, pesanan, kategori, customer, dan manajemen admin/staff.</p>
        </div>
        <div>
            <p class="fw-500 fs-sm" style="margin-bottom:4px;">👤 STAFF</p>
            <p class="fs-sm color-muted">Akses terbatas — hanya bisa kelola produk, pesanan, dan kategori. Tidak bisa akses manajemen pengguna.</p>
        </div>
        <a href="?add=1" class="btn btn-accent mt-1">+ Tambah Admin/Staff</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>