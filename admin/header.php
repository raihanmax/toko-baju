<?php
require_once '../includes/config.php';

// Guard: harus login sebagai admin
if (!isAdminLoggedIn()) {
    setFlash('error', 'Silakan login sebagai admin terlebih dahulu.');
    redirect(SITE_URL . '/admin/login.php');
}

// Guard: halaman tertentu hanya untuk superadmin
if (isset($superadminOnly) && $superadminOnly && !isSuperAdmin()) {
    setFlash('error', 'Halaman ini hanya untuk Superadmin.');
    redirect(SITE_URL . '/admin/');
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' — Admin ' . SITE_NAME : 'Admin ' . SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo"><?= SITE_NAME ?><span>.</span></div>
        <small>Admin Panel</small>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Menu Utama</div>
        <a href="index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">
            <span class="icon">Dashboard</span> 
        </a>
        <a href="products.php" class="<?= $currentPage === 'products' ? 'active' : '' ?>">
            <span class="icon">Produk</span> 
        </a>
        <a href="orders.php" class="<?= $currentPage === 'orders' ? 'active' : '' ?>">
            <span class="icon">Pesanan</span> 
        </a>
        <a href="categories.php" class="<?= $currentPage === 'categories' ? 'active' : '' ?>">
            <span class="icon">Kategori</span> 
        </a>

        <!-- Menu khusus superadmin -->
        <?php if (isSuperAdmin()): ?>
        <div class="nav-section" style="margin-top:1rem;">Manajemen</div>
        <a href="customers.php" class="<?= $currentPage === 'customers' ? 'active' : '' ?>">
            <span class="icon">👤</span> Customer
        </a>
        <a href="admins.php" class="<?= $currentPage === 'admins' ? 'active' : '' ?>">
            <span class="icon">⚙️</span> Admin & Staff
        </a>
        <?php endif; ?>

        <div class="nav-section" style="margin-top:1rem;">Lainnya</div>
        <a href="<?= SITE_URL ?>" target="_blank">
            <span class="icon">🌐</span> Lihat Toko
        </a>
    </nav>

    <div class="sidebar-footer">
        <!-- Info admin yang login -->
        <div style="margin-bottom:0.8rem;padding-bottom:0.8rem;border-bottom:1px solid #222;">
            <p style="font-size:0.82rem;color:#ccc;font-weight:500;"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
            <p style="font-size:0.72rem;color:#666;margin-top:2px;">
                <?= $_SESSION['admin_jabatan'] ?? ucfirst($_SESSION['admin_level']) ?>
                <span style="background:<?= isSuperAdmin() ? '#c8a96e33' : '#33333' ?>;color:<?= isSuperAdmin() ? '#c8a96e' : '#888' ?>;padding:1px 6px;border-radius:10px;font-size:0.68rem;margin-left:4px;">
                    <?= strtoupper($_SESSION['admin_level']) ?>
                </span>
            </p>
        </div>
        <a href="logout.php">
            <span>↩</span> Keluar
        </a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="topbar">
        <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
        <div class="topbar-right">
            <div class="user-pill">
                <span><?= isSuperAdmin() ? '👤' : '👤' ?></span>
                <span><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
                <span style="font-size:0.72rem;color:#aaa;">(<?= ucfirst($_SESSION['admin_level']) ?>)</span>
            </div>
        </div>
    </div>

    <div class="page-content">
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] ?>">
        <?= htmlspecialchars($flash['message']) ?>
        <button onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>