<?php
// /heavens/fatman/login.php
require_once __DIR__ . '/functions.php'; // session, DB ($mysqli), helper (csrf, flash, brute force)

// ⛔ JANGAN pakai require_admin() di halaman login!

// Jika SUDAH login
if (is_logged_in()) {
    // Kalau sudah admin → langsung ke dashboard admin
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: /heavens/fatman/index.php");
        exit;
    }
    // Kalau sudah login tapi bukan admin → kembalikan ke home sesuai role/umum
    set_flash("Anda sudah login. Halaman ini khusus admin.", "warning");
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'assisten') {
        header("Location: /heavens/akun_assisten/index.php"); exit;
    }
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'praktikan') {
        header("Location: /heavens/akun_mahasiswa/index.php"); exit;
    }
    header("Location: /heavens/index.php");
    exit;
}

$errors = [];

// Blokir brute force sederhana (pakai helper yang sudah ada di functions.php)
if (too_many_failed_logins()) {
    // Tidak proses POST sama sekali
    $errors[] = "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam beberapa menit.";
}

// Proses submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {

    // Validasi CSRF
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token invalid.";
    } else {
        // Ambil input
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $errors[] = "Username dan password wajib diisi.";
        } else {
            // Ambil user admin dari tabel users
            $stmt = $mysqli->prepare("SELECT id, username, password_hash, nama, role FROM users WHERE username = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($row = $res->fetch_assoc()) {
                    // Cek password
                    if (password_verify($password, $row['password_hash'])) {
                        // Pastikan role benar-benar admin
                        if (strtolower($row['role']) !== 'admin') {
                            // Jangan bocorkan info; beri pesan umum
                            $errors[] = "Username atau password salah.";
                            record_failed_login();
                        } else {
                            // Sukses login admin
                            session_regenerate_id(true);
                            $_SESSION['user_id']       = (int)$row['id'];
                            $_SESSION['user_nama']     = $row['nama'];
                            $_SESSION['role']          = 'admin';
                            $_SESSION['last_activity'] = time();

                            // Reset counter brute force
                            if (isset($_SESSION['failed_login'])) unset($_SESSION['failed_login']);

                            // Redirect ke tujuan bila ada, jika tidak ke dashboard admin
                            if (!empty($_SESSION['redirect_to'])) {
                                $redirect = $_SESSION['redirect_to'];
                                unset($_SESSION['redirect_to']);
                                safe_redirect($redirect);
                            } else {
                                header("Location: /heavens/fatman/index.php");
                                exit;
                            }
                        }
                    } else {
                        // Password salah
                        $errors[] = "Username atau password salah.";
                        record_failed_login();
                    }
                } else {
                    // Username tidak ditemukan
                    $errors[] = "Username atau password salah.";
                    record_failed_login();
                }
                $stmt->close();
            } else {
                $errors[] = "Kesalahan server. (prepare failed)";
            }
        }
    }
}

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php
// Tampilkan flash alert di paling atas (sekali tampil)
show_flash();
?>

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow" style="width:360px">
    <div class="card-body">
      <h4 class="text-center mb-3">Login Admin</h4>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err); ?></div>
      <?php endforeach; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

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
        <a class="small text-muted" href="/heavens/index.php">&larr; Kembali ke Beranda</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
