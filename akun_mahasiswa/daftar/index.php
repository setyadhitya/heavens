<?php
// /heavens/akun_mahasiswa/daftar/index.php
require_once __DIR__ . '/../../fatman/functions.php';

$pdo = db();
$errors = [];
$success = false;
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// FORM SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token invalid.";
    }

    // Rate limit: max 3 / jam / IP
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM tb_pendaftaran_akun 
        WHERE ip_address = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ip]);
    if ($stmt->fetchColumn() >= 3) {
        $errors[] = "Terlalu banyak pendaftaran dari IP Anda. Coba lagi nanti.";
    }

    // Input
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $nim      = trim($_POST['nim'] ?? '');
    $nomorhp  = trim($_POST['nomorhp'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi
    if ($nama === '' || !preg_match('/^[A-Za-z\s]+$/', $nama)) {
        $errors[] = "Nama hanya boleh huruf dan spasi.";
    }
    if ($username === '' || !preg_match('/^[A-Za-z0-9]+$/', $username)) {
        $errors[] = "Username hanya boleh huruf dan angka.";
    }
    if ($nim === '' || !ctype_digit($nim)) {
        $errors[] = "NIM wajib angka.";
    }
    if ($nomorhp === '' || !ctype_digit($nomorhp)) {
        $errors[] = "Nomor HP wajib angka.";
    }
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
        $errors[] = "Password minimal 8 karakter dan harus ada huruf + angka.";
    }

    // CEK DUPLIKAT (Pendaftaran + Praktikan + Asisten)
    if (!$errors) {

        // cek user pending
        $stmt = $pdo->prepare("SELECT 1 FROM tb_pendaftaran_akun WHERE username=? OR nim=?");
        $stmt->execute([$username, $nim]);
        if ($stmt->fetch()) $errors[] = "Username atau NIM sudah terdaftar (pending).";

        // cek praktikan aktif
        $stmt = $pdo->prepare("SELECT 1 FROM tb_praktikan WHERE username=? OR nim=?");
        $stmt->execute([$username, $nim]);
        if ($stmt->fetch()) $errors[] = "Username atau NIM sudah aktif sebagai Praktikan.";

        // cek asisten
        $stmt = $pdo->prepare("SELECT 1 FROM tb_assisten WHERE username=? OR nim=?");
        $stmt->execute([$username, $nim]);
        if ($stmt->fetch()) $errors[] = "Data sudah terdaftar sebagai Asisten.";
    }

    // SIMPAN
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO tb_pendaftaran_akun (username, nama, nim, nomorhp, password, status, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, 'waiting', ?, NOW())
        ");
        if ($stmt->execute([$username, $nama, $nim, $nomorhp, $hash, $ip])) {
            $success = true;
        } else {
            $errors[] = "Gagal menyimpan data.";
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Akun Mahasiswa</title>
    <?php if ($success): ?>
    <meta http-equiv="refresh" content="4;url=/heavens/index.php">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5 d-flex justify-content-center">
    <div class="card shadow" style="max-width: 500px; width: 100%">
        <div class="card-body">
            <h4 class="text-center mb-4">Pendaftaran Akun Mahasiswa</h4>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ Permohonan akun berhasil dikirim.<br>
                    Silakan tunggu persetujuan admin (max 1×24 jam).<br>
                    Gunakan <b>username</b> dan <b>password</b> untuk login setelah di-approve.
                </div>
            <?php else: ?>

                <?php foreach ($errors as $e): ?>
                    <div class="alert alert-danger"><?= e($e) ?></div>
                <?php endforeach; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input name="nama" class="form-control" required value="<?= e($_POST['nama'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input name="username" class="form-control" required value="<?= e($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">NIM</label>
                        <input name="nim" class="form-control" required value="<?= e($_POST['nim'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor HP</label>
                        <input name="nomorhp" class="form-control" required value="<?= e($_POST['nomorhp'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter + angka" required>
                    </div>

                    <button class="btn btn-primary w-100">Daftar Sekarang</button>
                </form>

            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="/heavens/akun_mahasiswa/login.php">&larr; Kembali ke Login</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
