<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/config.php';

// CSRF helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

// Output escaping
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Simple auth check
function is_logged_in() {
    if (empty($_SESSION['user_id'])) return false;
    // Session timeout (30 minutes)
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // expired
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

// Simple brute-force protection (session based)
function record_failed_login() {
    if (!isset($_SESSION['failed_login'])) {
        $_SESSION['failed_login'] = ['count' => 0, 'last' => 0];
    }
    $_SESSION['failed_login']['count'] += 1;
    $_SESSION['failed_login']['last'] = time();
}

function too_many_failed_logins() {
    if (empty($_SESSION['failed_login'])) return false;
    $fail = $_SESSION['failed_login'];
    if ($fail['count'] >= 5 && (time() - $fail['last']) < 300) {
        return true;
    }
    // reset attempts after 10 minutes
    if ((time() - $fail['last']) > 600) {
        unset($_SESSION['failed_login']);
    }
    return false;
}
// Ambil path relatif saat ini (contoh: /heavens/dashboard.php?x=1)
function get_current_path() {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    // Jika aplikasi berada di subfolder (contoh /heavens), uri sudah relatif dari root server
    return $uri;
}

// Cegah open redirect — hanya izinkan path internal (tidak mengizinkan http://)
function safe_redirect($url) {
    if (empty($url)) {
        header('Location: index.php');
        exit;
    }
    // pastikan bukan URL luar
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        header('Location: index.php');
        exit;
    }
    // jika url dimulai dengan /, gunakan itu; jika relative, gunakan langsung
    header('Location: ' . $url);
    exit;
}

// Saat butuh akses halaman terlindungi: simpan tujuan dan pindah ke login
function require_login_and_redirect() {
    if (!is_logged_in()) {
        // Simpan halaman yang diminta sebelum login
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];

        // 🔒 Redirect absolut ke login.php di root folder
        header('Location: /heavens/login.php');
        exit;
    }
}

function require_admin() {
    require_login(); // pastikan sudah login dulu
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die('<div style="padding:20px;color:white;background:red;">Akses ditolak! Halaman ini hanya untuk ADMIN.</div>');
    }
}
?>