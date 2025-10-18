<?php
/**
 * Access Guard (Global) — /heavens/access_guard.php
 * --------------------------------------------------
 * Dipanggil sekali di index.php setiap folder role:
 *   - /heavens/fatman/index.php            (ADMIN)
 *   - /heavens/akun_assisten/index.php     (ASSISTEN)
 *   - /heavens/akun_mahasiswa/index.php    (PRAKTIKAN)
 * 
 * Efek:
 * - Memblokir akses ke folder yang bukan hak role
 * - Redirect otomatis ke halaman role masing-masing
 * - Tampilkan alert Bootstrap (flash) di halaman tujuan
 * - Tidak perlu menulis proteksi di tiap file/subfolder lagi
 */

require_once __DIR__ . '/fatman/functions.php'; // memuat session, config, helpers

// Helper ringkas untuk set flash message
if (!function_exists('set_flash')) {
    function set_flash($msg, $type = 'danger') {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    }
}

// Helper untuk redirect dan stop eksekusi
if (!function_exists('guard_redirect')) {
    function guard_redirect($url) {
        header("Location: $url");
        exit;
    }
}

// Jika belum login sama sekali → arahkan ke halaman utama (atau login Anda)
if (empty($_SESSION['role'])) {
    set_flash("Silakan login terlebih dahulu.", "warning");
    guard_redirect("/heavens/index.php");
}

// Ambil role dan path saat ini
$role        = $_SESSION['role'];
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';

// Aturan blokir akses antar role:
// - admin:  DILARANG ke /akun_assisten/ dan /akun_mahasiswa/
// - assisten: DILARANG ke /fatman/ dan /akun_mahasiswa/
// - praktikan: DILARANG ke /fatman/ dan /akun_assisten/
$deny = [
    'admin'     => ['/akun_assisten', '/akun_mahasiswa'],
    'assisten'  => ['/fatman', '/akun_mahasiswa'],
    'praktikan' => ['/fatman', '/akun_assisten'],
];

// Jika role tidak dikenal, pulangkan ke beranda
if (!isset($deny[$role])) {
    set_flash("Akses ditolak. Role tidak dikenali.", "danger");
    guard_redirect("/heavens/index.php");
}

// Cek apakah path sekarang mengandung folder yang dilarang
foreach ($deny[$role] as $forbidden) {
    if (strpos($currentPath, $forbidden) !== false) {
        // Kirim flash alert polos (tanpa ikon)
        set_flash("Halaman ini bukan untuk role akun anda.", "danger");

        // Redirect ke home sesuai role
        if ($role === 'admin')     guard_redirect("/heavens/fatman/index.php");
        if ($role === 'assisten')  guard_redirect("/heavens/akun_assisten/index.php");
        if ($role === 'praktikan') guard_redirect("/heavens/akun_mahasiswa/index.php");
    }
}

// Jika lolos semua pengecekan, berarti akses valid untuk role saat ini.
