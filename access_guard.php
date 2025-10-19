<?php
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// di paling atas access_guard.php (baris whitelist)
if (strpos($uri, '/heavens/akun_mahasiswa/daftar') === 0) return;

// ✅ IZINKAN HALAMAN LOGIN & LOGOUT
if (strpos($uri, '/heavens/fatman/login.php') === 0) return;
if (strpos($uri, '/heavens/akun_assisten/login') === 0) return;
if (strpos($uri, '/heavens/akun_mahasiswa/login') === 0) return;

if (strpos($uri, '/heavens/fatman/logout.php') === 0) return;
if (strpos($uri, '/heavens/akun_assisten/logout.php') === 0) return;
if (strpos($uri, '/heavens/akun_mahasiswa/logout.php') === 0) return;


/**
 * Access Guard Global — /heavens/access_guard.php
 * Auto-dijalankan untuk SETIAP file di folder role via .htaccess (auto_prepend_file).
 * Fitur:
 *  - Redirect ke portal login sesuai folder bila belum login (multi-login).
 *  - Blokir akses silang antar role, redirect ke home role + flash alert.
 *  - Aman untuk semua subfolder & file.
 */
require_once __DIR__ . '/fatman/functions.php'; // session + helpers

// Helper ringkas (fallback jika belum ada)
if (!function_exists('set_flash')) {
    function set_flash($msg, $type = 'danger') {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    }
}

// Ambil path saat ini
$uri  = $_SERVER['REQUEST_URI'] ?? '/';
$role = $_SESSION['role'] ?? null;

// ————— 1) Jika BELUM LOGIN → arahkan ke portal login sesuai folder yang diakses —————
if (!$role) {
    // admin folder
    if (strpos($uri, '/heavens/fatman/') === 0) {
        set_flash('Silakan login terlebih dahulu.', 'warning');
        header('Location: /heavens/fatman/login.php');
        exit;
    }
    // assisten folder
    if (strpos($uri, '/heavens/akun_assisten/') === 0) {
        set_flash('Silakan login terlebih dahulu.', 'warning');
        header('Location: /heavens/akun_assisten/login/index.php');
        exit;
    }
    // praktikan folder
    if (strpos($uri, '/heavens/akun_mahasiswa/') === 0) {
        set_flash('Silakan login terlebih dahulu.', 'warning');
        header('Location: /heavens/akun_mahasiswa/login/index.php');
        exit;
    }
    // selain itu biarkan ke beranda
    // (biar halaman umum tetap bisa diakses tanpa login)
    return;
}

// ————— 2) Sudah LOGIN → Blokir akses folder yang bukan haknya —————
$deny = [
    'admin'     => ['/heavens/akun_assisten/', '/heavens/akun_mahasiswa/'],
    'assisten'  => ['/heavens/fatman/', '/heavens/akun_mahasiswa/'],
    'praktikan' => ['/heavens/fatman/', '/heavens/akun_assisten/'],
];

if (!isset($deny[$role])) {
    set_flash('Akses ditolak. Role tidak dikenali.', 'danger');
    header('Location: /heavens/index.php');
    exit;
}

foreach ($deny[$role] as $forbiddenPrefix) {
    if (strpos($uri, $forbiddenPrefix) === 0) {
        set_flash('Halaman yang akan anda tuju bukan untuk role akun anda.', 'danger');
        if ($role === 'admin') {
            header('Location: /heavens/fatman/index.php');
        } elseif ($role === 'assisten') {
            header('Location: /heavens/akun_assisten/index.php');
        } else { // praktikan
            header('Location: /heavens/akun_mahasiswa/index.php');
        }
        exit;
    }
}

// Lolos guard → halaman aman untuk role saat ini.
