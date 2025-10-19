<?php
// heavens/akun_assisten/aktivitas/presensi/index.php
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

// ===== Total peserta praktikum =====
$stPes = $pdo->prepare("SELECT COUNT(*) FROM tb_peserta WHERE praktikum_id = ?");
$stPes->execute([$praktikum_id]);
$total_peserta = (int)$stPes->fetchColumn();

// ===== Ambil daftar pertemuan dari tb_kode_presensi (urut ASC) =====
// Catatan: kombinasi (praktikum_id, pertemuan_ke) unik by design
$stKp = $pdo->prepare("
    SELECT id, pertemuan_ke, materi, status, created_at
    FROM tb_kode_presensi
    WHERE praktikum_id = ?
    ORDER BY pertemuan_ke ASC
");
$stKp->execute([$praktikum_id]);
$pertemuan_rows = $stKp->fetchAll();

// Buat map pertemuan -> data
$pertemuan_map = [];
foreach ($pertemuan_rows as $row) {
    $pertemuan_map[(int)$row['pertemuan_ke']] = $row;
}

// Tentukan max pertemuan dari data yang ada (untuk tabel rapih 1..max)
$max_pertemuan = !empty($pertemuan_rows) ? (int)max(array_column($pertemuan_rows, 'pertemuan_ke')) : 0;

// ===== Helper hitung hadir untuk per-pertemuan =====
$stHadir = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tb_presensi 
    WHERE praktikum_id = ? AND pertemuan_ke = ? AND status = 'Hadir'
");

function badge_status_presensi(?string $statusKode, bool $exists): string {
    // $statusKode: 'aktif' / 'expired' / null
    // $exists: apakah pertemuan punya kode
    if (!$exists) {
        return '<span class="badge bg-danger">Belum dimulai</span>';
    }
    if ($statusKode === 'aktif') {
        return '<span class="badge bg-warning text-dark">Masih dibuka</span>';
    }
    // selain aktif, anggap selesai/ditutup
    return '<span class="badge bg-success">Selesai</span>';
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Presensi Praktikum</title>
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
      <h4 class="mb-1"><i class="bi bi-people-check"></i> Presensi Praktikum</h4>
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
      <?php if ($max_pertemuan === 0): ?>
        <div class="alert alert-info mb-0">
          Belum ada pertemuan/kehadiran tercatat untuk praktikum ini.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:100px;">Pertemuan</th>
                <th>Materi</th>
                <th style="width:120px;">Hadir</th>
                <th style="width:140px;">Tidak Hadir</th>
                <th style="width:160px;">Status Presensi</th>
                <th style="width:220px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php for ($i = 1; $i <= $max_pertemuan; $i++): 
                    $row = $pertemuan_map[$i] ?? null;
                    $materi = $row['materi'] ?? '-';
                    $statusKode = $row['status'] ?? null;
                    // Hitung hadir jika ada pertemuan
                    $hadir = 0;
                    if ($row) {
                        $stHadir->execute([$praktikum_id, $i]);
                        $hadir = (int)$stHadir->fetchColumn();
                    }
                    $tidak_hadir = max(0, $total_peserta - $hadir);
                    $statusBadge = badge_status_presensi($statusKode, (bool)$row);
              ?>
              <tr>
                <td><span class="fw-semibold">Ke-<?= $i ?></span></td>
                <td><?= e($materi) ?></td>
                <td><span class="badge bg-success-subtle text-success border border-success"><?= $hadir ?></span></td>
                <td><span class="badge bg-secondary-subtle text-secondary border border-secondary"><?= $tidak_hadir ?></span></td>
                <td><?= $statusBadge ?></td>
                <td>
                  <?php if ($row): ?>
                    <a href="detail.php?praktikum_id=<?= (int)$praktikum_id ?>&pertemuan=<?= $i ?>" class="btn btn-outline-primary btn-sm">
                      <i class="bi bi-eye"></i> Lihat
                    </a>
                    <a href="rekap_pdf.php?praktikum_id=<?= (int)$praktikum_id ?>&pertemuan=<?= $i ?>" class="btn btn-outline-danger btn-sm">
                      <i class="bi bi-filetype-pdf"></i> Download PDF
                    </a>
                  <?php else: ?>
                    <button class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-eye"></i> Lihat</button>
                    <button class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-filetype-pdf"></i> Download PDF</button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
