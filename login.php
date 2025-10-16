<?php
require_once __DIR__ . '/functions.php';

// Jika sudah login, jangan balik ke login lagi
if (is_logged_in()) {
  header("Location: index.php");
  exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    $errors[] = "CSRF token invalid.";
  } else {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT id, password_hash, nama FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
      if (password_verify($password, $row['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['user_nama'] = $row['nama'];
    $_SESSION['last_activity'] = time();

    // 🔄 Jika ada redirect_to, arahkan ke sana
    if (!empty($_SESSION['redirect_to'])) {
        $redirect = $_SESSION['redirect_to'];
        unset($_SESSION['redirect_to']);
        header("Location: " . $redirect);
    } else {
        header("Location: /heavens/index.php");
    }
    exit;
}

    }
    $errors[] = "Username atau password salah.";
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow" style="width:350px">
      <div class="card-body">
        <h4 class="text-center mb-3">Login</h4>
        <?php foreach($errors as $err): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($err); ?></div>
        <?php endforeach; ?>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">Masuk</button>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
