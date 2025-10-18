<?php
// ✅ Urutan HARUS seperti ini:
// 1. functions.php untuk memulai session & helper
// 2. access_guard.php untuk proteksi role otomatis

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../access_guard.php';

// ✅ Ini opsional, tapi aman: cek jika belum login
if (!is_logged_in()) {
    header("Location: /heavens/login.php");
    exit;
}

// ✅ Jika perlu navbar, panggil di sini
include 'navbar.php';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Dashboard Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- ✅ Flash alert muncul di paling atas -->
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert alert-<?= $_SESSION['flash']['type'] ?> text-center mb-0">
      <?= $_SESSION['flash']['msg'] ?>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="container mt-4">
  <div class="card shadow-sm">
    <div class="card-body">
      <h4 class="card-title">Dashboard Admin</h4>
      <p>Selamat datang, <b><?= htmlspecialchars($_SESSION['user_nama'] ?? 'User'); ?></b>!</p>
      <p>Silakan pilih menu di navbar untuk melanjutkan.</p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
