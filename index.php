<?php
require_once __DIR__ . '/functions.php';

$errors = [];
if (too_many_failed_logins()) {
    $errors[] = 'Terlalu banyak percobaan login. Coba lagi nanti.';
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token invalid.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $mysqli->prepare('SELECT id, password_hash, nama FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                // success
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_nama'] = $row['nama'];
                $_SESSION['last_activity'] = time();
                // reset failed attempts
                if (isset($_SESSION['failed_login'])) unset($_SESSION['failed_login']);
                header('Location: dashboard.php');
                exit;
            } else {
                record_failed_login();
                $errors[] = 'Username atau password salah.';
            }
        } else {
            record_failed_login();
            $errors[] = 'Username atau password salah.';
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title></head>
<body>
<h2>Login</h2>
<?php foreach($errors as $err): ?>
    <div style="color:red"><?php echo e($err); ?></div>
<?php endforeach; ?>
<form method="post" action="">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <label>Username:<br><input type="text" name="username" required></label><br>
    <label>Password:<br><input type="password" name="password" required></label><br>
    <button type="submit">Login</button>
</form>
<p><a href="register.php">Register (use once)</a></p>
</body>
</html>
