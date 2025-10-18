<?php
// /heavens/akun_mahasiswa/daftar/index.php

// HANYA include helper (session, DB, csrf, flash). JANGAN include access_guard di halaman publik ini.
require_once __DIR__ . '/../../fatman/functions.php';

$errors = [];
$success = false;

// Dapatkan IP address (sederhana)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Rate limit: Maksimal 3 request pendaftaran per 1 jam per IP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CEK RATE LIMIT (3/jam/IP)
    $sqlRate = "SELECT COUNT(*) AS cnt FROM tb_pendaftaran_akun WHERE ip_address = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)";
    if ($stmt = $mysqli->prepare($sqlRate)) {
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ((int)$res['cnt'] >= 3) {
            $errors[] = "Terlalu banyak permintaan pendaftaran dari IP Anda. Silakan coba lagi setelah beberapa saat.";
        }
        $stmt->close();
    } else {
        $errors[] = "Kesalahan server (rate limit).";
    }

    // Validasi CSRF
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token invalid.";
    }

    // Ambil input
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $nim      = trim($_POST['nim'] ?? '');
    $nomorhp  = trim($_POST['nomorhp'] ?? '');
    $password = $_POST['password'] ?? '';

    // =========================
    // VALIDASI INPUT (server-side)
    // =========================
    // Nama: hanya huruf dan spasi (tanpa angka/simbol)
    if ($nama === '' || !preg_match('/^[A-Za-z\s]+$/', $nama)) {
        $errors[] = "Nama hanya boleh huruf dan spasi (tanpa angka/simbol).";
    }

    // Username: hanya huruf/angka (tanpa spasi/simbol)
    if ($username === '' || !preg_match('/^[A-Za-z0-9]+$/', $username)) {
        $errors[] = "Username hanya boleh huruf dan angka (tanpa spasi/simbol).";
    }

    // NIM: wajib angka
    if ($nim === '' || !ctype_digit($nim)) {
        $errors[] = "NIM wajib angka.";
    }

    // Nomor HP: wajib angka
    if ($nomorhp === '' || !ctype_digit($nomorhp)) {
        $errors[] = "Nomor HP wajib angka.";
    }

    // Password: minimal 6 karakter (opsional tapi bagus)
    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter.";
    }

    // =========================
    // CEK DUPLIKAT USERNAME & NIM
    // =========================
    if (empty($errors)) {
        // Cek username
        $sqlUser = "SELECT 1 FROM tb_pendaftaran_akun WHERE username = ? LIMIT 1";
        if ($stmt = $mysqli->prepare($sqlUser)) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $errors[] = "Username sudah terdaftar. Gunakan username lain.";
            }
            $stmt->close();
        } else {
            $errors[] = "Kesalahan server (cek username).";
        }

        // Cek NIM
        $sqlNim = "SELECT 1 FROM tb_pendaftaran_akun WHERE nim = ? LIMIT 1";
        if ($stmt = $mysqli->prepare($sqlNim)) {
            $stmt->bind_param('s', $nim);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $errors[] = "NIM sudah terdaftar. Gunakan NIM lain.";
            }
            $stmt->close();
        } else {
            $errors[] = "Kesalahan server (cek NIM).";
        }
    }

    // =========================
    // SIMPAN DATA (status waiting)
    // =========================
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $sqlIns = "INSERT INTO tb_pendaftaran_akun (username, nama, nim, nomorhp, password, status, ip_address)
                   VALUES (?, ?, ?, ?, ?, 'waiting', ?)";
        if ($stmt = $mysqli->prepare($sqlIns)) {
            $stmt->bind_param('ssssss', $username, $nama, $nim, $nomorhp, $hash, $ip);
            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors[] = "Gagal menyimpan data. Silakan coba lagi.";
            }
            $stmt->close();
        } else {
            $errors[] = "Kesalahan server (insert).";
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Daftar Akun Mahasiswa</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php if ($success): ?>
    <!-- Redirect otomatis setelah 3 detik -->
    <meta http-equiv="refresh" content="3;url=/heavens/index.php">
  <?php endif; ?>
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center py-5">
  <div class="card shadow" style="max-width:520px; width:100%;">
    <div class="card-body">
      <h4 class="text-center mb-3">Pendaftaran Akun Mahasiswa</h4>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <strong>Request akun terkirim!</strong><br>
          Silahkan menunggu 1x24 jam untuk approve akun oleh admin.<br>
          Gunakan <strong>username dan password</strong> yang sudah didaftarkan untuk login.
        </div>
        <div class="text-center text-muted small">Mengalihkan ke beranda...</div>
      <?php else: ?>

        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger"><?= e($err); ?></div>
        <?php endforeach; ?>

        <form method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

          <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" required
                   value="<?= e($_POST['nama'] ?? '') ?>"
                   placeholder="contoh: Nafisa Ananda Zahra">
            <div class="form-text">Hanya huruf dan spasi (tanpa angka/simbol).</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required
                   value="<?= e($_POST['username'] ?? '') ?>"
                   placeholder="contoh: nafisazahra">
            <div class="form-text">Hanya huruf dan angka (tanpa spasi/simbol).</div>
          </div>

          <div class="mb-3">
            <label class="form-label">NIM</label>
            <input type="text" name="nim" class="form-control" required
                   value="<?= e($_POST['nim'] ?? '') ?>"
                   placeholder="contoh: 23117765">
            <div class="form-text">Hanya angka.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Nomor HP</label>
            <input type="text" name="nomorhp" class="form-control" required
                   value="<?= e($_POST['nomorhp'] ?? '') ?>"
                   placeholder="contoh: 081234567890">
            <div class="form-text">Hanya angka.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
              <input type="password" id="pwd" name="password" class="form-control" required placeholder="minimal 6 karakter">
              <button class="btn btn-outline-secondary" type="button" id="togglePwd">Show</button>
            </div>
          </div>

          <button class="btn btn-primary w-100">Kirim Permohonan</button>
        </form>

      <?php endif; ?>

      <div class="text-center mt-3">
        <a class="small text-muted" href="/heavens/akun_mahasiswa/login.php">&larr; Kembali ke halaman login</a>
      </div>
    </div>
  </div>
</div>

<script>
  // Show/Hide Password
  const btn = document.getElementById('togglePwd');
  const pwd = document.getElementById('pwd');
  if (btn && pwd) {
    btn.addEventListener('click', () => {
      if (pwd.type === 'password') {
        pwd.type = 'text';
        btn.textContent = 'Hide';
      } else {
        pwd.type = 'password';
        btn.textContent = 'Show';
      }
    });
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
