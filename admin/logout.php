<?php
require_once '../includes/config.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_level']);
unset($_SESSION['admin_jabatan']);

redirect(SITE_URL . '/admin/login.php');