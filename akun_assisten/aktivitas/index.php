<?php
// heavens/akun_assisten/aktivitas/index.php
$currentPage = 'aktivitluarassisten';
include __DIR__ . '/../../components/helper_bubble.php';
require_once __DIR__ . '/../../fatman/functions.php';

// ===== AUTH (khusus assisten) =====
if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'assisten') {
    set_flash('Silakan login sebagai assisten terlebih dahulu.', 'warning');
    header('Location: /heavens/akun_assisten/login/');
    exit;
}

$pdo = db();
$assisten_id = (int)($_SESSION['user_id'] ?? 0);

// ===== Ambil daftar praktikum yang diampu assisten =====
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
    WHERE ap.assisten_id = ?
    ORDER BY m.mata_kuliah ASC
");
$stmt->execute([$assisten_id]);
$praktikum_list = $stmt->fetchAll();

// ===== Hitung total tugas dan total presensi per praktikum =====
function get_total_tugas($pdo, $praktikum_id) {
    $q = $pdo->prepare("SELECT COUNT(*) FROM tb_tugas WHERE praktikum_id = ?");
    $q->execute([$praktikum_id]);
    return (int)$q->fetchColumn();
}

function get_total_pertemuan($pdo, $praktikum_id) {
    $q = $pdo->prepare("SELECT COUNT(DISTINCT pertemuan_ke) FROM tb_kode_presensi WHERE praktikum_id = ?");
    $q->execute([$praktikum_id]);
    return (int)$q->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Aktivitas Assisten</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body { background: #f4f6f9; }
    .header { padding: 20px; background: linear-gradient(135deg, #0d6efd, #2274ff); color: white; border-radius: 12px; margin-top: 15px; margin-bottom: 20px; }
    .card-praktikum { border-radius: 12px; border: none; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    .card-praktikum:hover { transform: translateY(-3px); transition: 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
    .info-label { font-size: .88rem; color: #6c757d; }
</style>
</head>
<body>

<div class="container">
    <div class="header shadow-sm d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1"><i class="bi bi-collection-play"></i> Aktivitas Praktikum Saya</h4>
            <div class="small">Kelola presensi, tugas, dan penilaian praktikum Anda.</div>
        </div>
        <a href="/heavens/akun_assisten/" class="btn btn-light btn-sm"><i class="bi bi-house"></i> Dashboard</a>
    </div>

    <?php if (empty($praktikum_list)): ?>
        <div class="alert alert-warning">Anda belum memiliki praktikum yang diampu.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($praktikum_list as $p): ?>
                <?php
                    $total_tugas = get_total_tugas($pdo, $p['praktikum_id']);
                    $total_presensi = get_total_pertemuan($pdo, $p['praktikum_id']);
                ?>
                <div class="col-md-6">
                    <div class="card card-praktikum">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><i class="bi bi-journal-text"></i> <?= htmlspecialchars($p['mata_kuliah']) ?></h5>
                            <div class="mb-2 info-label">
                                Kelas: <strong><?= htmlspecialchars($p['kelas']) ?></strong> • 
                                Shift: <strong><?= htmlspecialchars($p['shift']) ?></strong> • 
                                <?= htmlspecialchars($p['hari']) ?>
                            </div>
                            <div class="mb-3">
                                📚 Tugas: <strong><?= $total_tugas ?></strong> &nbsp;&nbsp;&nbsp; ✅ Pertemuan: <strong><?= $total_presensi ?></strong>
                            </div>
                            <a href="detail.php?praktikum_id=<?= $p['praktikum_id'] ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-gear"></i> Kelola Aktivitas
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
