<?php
// heavens/akun_assisten/aktivitas/tugas/index.php
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

// ===== Ambil daftar tugas =====
$tugas_stmt = $pdo->prepare("
    SELECT id, pertemuan_ke, judul, deadline
    FROM tb_tugas
    WHERE praktikum_id = ?
    ORDER BY pertemuan_ke ASC
");
$tugas_stmt->execute([$praktikum_id]);
$tugas_list = $tugas_stmt->fetchAll();

// ===== Total peserta untuk progress =====
$total_peserta_stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_peserta WHERE praktikum_id = ?");
$total_peserta_stmt->execute([$praktikum_id]);
$total_peserta = (int)$total_peserta_stmt->fetchColumn();

// ===== Fungsi format tanggal =====
function e_dt($dt) {
    return $dt ? date('d M Y H:i', strtotime($dt)) : '-';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Tugas Masuk</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body { background: #f4f6f9; }
    .header { padding: 20px; background: linear-gradient(135deg,#0d6efd,#2274ff); color: white; border-radius: 12px; margin-top: 15px; margin-bottom: 20px; }
    .badge-count { font-size: .9rem; }
</style>
</head>
<body>

<div class="container">
    <div class="header d-flex justify-content-between align-items-center shadow-sm">
        <div>
            <h4 class="mb-1"><i class="bi bi-inbox"></i> Daftar Tugas Masuk</h4>
            <div class="small"><?= e($info['mata_kuliah']) ?> • Kelas <?= e($info['kelas']) ?> • Shift <?= e($info['shift']) ?> • <?= e($info['hari']) ?></div>
        </div>
        <a href="/heavens/akun_assisten/aktivitas/detail.php?praktikum_id=<?= $praktikum_id ?>" class="btn btn-light btn-sm">
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
                                <th>Pertemuan</th>
                                <th>Judul Tugas</th>
                                <th>Deadline</th>
                                <th>Masuk</th>
                                <th style="width:240px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tugas_list as $t): 
                                $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_kumpul_tugas WHERE tugas_id = ? AND file_kumpul IS NOT NULL");
                                $count_stmt->execute([$t['id']]);
                                $jumlah_masuk = (int)$count_stmt->fetchColumn();
                            ?>
                            <tr>
                                <td><strong>Ke-<?= $t['pertemuan_ke'] ?></strong></td>
                                <td><?= e($t['judul']) ?></td>
                                <td><?= e_dt($t['deadline']) ?></td>
                                <td><span class="badge bg-primary badge-count"><?= $jumlah_masuk ?>/<?= $total_peserta ?> Mahasiswa</span></td>
                                <td>
                                    <a href="detail.php?tugas_id=<?= $t['id'] ?>&praktikum_id=<?= $praktikum_id ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                    <a href="download_zip.php?tugas_id=<?= $t['id'] ?>&praktikum_id=<?= $praktikum_id ?>" class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-file-zip"></i> Download ZIP
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>

</body>
</html>
