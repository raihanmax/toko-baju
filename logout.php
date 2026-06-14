<?php
require_once 'includes/config.php';

// Hapus session customer
unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);
unset($_SESSION['cart']);

setFlash('info', 'Kamu berhasil keluar.');
redirect(SITE_URL . '/login.php');