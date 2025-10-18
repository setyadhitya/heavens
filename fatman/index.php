
<?php

require_once __DIR__ . '/functions.php';
block_folder_by_role('fatman');



// ✅ Jika belum login, alihkan ke login.php
if (!is_logged_in()) {
  header("Location: login.php");
  exit;
}

// Jika sudah login, tampilkan dashboard
include 'navbar.php';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php show_flash(); ?>
  <div class="container mt-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="card-title">Dashboard</h4>
        <p>Selamat datang, <b><?= htmlspecialchars($_SESSION['user_nama'] ?? 'User'); ?></b>!</p>
        <p>Pilih menu di atas untuk melanjutkan.</p>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
