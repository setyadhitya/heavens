<?php
// /akun_assisten/login/index.php
require_once __DIR__ . '/../../fatman/functions.php';

// Jika sudah login:
if (is_logged_in()) {
    // Kalau role asisten -> ke dashboard asisten
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'assisten') {
        header('Location: /akun_assisten/');
        exit;
    }
    // Selain itu tendang ke halaman utama
    header('Location: /index.php');
    exit;
}

$errors = [];
$redirect = $_GET['redirect'] ?? '/akun_assisten/';

// Brute force sederhana
if (too_many_failed_logins()) {
    $errors[] = "Terlalu banyak percobaan gagal. Coba lagi beberapa menit lagi.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token invalid.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $errors[] = "Username dan password wajib diisi.";
        } else {
            // Hanya izinkan asisten yang status 'aktif'
            $sql = "SELECT id, username, nama, nim, nomorhp, password, role, status
                    FROM tb_assisten
                    WHERE username = ? AND status = 'aktif'
                    LIMIT 1";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    if (password_verify($password, $row['password'])) {
                        // role harus 'assisten'
                        if (strtolower($row['role']) !== 'assisten') {
                            $errors[] = "Akun ini bukan role asisten.";
                        } else {
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = (int) $row['id'];
                            $_SESSION['user_nama'] = $row['nama'];
                            $_SESSION['role'] = 'assisten';
                            $_SESSION['last_activity'] = time();
                            // info tambahan (opsional)
                            $_SESSION['user_nim'] = $row['nim'];
                            $_SESSION['user_nomorhp'] = $row['nomorhp'];

                            // redirect aman (internal only)
                            safe_redirect('/heavens/akun_assisten/');
                        }
                    } else {
                        record_failed_login();
                        $errors[] = "Username atau password salah.";
                    }
                } else {
                    record_failed_login();
                    $errors[] = "Username atau password salah.";
                }
                $stmt->close();
            } else {
                $errors[] = "Kesalahan server.";
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
                    <a class="small text-muted" href="/index.php">&larr; Kembali ke halaman utama</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>