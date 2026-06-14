<?php
require_once 'includes/config.php';
$pageTitle = 'Pesanan Berhasil';
$orderNumber = $_SESSION['last_order'] ?? null;
if (!$orderNumber) redirect(SITE_URL);
?>
<?php include 'includes/header.php'; ?>

<div class="container order-success-card">
    <div class="order-success-icon">🎉</div>
    <h1>Pesanan Diterima!</h1>
    <p class="color-muted mt-1 mw-480" style="margin:0.5rem auto 2rem;">
        Terima kasih sudah berbelanja di <?= SITE_NAME ?>. Pesananmu sedang diproses dan akan segera dikirim.
    </p>
    <div class="order-success-number-box">
        <p class="order-success-label">Nomor Pesanan</p>
        <p class="order-success-number"><?= htmlspecialchars($orderNumber) ?></p>
    </div>
    <p class="fs-sm color-muted mb-2">Simpan nomor pesanan ini untuk melacak status pengirimanmu.</p>
    <div class="order-success-actions">
        <?php if (isLoggedIn()): ?>
        <a href="account.php" class="btn-outline">Lihat Pesanan</a>
        <?php endif; ?>
        <a href="products.php" class="btn-primary">Lanjut Belanja</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>