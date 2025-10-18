<?php
// Jika belum ada session yang berjalan, mulai session baru
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi & konfigurasi umum
require_once __DIR__ . '/config.php';

// =====================
// CSRF Helper Function
// =====================
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

// ==========================
// Output Escaping (Security)
// ==========================
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// =============================
// Autentikasi & Sistem Login
// =============================
function is_logged_in() {
    if (empty($_SESSION['user_id'])) return false;

    // Auto logout kalau idle > 30 menit
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * NOTE (multi-login):
 * Biarkan generic → ke beranda umum. Guard yang memutuskan portal login per folder.
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: /heavens/index.php');
        exit;
    }
}

// =====================================
// Perlindungan Brute Force (Login Gagal)
// =====================================
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
    if ((time() - $fail['last']) > 600) {
        unset($_SESSION['failed_login']);
    }
    return false;
}

// ==============================
// Utility Path dan Redirect Aman
// ==============================
function get_current_path() {
    return $_SERVER['REQUEST_URI'] ?? '/';
}

function safe_redirect($url) {
    if (empty($url)) {
        header('Location: /heavens/index.php');
        exit;
    }
    // cegah open redirect
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        header('Location: /heavens/index.php');
        exit;
    }
    header('Location: ' . $url);
    exit;
}

// ====================
// ROLE: Khusus ADMIN
// ====================
function require_admin() {
    require_login();
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die('<div style="padding:20px;color:white;background:red;">Akses ditolak! Halaman ini hanya untuk ADMIN.</div>');
    }
}

// =========================
// FLASH MESSAGE (sekali tampil)
// =========================
function set_flash($msg, $type = 'danger') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function show_flash() {
    if (!empty($_SESSION['flash'])) {
        echo '<div class="alert alert-' . e($_SESSION['flash']['type']) . ' text-center mb-0">'
           . e($_SESSION['flash']['msg']) .
           '</div>';
        unset($_SESSION['flash']);
    }
}

/**
 * ❌ HAPUS fungsi block_folder_by_role()
 * (Sekarang proteksi role ditangani otomatis oleh access_guard.php via auto_prepend_file)
 */
