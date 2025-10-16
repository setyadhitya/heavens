<?php
require_once __DIR__ . '/functions.php';

// jika sudah login, langsung ke dashboard atau ke redirect_to (jika ada)
if (is_logged_in()) {
    // jika ada redirect_to (mis. user kembali akses page lain), pakai itu, else ke dashboard
    $to = $_SESSION['redirect_to'] ?? 'dashboard.php';
    unset($_SESSION['redirect_to']);
    safe_redirect($to);
}

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
                if (isset($_SESSION['failed_login'])) unset($_SESSION['failed_login']);

                // redirect ke halaman semula kalau ada, gunakan safe redirect
                $redirect = $_SESSION['redirect_to'] ?? 'dashboard.php';
                unset($_SESSION['redirect_to']);
                safe_redirect($redirect);
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

// mulai render HTML dengan Bootstrap
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow-sm" style="width: 380px;">
    <div class="card-body">
      <h4 class="card-title text-center mb-3">Masuk</h4>

      <?php if(!empty($errors)): ?>
        <?php foreach($errors as $err): ?>
          <div class="alert alert-danger py-2" role="alert"><?php echo e($err); ?></div>
        <?php endforeach; ?>
      <?php endif; ?>

      <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="d-grid">
          <button class="btn btn-primary" type="submit">Masuk</button>
        </div>
      </form>

      <div class="mt-3 text-center small">
        <a href="register.php">Daftar (sekali pakai)</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
