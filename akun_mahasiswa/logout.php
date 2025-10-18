<?php
// /heavens/akun_assisten/logout.php
require_once __DIR__ . '/../fatman/functions.php';

// Kalau mau logout khusus asisten saja tanpa ganggu user lain, pastikan role-nya asisten.
// Kalau tidak peduli (logout global), biarkan seperti ini.
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Kembali ke login asisten
header('Location: /heavens/akun_mahasiswa/login/index.php');
exit;
