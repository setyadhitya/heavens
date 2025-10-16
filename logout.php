<?php
session_start();

// Hapus semua data sesi
$_SESSION = [];

// Hapus cookie sesi di browser (jika ada)
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
  );
}

// Hancurkan sesi
session_destroy();

// Arahkan kembali ke halaman login
header("Location: /heavens/login.php");
exit;
