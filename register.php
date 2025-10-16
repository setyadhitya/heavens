<?php
require_once __DIR__ . '/functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token invalid.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $nama = trim($_POST['nama'] ?? '');

        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3-30 characters (letters, numbers, underscore).';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if (empty($errors)) {
            // check existing
            $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = 'Username already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare('INSERT INTO users (username, password_hash, nama, created_at) VALUES (?, ?, ?, NOW())');
                $stmt->bind_param('sss', $username, $hash, $nama);
                if ($stmt->execute()) {
                    $success = 'User registered. You can now login.';
                } else {
                    $errors[] = 'Register failed. ' . $mysqli->error;
                }
            }
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Register</title></head>
<body>
<h2>Register (use once then remove)</h2>
<?php foreach($errors as $err): ?>
    <div style="color:red"><?php echo e($err); ?></div>
<?php endforeach; ?>
<?php if($success): ?>
    <div style="color:green"><?php echo e($success); ?></div>
<?php endif; ?>
<form method="post" action="">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <label>Nama:<br><input type="text" name="nama" required></label><br>
    <label>Username:<br><input type="text" name="username" required></label><br>
    <label>Password:<br><input type="password" name="password" required></label><br>
    <button type="submit">Register</button>
</form>
<p><a href="index.php">Back to Login</a></p>
</body>
</html>
