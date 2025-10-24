<?php
// /heavens/akun_assisten/login/index.php
// Login khusus untuk role "assisten"
$currentPage = 'loginassisten';
include __DIR__ . '/../../components/helper_bubble.php';
require_once __DIR__ . '/../../fatman/functions.php';

// Jika sudah login
if (is_logged_in()) {
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'assisten') {
        header('Location: /heavens/akun_assisten/');
        exit;
    }
    set_flash("Anda sudah login. Halaman login assisten hanya untuk akun assisten.", "warning");
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: /heavens/fatman/index.php'); exit;
    }
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'praktikan') {
        header('Location: /heavens/akun_mahasiswa/index.php'); exit;
    }
    header('Location: /heavens/index.php'); exit;
}

$pdo = db(); // koneksi PDO
$errors = [];
$redirect = $_GET['redirect'] ?? '/heavens/akun_assisten/';

// Batasi brute force
if (too_many_failed_logins()) {
    $errors[] = "Terlalu banyak percobaan login gagal. Silakan coba lagi beberapa menit.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    // CSRF
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token invalid.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $errors[] = "Username dan password wajib diisi.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    SELECT id, username, nama, nim, nomorhp, password, role, status
                    FROM tb_assisten
                    WHERE username = ? AND status = 'aktif'
                    LIMIT 1
                ");
                $stmt->execute([$username]);
                $row = $stmt->fetch();

                if ($row && password_verify($password, $row['password'])) {
                    if (strtolower($row['role']) !== 'assisten') {
                        // role tidak sesuai
                        $errors[] = "Username atau password salah.";
                        record_failed_login();
                    } else {
                        // Login sukses
                        session_regenerate_id(true);
                        $_SESSION['user_id']       = (int)$row['id'];
                        $_SESSION['user_nama']     = $row['nama'];
                        $_SESSION['role']          = 'assisten';
                        $_SESSION['last_activity'] = time();

                        // Opsional
                        $_SESSION['user_nim']      = $row['nim'];
                        $_SESSION['user_nomorhp']  = $row['nomorhp'];

                        // Reset counter gagal
                        if (isset($_SESSION['failed_login'])) unset($_SESSION['failed_login']);

                        // Redirect aman
                        $r = $_POST['redirect'] ?? $redirect;
                        if (empty($r) || strpos($r, 'http://') === 0 || strpos($r, 'https://') === 0) {
                            safe_redirect('/heavens/akun_assisten/');
                        } else {
                            if (strpos($r, '/heavens') !== 0) {
                                $r = '/heavens' . (strpos($r, '/') === 0 ? $r : '/' . $r);
                            }
                            safe_redirect($r);
                        }
                    }
                } else {
                    // user tidak ditemukan / password salah
                    $errors[] = "Username atau password salah.";
                    record_failed_login();
                }
            } catch (Exception $e) {
                $errors[] = "Kesalahan server saat memproses login.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Login Assisten</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php show_flash(); ?>

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow" style="width:380px">
    <div class="card-body">
      <h4 class="text-center mb-3">Login Assisten</h4>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err); ?></div>
      <?php endforeach; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="redirect" value="<?= e($redirect); ?>">

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required autofocus>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <button class="btn btn-primary w-100">Masuk</button>
      </form>

      <div class="text-center mt-3">
        <a class="small text-muted" href="/heavens/index.php">&larr; Kembali ke halaman utama</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
