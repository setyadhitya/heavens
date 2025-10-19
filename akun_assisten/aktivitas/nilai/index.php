<?php
// heavens/akun_assisten/aktivitas/nilai/index.php
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

if ($praktikum_id <= 0) {
    set_flash('Praktikum tidak valid.', 'danger');
    header('Location: /heavens/akun_assisten/aktivitas/index.php');
    exit;
}

// ===== Validasi akses praktikum oleh assisten =====
$stmt = $pdo->prepare("
    SELECT 
        p.id AS praktikum_id,
        m.mata_kuliah,
        p.kelas,
        p.shift,
        p.hari
    FROM tb_assisten_praktikum ap
    JOIN tb_praktikum p ON p.id = ap.praktikum_id
    JOIN tb_matkul m ON m.id = p.mata_kuliah
    WHERE ap.assisten_id = ? AND p.id = ?
    LIMIT 1
");
$stmt->execute([$assisten_id, $praktikum_id]);
$info = $stmt->fetch();

if (!$info) {
    set_flash('Anda tidak memiliki akses ke praktikum ini.', 'danger');
    header('Location: /heavens/akun_assisten/aktivitas/index.php');
    exit;
}

// ===== Total peserta praktikum (penyebut progress) =====
$stTotal = $pdo->prepare("SELECT COUNT(*) FROM tb_peserta WHERE praktikum_id = ?");
$stTotal->execute([$praktikum_id]);
$total_peserta = (int)$stTotal->fetchColumn();

// ===== Ambil daftar tugas untuk praktikum ini (urut pertemuan 1->akhir) =====
$stTugas = $pdo->prepare("
    SELECT id, pertemuan_ke, judul, deadline
    FROM tb_tugas
    WHERE praktikum_id = ?
    ORDER BY pertemuan_ke ASC, id ASC
");
$stTugas->execute([$praktikum_id]);
$tugas_list = $stTugas->fetchAll();

// ===== Siapkan statement hitung progress nilai per tugas =====
// Dinilai = baris di tb_kumpul_tugas dengan nilai TIDAK NULL untuk tugas tsb
$stDinilai = $pdo->prepare("SELECT COUNT(*) FROM tb_kumpul_tugas WHERE tugas_id = ? AND nilai IS NOT NULL");

// Helper format tanggal
function e_dt($dt) {
    if (!$dt) return '-';
    $ts = strtotime($dt);
    if ($ts === false) return e($dt);
    return date('d M Y H:i', $ts);
}

// Helper badge progress
function progress_badge(int $done, int $total): string {
    if ($total <= 0) {
        // tidak ada peserta → tampil netral
        return '<span class="badge bg-secondary">0/0</span>';
    }
    if ($done <= 0) {
        return '<span class="badge bg-danger">' . $done . '/' . $total . ' Belum dinilai</span>';
    }
    if ($done >= $total) {
        return '<span class="badge bg-success">' . $done . '/' . $total . ' Selesai</span>';
    }
    return '<span class="badge bg-warning text-dark">' . $done . '/' . $total . ' Proses</span>';
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Penilaian Tugas</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{background:#f4f6f9;}
  .header{padding:22px;background:linear-gradient(135deg,#0d6efd,#2274ff);color:#fff;border-radius:12px;margin:20px 0 16px;}
  .card{border-radius:14px;}
  .small-muted{color:#6c757d;font-size:.9rem;}
</style>
</head>
<body>

<div class="container">
  <div class="header shadow-sm d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-1"><i class="bi bi-clipboard-check"></i> Penilaian Tugas</h4>
      <div class="small">
        <?= e($info['mata_kuliah']); ?> • Kelas <?= e($info['kelas']); ?> • Shift <?= e($info['shift']); ?> • <?= e($info['hari']); ?>
      </div>
      <div class="small mt-1">Total Peserta: <strong><?= (int)$total_peserta ?></strong></div>
    </div>
    <a href="/heavens/akun_assisten/aktivitas/detail.php?praktikum_id=<?= (int)$info['praktikum_id'] ?>" class="btn btn-light btn-sm">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-4">
      <?php if (empty($tugas_list)): ?>
        <div class="alert alert-info mb-0">Belum ada tugas untuk praktikum ini.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:100px;">Pertemuan</th>
                <th>Judul Tugas</th>
                <th style="width:200px;">Deadline</th>
                <th style="width:220px;">Progres Dinilai</th>
                <th style="width:140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tugas_list as $t): 
                    $stDinilai->execute([$t['id']]);
                    $done = (int)$stDinilai->fetchColumn();
                    $badge = progress_badge($done, $total_peserta);
              ?>
              <tr>
                <td><span class="fw-semibold">Ke-<?= (int)$t['pertemuan_ke'] ?></span></td>
                <td><?= e($t['judul']) ?></td>
                <td><?= e_dt($t['deadline']) ?></td>
                <td><?= $badge ?></td>
                <td>
                  <a class="btn btn-primary btn-sm" href="detail.php?tugas_id=<?= (int)$t['id'] ?>&praktikum_id=<?= (int)$info['praktikum_id'] ?>">
                    <i class="bi bi-pencil-square"></i> Nilai Tugas
                  </a>
                </td>
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
