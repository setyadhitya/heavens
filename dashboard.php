<?php
require_once __DIR__ . '/functions.php';
require_login_and_redirect();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Heavens</a>
    <div class="d-flex">
      <span class="navbar-text text-white me-2">Halo, <?php echo e($_SESSION['user_nama'] ?? 'User'); ?></span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Dashboard</h5>
      <p class="card-text">Selamat datang di dashboard. Tambahkan kontenmu di sini.</p>
      <p>Contoh link ke halaman lain yang butuh login:</p>
      <a href="protected_page.php" class="btn btn-primary">Halaman Terlindungi</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
