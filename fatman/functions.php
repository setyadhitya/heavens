<?php
// Jika belum ada session yang berjalan, mulai session baru
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Panggil file config.php (koneksi database + pengaturan dasar)
require_once __DIR__ . '/config.php';


// =====================
// CSRF Helper Function
// =====================

// Membuat token CSRF unik untuk mencegah serangan CSRF pada form
function csrf_token() {
    // Jika token belum dibuat, buat token acak 32 byte
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token']; // kirim token untuk digunakan di form
}

// Memverifikasi apakah token CSRF yang dikirim dari form valid
function verify_csrf($token) {
    // Bandingkan token dari form dan session secara aman (mencegah timing attack)
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}


// ==========================
// Output Escaping (Security)
// ==========================

// Melindungi output HTML dari serangan XSS dengan htmlspecialchars
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


// =============================
// Autentikasi & Sistem Login
// =============================

// Cek apakah user sudah login
function is_logged_in() {
    // Kalau session user_id belum ada → dianggap belum login
    if (empty($_SESSION['user_id'])) return false;

    // Auto logout kalau idle lebih dari 30 menit
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();     // hapus semua session
        session_destroy();   // hancurkan session
        return false;
    }

    // Perbarui waktu aktivitas terakhir
    $_SESSION['last_activity'] = time();
    return true;
}

// Memaksa user login sebelum akses halaman tertentu
function require_login() {
    if (!is_logged_in()) {
        // Jika belum login → redirect ke halaman login
        header('Location: index.php');
        exit;
    }
}


// =====================================
// Perlindungan Brute Force (Login Gagal)
// =====================================

// Menyimpan data percobaan login gagal dalam session
function record_failed_login() {
    if (!isset($_SESSION['failed_login'])) {
        $_SESSION['failed_login'] = ['count' => 0, 'last' => 0];
    }
    $_SESSION['failed_login']['count'] += 1; // tambah 1 kali gagal
    $_SESSION['failed_login']['last'] = time(); // simpan waktu gagal terakhir
}

// Cek apakah terlalu banyak percobaan login gagal
function too_many_failed_logins() {
    if (empty($_SESSION['failed_login'])) return false;

    $fail = $_SESSION['failed_login'];

    // Kalau gagal lebih dari 5 kali dalam 5 menit → BLOCK sementara
    if ($fail['count'] >= 5 && (time() - $fail['last']) < 300) {
        return true;
    }

    // Reset percobaan gagal jika sudah lewat 10 menit
    if ((time() - $fail['last']) > 600) {
        unset($_SESSION['failed_login']);
    }

    return false;
}


// ==============================
// Utility Path dan Redirect Aman
// ==============================

// Ambil URL/path halaman yang sedang diakses
function get_current_path() {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return $uri; // Contoh: /heavens/dashboard.php
}

// Redirect aman → tidak izinkan URL luar (open redirect attack)
function safe_redirect($url) {
    if (empty($url)) {
        header('Location: index.php');
        exit;
    }

    // Tolak redirect ke link HTTP dari luar (misal redirect ke google.com)
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        header('Location: index.php');
        exit;
    }

    // Redirect lokal (internal) aja
    header('Location: ' . $url);
    exit;
}


// ============================
// Login + Auto Redirect Halaman
// ============================

// Kalau user masuk halaman yang butuh login, tapi belum login → bawa ke login.php
// Setelah login, balikin lagi ke halaman semula
function require_login_and_redirect() {
    if (!is_logged_in()) {
        $current = $_SERVER['REQUEST_URI']; // URL yang sedang diakses

        // Hanya simpan redirect jika URL bukan folder "sensitif"
        if (
            strpos($current, '/praktikum/') === false &&
            strpos($current, '/assisten_praktikum/') === false &&
            strpos($current, '/peserta/') === false &&
            strpos($current, '/approve/') === false &&
            strpos($current, '/praktikan/') === false &&
            strpos($current, '/admin/') === false
        ) {
            $_SESSION['redirect_to'] = $current; // simpan tujuan redirect
        } else {
            unset($_SESSION['redirect_to']); // kalau folder sensitif, jangan redirect otomatis
        }

        header('Location: /heavens/fatman/login.php');
        exit;
    }
}


// ====================
// ROLE: Khusus ADMIN
// ====================

// Batasi halaman ini hanya untuk admin
function require_admin() {
    require_login(); // pastikan user sudah login dulu

    // Kalau role bukan admin → stop akses
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403); // Unauthorized
        die('<div style="padding:20px;color:white;background:red;">Akses ditolak! Halaman ini hanya untuk ADMIN.</div>');
    }
}


// =========================
// FLASH MESSAGE (alert sekali tampil)
// =========================

// Simpan pesan flash ke session
// Simpan pesan flash ke session
function set_flash($msg, $type = 'danger') {
    $_SESSION['flash'] = [
        'msg'  => $msg,
        'type' => $type
    ];
}

// Tampilkan alert Bootstrap di bagian atas halaman lalu hapus
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        // Polos, tanpa ikon — sesuai permintaan
        echo '<div class="alert alert-' . e($_SESSION['flash']['type']) . ' text-center mb-0">'
             . e($_SESSION['flash']['msg']) .
             '</div>';
        unset($_SESSION['flash']); // hapus supaya tidak tampil berulang
    }
}

// ==============================
// ROLE-BASED FOLDER ACCESS BLOCKER
// ==============================
function block_folder_by_role($currentFolder) {
    $role = $_SESSION['role'] ?? null;

    // Jika belum login, ya nggak usah diblokir di sini (biar fungsi login yang handle)
    if (!$role) return;

    // Aturan akses sesuai role
    $denied = [
        'admin'      => ['akun_assisten', 'akun_mahasiswa'],
        'assisten'   => ['fatman', 'akun_mahasiswa'],
        'praktikan'  => ['fatman', 'akun_assisten']
    ];

    // Kalau role tidak dikenal, dilarang total
    if (!isset($denied[$role])) {
        set_flash("Akses ditolak. Role tidak dikenali!", "danger");
        header("Location: /heavens/index.php");
        exit;
    }

    // Kalau role dilarang buka folder ini -> redirect dengan pesan
    if (in_array($currentFolder, $denied[$role])) {
        set_flash("Halaman tadi bukan untuk role akun Anda! silahkan kembali kesini", "danger");

        // Redirect sesuai role
        if ($role === 'admin') header("Location: /heavens/fatman/index.php");
        if ($role === 'assisten') header("Location: /heavens/akun_assisten/index.php");
        if ($role === 'praktikan') header("Location: /heavens/akun_mahasiswa/index.php");
        exit;
    }
}

?>
