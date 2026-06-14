<?php
$pageTitle    = 'Kelola Customer';
$superadminOnly = true;
require_once 'header.php';
$db = getDB();

$search = clean($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 20;
$offset = ($page - 1) * $per;

$where = "WHERE 1=1";
if ($search) $where .= " AND (name LIKE '%" . $db->real_escape_string($search) . "%' OR email LIKE '%" . $db->real_escape_string($search) . "%')";

$total      = $db->query("SELECT COUNT(*) as n FROM customers $where")->fetch_assoc()['n'];
$customers  = $db->query("SELECT c.*,
                (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) as order_count,
                (SELECT SUM(total_amount) FROM orders WHERE customer_id = c.id AND status != 'cancelled') as total_spent
                FROM customers c $where ORDER BY c.created_at DESC LIMIT $per OFFSET $offset");
$totalPages = ceil($total / $per);
?>

<div class="table-card">
    <div class="table-header">
        <h3>Daftar Customer (<?= $total ?>)</h3>
        <form method="GET" class="d-flex gap-05">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   class="form-control" placeholder="Cari nama / email..." style="width:240px;">
            <button type="submit" class="btn btn-outline">Cari</button>
            <?php if ($search): ?>
            <a href="customers.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Telepon</th>
                <th>Total Pesanan</th>
                <th>Total Belanja</th>
                <th>Terdaftar</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($customers->num_rows > 0): ?>
            <?php while ($c = $customers->fetch_assoc()): ?>
            <tr>
                <td>
                    <div class="user-row">
                        <div class="user-avatar"><?= strtoupper(substr($c['name'], 0, 1)) ?></div>
                        <span class="fw-500"><?= htmlspecialchars($c['name']) ?></span>
                    </div>
                </td>
                <td class="color-muted fs-sm"><?= htmlspecialchars($c['email']) ?></td>
                <td class="color-muted fs-sm"><?= htmlspecialchars($c['phone'] ?: '—') ?></td>
                <td class="text-center"><?= $c['order_count'] ?></td>
                <td><?= $c['total_spent'] ? formatRupiah($c['total_spent']) : '—' ?></td>
                <td class="color-muted fs-sm"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="6" class="text-center color-muted" style="padding:2rem;">Belum ada customer terdaftar.</td></tr>
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

<?php require_once 'footer.php'; ?>