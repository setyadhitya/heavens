<?php
// /akun_assisten/index.php

require_once __DIR__ . '/../fatman/functions.php';
block_folder_by_role('akun_assisten'); // 🔥 proteksi folder



// Wajib login
// Paksa login khusus asisten ONLY
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'assisten') {
header("Location: /heavens/akun_assisten/login/index.php?redirect=/heavens/akun_assisten/");
    exit;
}

// Wajib role 'assisten'
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'assisten') {
    header('Location: /index.php');
    exit;
}

// Data tampilan
$nama    = $_SESSION['user_nama']   ?? 'Asisten';
$nim     = $_SESSION['user_nim']    ?? '-';
$nomorhp = $_SESSION['user_nomorhp']?? '-';
$status  = 'aktif'; // karena login disaring status aktif
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Halaman Asisten - <?= e($nama); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f4f6f9; display:flex; flex-direction:column; min-height:100vh; }
.header {
  padding:24px; background:linear-gradient(135deg,#007bff,#00c6ff);
  color:#fff; border-radius:10px; margin:20px 0 14px;
}
.card-menu { cursor:pointer; transition:.2s; border-radius:12px; }
.card-menu:hover { transform:translateY(-6px); box-shadow:0 8px 18px rgba(0,0,0,.12); }
footer { margin-top:auto; padding:12px 0; color:gray; font-size:.9rem; text-align:center; }
a.text-dark { text-decoration:none; }
</style>
</head>
<body>
    <?php show_flash(); ?>

<div class="container">
  <div class="header shadow-sm">
    <h3 class="mb-2">Halaman Asisten</h3>
    <div class="small">Halo, <strong><?= e($nama); ?></strong></div>
    <div class="small">NIM: <?= e($nim); ?> | No. HP: <?= e($nomorhp); ?></div>
    <div class="small">Status: <?= e($status); ?></div>
  </div>

  <div class="row g-3 justify-content-center">
    <div class="col-6 col-md-4">
      <a href="/akun_assisten/buat_kode_presensi.php" class="text-dark">
        <div class="card p-3 card-menu text-center bg-white">
          <i class="bi bi-qr-code display-6 text-primary"></i>
          <div class="fw-semibold mt-2">Buat Kode Presensi</div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4">
      <a href="/akun_assisten/penugasan.php" class="text-dark">
        <div class="card p-3 card-menu text-center">
          <i class="bi bi-file-earmark-text display-6 text-danger"></i>
          <div class="fw-semibold mt-2">Penugasan</div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4">
      <a href="/akun_assisten/aktivitas.php" class="text-dark">
        <div class="card p-3 card-menu text-center">
          <i class="bi bi-list-task display-6 text-success"></i>
          <div class="fw-semibold mt-2">Aktivitas</div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4">
      <a href="/akun_assisten/pengumuman.php" class="text-dark">
        <div class="card p-3 card-menu text-center">
          <i class="bi bi-megaphone display-6 text-warning"></i>
          <div class="fw-semibold mt-2">Pengumuman</div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4">
      <a href="/akun_assisten/akun.php" class="text-dark">
        <div class="card p-3 card-menu text-center">
          <i class="bi bi-person-circle display-6 text-info"></i>
          <div class="fw-semibold mt-2">Akun</div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4">
      <a href="/heavens/akun_assisten/logout.php" class="text-dark">
        <div class="card p-3 card-menu text-center">
          <i class="bi bi-box-arrow-right display-6"></i>
          <div class="fw-semibold mt-2">Logout</div>
        </div>
      </a>
    </div>
  </div>
</div>

<footer class="mt-3">
  © 2025 LabKom 3 Jaringan • Dibuat setengah semangat oleh PLP ☕
</footer>
</body>
</html>
