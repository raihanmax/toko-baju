<?php
require_once '../includes/config.php';
if (isAdminLoggedIn()) redirect(SITE_URL . '/admin/');
$pageTitle = 'Admin Login';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']      = $admin['id'];
        $_SESSION['admin_name']    = $admin['name'];
        $_SESSION['admin_level']   = $admin['level'];
        $_SESSION['admin_jabatan'] = $admin['jabatan'];
        setFlash('success', 'Selamat datang, ' . $admin['name'] . '!');
        redirect(SITE_URL . '/admin/');
    } else {
        setFlash('error', 'Email atau password admin salah.');
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
    <style>
        body { background: #f0ece8; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .login-wrap { width: 100%; max-width: 420px; padding: 1rem; }
        .login-card { background: #fff; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .login-logo { text-align: center; margin-bottom: 1.5rem; }
        .login-logo .logo { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 600; color: #0a0a0a; letter-spacing: 0.06em; }
        .login-logo .logo span { color: #c8a96e; }
        .login-logo small { display:block; font-size:0.72rem; letter-spacing:0.15em; text-transform:uppercase; color:#aaa; margin-top:4px; }
        .login-title { font-size:1.1rem; font-weight:500; color:#1a1a1a; margin-bottom:0.3rem; }
        .login-sub { font-size:0.84rem; color:#888; margin-bottom:1.5rem; }
        .back-link { text-align:center; margin-top:1.2rem; font-size:0.82rem; color:#aaa; }
        .back-link a { color:#888; }
        .back-link a:hover { color:#333; }
    </style>
</head>
<body>
<div class="login-wrap">
    <?php if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] ?>" style="margin-bottom:1rem;">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <div class="login-card">
        <div class="login-logo">
            <div class="logo"><?= SITE_NAME ?><span>.</span></div>
            <small>Admin Panel</small>
        </div>

        <p class="login-title">Masuk sebagai Admin</p>
        <p class="login-sub">Hanya untuk superadmin dan staff toko.</p>

        <form method="POST">
            <div class="form-group">
                <label>Email Admin</label>
                <input type="email" name="email" class="form-control" required
                       placeholder="admin@fashionstore.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%;padding:0.75rem;margin-top:0.5rem;">
                Masuk ke Admin Panel
            </button>
        </form>

        <div class="back-link">
            <a href="<?= SITE_URL ?>">← Kembali ke Toko</a>
        </div>

        <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid #f0ece8;font-size:0.75rem;color:#bbb;text-align:center;">
            Superadmin: admin@fashionstore.com / admin123<br>
            Staff: staff@fashionstore.com / admin123
        </div>
    </div>
</div>
</body>
</html>