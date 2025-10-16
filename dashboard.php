<?php
require_once __DIR__ . '/functions.php';
require_login();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard</title></head>
<body>
<h2>Welcome, <?php echo e($_SESSION['user_nama'] ?? 'User'); ?></h2>
<p>Ini halaman yang hanya bisa diakses jika login.</p>
<p><a href="logout.php">Logout</a></p>
</body>
</html>
