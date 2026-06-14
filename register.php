<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect(SITE_URL);
$pageTitle = 'Daftar';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = clean($_POST['name'] ?? '');
    $email   = clean($_POST['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$pass) {
        setFlash('error', 'Semua field wajib diisi.');
    } elseif (strlen($pass) < 6) {
        setFlash('error', 'Password minimal 6 karakter.');
    } elseif ($pass !== $confirm) {
        setFlash('error', 'Konfirmasi password tidak cocok.');
    } else {
        // Cek email di tabel customers
        $stmt = $db->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            setFlash('error', 'Email sudah terdaftar.');
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt   = $db->prepare("INSERT INTO customers (name, email, password) VALUES (?,?,?)");
            $stmt->bind_param('sss', $name, $email, $hashed);
            $stmt->execute();
            $_SESSION['customer_id']   = $db->insert_id;
            $_SESSION['customer_name'] = $name;
            setFlash('success', 'Akun berhasil dibuat! Selamat berbelanja, ' . $name . '!');
            redirect(SITE_URL);
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="auth-page">
    <div class="auth-card">
        <h2>Buat Akun</h2>
        <p class="auth-subtitle">Bergabung dengan komunitas <?= SITE_NAME ?></p>

        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="name" class="form-control" required
                       placeholder="Nama kamu"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Alamat Email</label>
                <input type="email" name="email" class="form-control" required
                       placeholder="kamu@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Ulangi password">
            </div>
            <button type="submit" class="btn-primary btn-block" style="padding:0.9rem;">Buat Akun</button>
        </form>

        <p class="auth-footer">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>