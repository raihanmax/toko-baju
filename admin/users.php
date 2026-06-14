<?php
$pageTitle = 'Kelola Pengguna';
require_once 'header.php';
$db = getDB();

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)$_POST['id'];
    $role = in_array($_POST['role'], ['customer','admin']) ? $_POST['role'] : 'customer';
    if ($id !== (int)$_SESSION['user_id']) {
        $db->query("UPDATE users SET role='$role' WHERE id=$id");
        setFlash('success', 'Role pengguna diperbarui.');
    } else {
        setFlash('error', 'Tidak bisa mengubah role diri sendiri.');
    }
    redirect(SITE_URL . '/admin/users.php?tab=' . clean($_POST['current_tab'] ?? 'customer'));
}

$tab    = in_array(clean($_GET['tab'] ?? ''), ['admin','customer']) ? clean($_GET['tab']) : 'customer';
$search = clean($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 20;
$offset = ($page - 1) * $per;

$where = "WHERE role = '$tab'";
if ($search) $where .= " AND (name LIKE '%" . $db->real_escape_string($search) . "%' OR email LIKE '%" . $db->real_escape_string($search) . "%')";

$total      = $db->query("SELECT COUNT(*) as n FROM users $where")->fetch_assoc()['n'];
$users      = $db->query("SELECT u.*,
                (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
                (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status != 'cancelled') as total_spent
                FROM users u $where ORDER BY u.created_at DESC LIMIT $per OFFSET $offset");
$totalPages = ceil($total / $per);

$countAdmin    = $db->query("SELECT COUNT(*) as n FROM users WHERE role='admin'")->fetch_assoc()['n'];
$countCustomer = $db->query("SELECT COUNT(*) as n FROM users WHERE role='customer'")->fetch_assoc()['n'];
?>

<!-- Tab switch -->
<div class="status-tabs">
    <a href="?tab=customer<?= $search ? '&q='.urlencode($search) : '' ?>"
       class="btn btn-sm <?= $tab === 'customer' ? 'btn-primary' : 'btn-outline' ?>">
        👤 Customer (<?= $countCustomer ?>)
    </a>
    <a href="?tab=admin<?= $search ? '&q='.urlencode($search) : '' ?>"
       class="btn btn-sm <?= $tab === 'admin' ? 'btn-primary' : 'btn-outline' ?>">
        ⚙️ Admin (<?= $countAdmin ?>)
    </a>
</div>

<div class="table-card">
    <div class="table-header">
        <h3><?= $tab === 'admin' ? 'Daftar Admin' : 'Daftar Customer' ?> (<?= $total ?>)</h3>
        <form method="GET" class="d-flex gap-05">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   class="form-control" placeholder="Cari nama / email..." style="width:240px;">
            <button type="submit" class="btn btn-outline">Cari</button>
            <?php if ($search): ?>
            <a href="?tab=<?= $tab ?>" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Telepon</th>
                <?php if ($tab === 'customer'): ?>
                <th>Total Pesanan</th>
                <th>Total Belanja</th>
                <?php endif; ?>
                <th>Terdaftar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($users->num_rows > 0): ?>
            <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
                <td>
                    <div class="user-row">
                        <div class="user-avatar <?= $u['role'] === 'admin' ? 'admin-avatar' : '' ?>">
                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                        </div>
                        <span class="fw-500"><?= htmlspecialchars($u['name']) ?></span>
                        <?php if ($u['id'] === (int)$_SESSION['user_id']): ?>
                        <span style="font-size:0.7rem;background:#c8a96e22;color:#a88848;padding:1px 6px;border-radius:10px;">Anda</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="color-muted fs-sm"><?= htmlspecialchars($u['email']) ?></td>
                <td class="color-muted fs-sm"><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                <?php if ($tab === 'customer'): ?>
                <td class="text-center"><?= $u['order_count'] ?></td>
                <td><?= $u['total_spent'] ? formatRupiah($u['total_spent']) : '—' ?></td>
                <?php endif; ?>
                <td class="color-muted fs-sm"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                    <form method="POST" class="role-form">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="current_tab" value="<?= $tab ?>">
                        <select name="role" class="form-control role-select">
                            <option value="customer" <?= $u['role']==='customer'?'selected':'' ?>>Customer</option>
                            <option value="admin"    <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                        </select>
                        <button type="submit" class="btn btn-outline btn-sm"
                                data-confirm="Ubah role pengguna ini?">Ubah</button>
                    </form>
                    <?php else: ?>
                    <span class="fs-xs color-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
                <td colspan="<?= $tab === 'customer' ? 7 : 5 ?>" class="text-center color-muted" style="padding:2.5rem;">
                    <?= $search ? 'Tidak ada hasil untuk "' . htmlspecialchars($search) . '"' : 'Belum ada ' . ($tab === 'admin' ? 'admin' : 'customer') . ' terdaftar.' ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.2rem;">
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++):
                $url = '?' . http_build_query(array_merge($_GET, ['page' => $i])); ?>
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

<?php require_once 'footer.php'; ?>
