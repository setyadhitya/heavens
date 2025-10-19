<?php
// heavens/akun_assisten/aktivitas/detail.php
require_once __DIR__ . '/../../fatman/functions.php';

// ===== AUTH (khusus assisten) =====
if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'assisten') {
    set_flash('Silakan login sebagai assisten terlebih dahulu.', 'warning');
    header('Location: /heavens/akun_assisten/login/');
    exit;
}

$pdo = db();
$assisten_id = (int)($_SESSION['user_id'] ?? 0);
$praktikum_id = (int)($_GET['praktikum_id'] ?? 0);

if ($praktikum_id <= 0) {
    set_flash('Praktikum tidak valid.', 'danger');
    header('Location: /heavens/akun_assisten/aktivitas/index.php');
    exit;
}

// ===== Validasi apakah assisten mengampu praktikum ini =====
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
$data = $stmt->fetch();

if (!$data) {
    set_flash('Anda tidak memiliki akses ke praktikum ini.', 'danger');
    header('Location: /heavens/akun_assisten/aktivitas/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Aktivitas Praktikum</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body { background: #f4f6f9; }
    .header { padding: 20px; background: linear-gradient(135deg, #0d6efd, #2274ff); color: white; border-radius: 12px; margin-top: 15px; margin-bottom: 20px; }
    .menu-card { text-align: center; border-radius: 14px; padding: 25px; transition: .2s; }
    .menu-card:hover { transform: translateY(-4px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
    .icon { font-size: 2.5rem; margin-bottom: 10px; color: #0d6efd; }
</style>
</head>
<body>

<div class="container">
    <div class="header shadow-sm d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1"><i class="bi bi-collection"></i> Aktivitas Praktikum</h4>
            <div class="small">
                <?= htmlspecialchars($data['mata_kuliah']) ?> • Kelas <?= htmlspecialchars($data['kelas']) ?> • Shift <?= htmlspecialchars($data['shift']) ?> • <?= htmlspecialchars($data['hari']) ?>
            </div>
        </div>
        <a href="/heavens/akun_assisten/aktivitas/index.php" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Presensi -->
        <div class="col-md-4">
            <div class="card menu-card shadow-sm">
                <div class="icon"><i class="bi bi-people-check"></i></div>
                <h5>Kelola Presensi</h5>
                <p class="text-muted small">Lihat & pantau kehadiran praktikan</p>
                <a href="presensi/index.php?praktikum_id=<?= $data['praktikum_id'] ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-right-circle"></i> Buka
                </a>
            </div>
        </div>

        <!-- Tugas Masuk -->
        <div class="col-md-4">
            <div class="card menu-card shadow-sm">
                <div class="icon"><i class="bi bi-inbox"></i></div>
                <h5>Tugas Masuk</h5>
                <p class="text-muted small">Lihat tugas yang dikumpulkan mahasiswa</p>
                <a href="tugas/index.php?praktikum_id=<?= $data['praktikum_id'] ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-right-circle"></i> Buka
                </a>
            </div>
        </div>

        <!-- Nilai Tugas -->
        <div class="col-md-4">
            <div class="card menu-card shadow-sm">
                <div class="icon"><i class="bi bi-clipboard-check"></i></div>
                <h5>Penilaian Tugas</h5>
                <p class="text-muted small">Nilai tugas mahasiswa dengan mudah</p>
                <a href="nilai/index.php?praktikum_id=<?= $data['praktikum_id'] ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-right-circle"></i> Buka
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
