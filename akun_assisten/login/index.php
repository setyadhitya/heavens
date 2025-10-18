<?php
// /heavens/akun_assisten/login/index.php
// Login khusus untuk role "assisten"

// --- include helper (session, db, csrf, flash, brute-force helpers) ---
// path relatif: dari /heavens/akun_assisten/login/ ke /heavens/fatman/functions.php
require_once __DIR__ . '/../../fatman/functions.php';

// --------------------------------------------------
// PENTING: TIDAK MENG-include access_guard.php DI SINI
// access_guard otomatis dijalankan untuk halaman protected via .htaccess,
// tetapi HALAMAN LOGIN harus dibiarkan bebas supaya user bisa masuk.
// --------------------------------------------------

// Jika sudah login
if (is_logged_in()) {
    // Kalau sudah asisten -> langsung ke dashboard asisten
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'assisten') {
        header('Location: /heavens/akun_assisten/');
        exit;
    }

    // Kalau sudah login tapi bukan asisten -> redirect ke home sesuai role
    set_flash("Anda sudah login. Halaman login asisten hanya untuk akun asisten.", "warning");
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: /heavens/fatman/index.php');
        exit;
    }
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'praktikan') {
        header('Location: /heavens/akun_mahasiswa/index.php');
        exit;
    }
    header('Location: /heavens/index.php');
    exit;
}

// Persiapan
$errors = [];
// redirect param (pastikan internal)
$redirect = $_GET['redirect'] ?? '/heavens/akun_assisten/';

// Jika terlalu banyak percobaan gagal, hentikan proses login
if (too_many_failed_logins()) {
    $errors[] = "Terlalu banyak percobaan login gagal. Silakan coba lagi beberapa menit.";
}

// Proses POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    // CSRF check
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token invalid.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $errors[] = "Username dan password wajib diisi.";
        } else {
            // Ambil asisten yang status = 'aktif'
            $sql = "SELECT id, username, nama, nim, nomorhp, password, role, status
                    FROM tb_assisten
                    WHERE username = ? AND status = 'aktif'
                    LIMIT 1";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    // Password column = 'password' (hash)
                    if (password_verify($password, $row['password'])) {
                        // Pastikan role 'assisten' (case-insensitive)
                        if (strtolower($row['role']) !== 'assisten') {
                            // Jangan beri detail, cukup pesan umum
                            $errors[] = "Username atau password salah.";
                            record_failed_login();
                        } else {
                            // Login sukses
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = (int)$row['id'];
                            $_SESSION['user_nama'] = $row['nama'];
                            $_SESSION['role'] = 'assisten';
                            $_SESSION['last_activity'] = time();

                            // Simpan info opsional
                            $_SESSION['user_nim'] = $row['nim'];
                            $_SESSION['user_nomorhp'] = $row['nomorhp'];

                            // Reset percobaan gagal
                            if (isset($_SESSION['failed_login'])) unset($_SESSION['failed_login']);

                            // Redirect aman (internal only)
                            // Gunakan safe_redirect jika redirect berasal dari input/param
                            // Tapi pastikan redirect internal; jika param kosong gunakan default
                            $r = $_POST['redirect'] ?? $redirect;
                            // jika r kosong atau berbahaya, pakai default
                            if (empty($r) || strpos($r, 'http://') === 0 || strpos($r, 'https://') === 0) {
                                safe_redirect('/heavens/akun_assisten/');
                            } else {
                                // jika relative path tanpa /heavens prefix, tambahkan /heavens jika perlu
                                if (strpos($r, '/heavens') !== 0) {
                                    $r = '/heavens' . (strpos($r, '/') === 0 ? $r : '/' . $r);
                                }
                                safe_redirect($r);
                            }
                        }
                    } else {
                        // password salah
                        $errors[] = "Username atau password salah.";
                        record_failed_login();
                    }
                } else {
                    // username tidak ditemukan atau status bukan aktif
                    $errors[] = "Username atau password salah.";
                    record_failed_login();
                }
                $stmt->close();
            } else {
                $errors[] = "Kesalahan server saat mempersiapkan query.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login Asisten</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php
// Tampilkan flash (flash diset oleh access_guard atau proses lain)
show_flash();
?>

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow" style="width:380px">
    <div class="card-body">
      <h4 class="text-center mb-3">Login Asisten</h4>

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
