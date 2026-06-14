<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect(SITE_URL);
$pageTitle = 'Masuk';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Cek di tabel customers
    $stmt = $db->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();

    if ($customer && password_verify($password, $customer['password'])) {
        $_SESSION['customer_id']   = $customer['id'];
        $_SESSION['customer_name'] = $customer['name'];
        setFlash('success', 'Selamat datang kembali, ' . $customer['name'] . '!');
        $redirect = clean($_GET['redirect'] ?? '');
        redirect($redirect ? SITE_URL . $redirect : SITE_URL);
    } else {
        setFlash('error', 'Email atau password salah.');
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="auth-page">
    <div class="auth-card">
        <h2>Masuk</h2>
        <p class="auth-subtitle">Selamat datang kembali di <?= SITE_NAME ?></p>

        <form method="POST">
            <div class="form-group">
                <label>Alamat Email</label>
                <input type="email" name="email" class="form-control" required
                       placeholder="kamu@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-primary btn-block" style="padding:0.9rem;">Masuk</button>
        </form>

        <p class="auth-footer">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
        <p class="auth-footer" style="margin-top:0.5rem;">
            <a href="admin/login.php" style="color:#888;font-size:0.8rem;">Login sebagai Admin →</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>