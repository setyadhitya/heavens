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
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow-sm" style="width:400px">
    <div class="card-body">
      <h4 class="card-title text-center mb-3">Register</h4>

      <?php foreach($errors as $err): ?>
        <div class="alert alert-danger py-2"><?php echo e($err); ?></div>
      <?php endforeach; ?>

      <?php if($success): ?>
        <div class="alert alert-success py-2"><?php echo e($success); ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <div class="mb-2">
          <label class="form-label">Nama</label>
          <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="d-grid">
          <button class="btn btn-success">Daftar</button>
        </div>
      </form>

      <div class="mt-3 small text-center">
        <a href="index.php">Kembali ke login</a>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
