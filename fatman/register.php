<?php
require_once __DIR__ . '/functions.php';
require_admin(); // 🔒 hanya admin login yang boleh akses

$errors = [];
$success = '';
$pdo = db(); // koneksi PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token invalid.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validasi nama
        if (strlen($nama) < 3) {
            $errors[] = 'Nama minimal 3 karakter.';
        }

        // Validasi username
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username harus 3-30 karakter (huruf, angka, underscore).';
        }

        // Validasi password (minimal 8 karakter, ada huruf + angka)
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*[0-9]).{8,}$/', $password)) {
            $errors[] = 'Password minimal 8 karakter dan wajib mengandung huruf & angka.';
        }

        // Jika aman
        if (!$errors) {
            // Cek existing user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'Username sudah digunakan.';
            } else {
                // Simpan
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password_hash, nama, role, created_at)
                    VALUES (?, ?, ?, 'admin', NOW())
                ");

                if ($stmt->execute([$username, $hash, $nama])) {
                    $success = '✅ Admin baru berhasil dibuat.';
                } else {
                    $errors[] = 'Gagal menyimpan data.';
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Register Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between mb-3">
        <h4>Tambah Admin Baru</h4>
        <a href="/heavens/fatman/index.php" class="btn btn-secondary btn-sm">Kembali</a>
    </div>
    <div class="card shadow-sm" style="max-width:500px;margin:auto">
        <div class="card-body">
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger"><?= e($err) ?></div>
            <?php endforeach; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input name="nama" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input name="username" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password (aman)</label>
                    <input type="password" name="password" class="form-control" required>
                    <small class="text-muted">Minimal 8 karakter & wajib ada huruf dan angka.</small>
                </div>

                <button class="btn btn-success w-100">Tambah Admin</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
