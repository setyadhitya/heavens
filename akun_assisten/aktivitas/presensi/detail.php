<?php
// heavens/akun_assisten/aktivitas/presensi/detail.php
require_once __DIR__ . '/../../../fatman/functions.php';

// ===== AUTH (khusus assisten) =====
if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'assisten') {
    set_flash('Silakan login sebagai assisten terlebih dahulu.', 'warning');
    header('Location: /heavens/akun_assisten/login/');
    exit;
}

$pdo = db();
$assisten_id  = (int)($_SESSION['user_id'] ?? 0);
$praktikum_id = (int)($_GET['praktikum_id'] ?? 0);
$pertemuan    = (int)($_GET['pertemuan'] ?? 0);

if ($praktikum_id <= 0 || $pertemuan <= 0) {
    set_flash('Parameter tidak valid.', 'danger');
    header('Location: /heavens/akun_assisten/aktivitas/index.php');
    exit;
}

// ===== Validasi praktikum milik assisten =====
$stmt = $pdo->prepare("
    SELECT p.id, m.mata_kuliah, p.kelas, p.shift, p.hari
    FROM tb_assisten_praktikum ap
    JOIN tb_praktikum p ON p.id = ap.praktikum_id
    JOIN tb_matkul m ON m.id = p.mata_kuliah
    WHERE ap.assisten_id = ? AND ap.praktikum_id = ?
    LIMIT 1
");
$stmt->execute([$assisten_id, $praktikum_id]);
$info = $stmt->fetch();

if (!$info) {
    set_flash('Anda tidak memiliki akses ke praktikum ini.', 'danger');
    header('Location: /heavens/akun_assisten/aktivitas/index.php');
    exit;
}

// ===== Ambil daftar peserta praktikum =====
$ps = $pdo->prepare("
    SELECT pk.id AS praktikan_id, pk.nama, pk.nim
    FROM tb_peserta p
    JOIN tb_praktikan pk ON pk.id = p.praktikan_id
    WHERE p.praktikum_id = ?
    ORDER BY pk.nim ASC
");
$ps->execute([$praktikum_id]);
$peserta = $ps->fetchAll();

// ===== Ambil data presensi per pertemuan =====
$pr = $pdo->prepare("
    SELECT praktikan_id, status, lokasi, created_at
    FROM tb_presensi
    WHERE praktikum_id = ? AND pertemuan_ke = ?
");
$pr->execute([$praktikum_id, $pertemuan]);
$presensi_map = [];
foreach ($pr->fetchAll() as $row) {
    $presensi_map[(int)$row['praktikan_id']] = $row;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Detail Presensi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{background:#f4f6f9;}
  .header{padding:20px;background:linear-gradient(135deg,#0d6efd,#2274ff);color:white;border-radius:12px;margin:20px 0 16px;}
  .badge-hadir{background:#28a745;padding:5px 10px;border-radius:6px;color:white;font-weight:600;}
  .badge-tidak{background:#dc3545;padding:5px 10px;border-radius:6px;color:white;font-weight:600;}
</style>
</head>
<body>

<div class="container">
  <div class="header shadow-sm d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-1"><i class="bi bi-list-check"></i> Detail Presensi Pertemuan <?= $pertemuan ?></h4>
      <div><?= e($info['mata_kuliah']) ?> • Kelas <?= e($info['kelas']) ?> • Shift <?= e($info['shift']) ?> • <?= e($info['hari']) ?></div>
    </div>
    <a href="index.php?praktikum_id=<?= $praktikum_id ?>" class="btn btn-light btn-sm">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body p-4">
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead class="table-light">
            <tr>
              <th>No</th>
              <th>Nama Mahasiswa</th>
              <th>NIM</th>
              <th>Status Kehadiran</th>
              <th>Lokasi</th>
              <th>Waktu Presensi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($peserta)): ?>
              <tr><td colspan="6" class="text-center text-muted">Belum ada peserta di praktikum ini.</td></tr>
            <?php else: $no=1; foreach ($peserta as $p): 
              $pres = $presensi_map[$p['praktikan_id']] ?? null;
              $hadir = $pres && $pres['status'] === 'Hadir';
            ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= e($p['nama']) ?></td>
                <td><?= e($p['nim']) ?></td>
                <td>
                  <?= $hadir ? '<span class="badge-hadir">🟢 HADIR</span>' : '<span class="badge-tidak">🔴 TIDAK HADIR</span>' ?>
                </td>
                <td>
                  <?php if ($hadir && !empty($pres['lokasi'])): ?>
                    <a href="https://www.google.com/maps?q=<?= e($pres['lokasi']) ?>" target="_blank">
                      📍 <?= e($pres['lokasi']) ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Tidak ada</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?= $hadir && !empty($pres['created_at']) ? date('d M Y H:i', strtotime($pres['created_at'])) : '<span class="text-muted">Tidak ada</span>' ?>
                </td>
              </tr>
            <?php endforeach; endif ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</body>
</html>
