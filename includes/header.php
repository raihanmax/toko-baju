<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' — ' . SITE_NAME : SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="container nav-inner">
        <a href="<?= SITE_URL ?>" class="logo"><?= SITE_NAME ?><span>.</span></a>

        <ul class="nav-links">
            <li><a href="<?= SITE_URL ?>">Beranda</a></li>
            <li><a href="<?= SITE_URL ?>/products.php">Produk</a></li>
            <?php
            $db = getDB();
            $cats = $db->query("SELECT name, slug FROM categories ORDER BY name");
            while ($cat = $cats->fetch_assoc()):
            ?>
            <li><a href="<?= SITE_URL ?>/products.php?category=<?= $cat['slug'] ?>"><?= $cat['name'] ?></a></li>
            <?php endwhile; ?>
        </ul>

        <div class="nav-actions">
            <a href="<?= SITE_URL ?>/cart.php" class="cart-btn">
                🛒 Keranjang
                <?php $cartCount = getCartCount(); if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/account.php" class="btn-outline">Akun</a>
                <?php if (isAdminLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/admin/" class="btn-primary">Dashboard</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/logout.php" class="btn-ghost">Keluar</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn-outline">Masuk</a>
                <a href="<?= SITE_URL ?>/register.php" class="btn-primary">Daftar</a>
            <?php endif; ?>
        </div>

        <button class="mobile-menu-btn" onclick="toggleMenu()">☰</button>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="<?= SITE_URL ?>">Beranda</a>
        <a href="<?= SITE_URL ?>/products.php">Semua Produk</a>
        <a href="<?= SITE_URL ?>/cart.php">Keranjang (<?= getCartCount() ?>)</a>
        <?php if (isLoggedIn()): ?>
            <?php if (isAdminLoggedIn()): ?><a href="<?= SITE_URL ?>/admin/">Admin Dashboard</a><?php endif; ?>
            <a href="<?= SITE_URL ?>/logout.php">Keluar</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/login.php">Masuk</a>
            <a href="<?= SITE_URL ?>/register.php">Daftar</a>
        <?php endif; ?>
    </div>
</nav>

<!-- FLASH MESSAGE -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="flash flash-<?= $flash['type'] ?>">
    <?= htmlspecialchars($flash['message']) ?>
    <button onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>