<?php
// heavens/akun_assisten/aktivitas/tugas/detail.php
require_once __DIR__ . '/../../../fatman/functions.php';

// ===== AUTH (khusus asisten) =====
if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'assisten') {
    set_flash('Silakan login sebagai asisten terlebih dahulu.', 'warning');
    header('Location: /heavens/akun_assisten/login/');
    exit;
}

$pdo = db();
$assisten_id  = (int)($_SESSION['user_id'] ?? 0);
$praktikum_id = (int)($_GET['praktikum_id'] ?? 0);
$tugas_id     = (int)($_GET['tugas_id'] ?? 0);

if ($praktikum_id <= 0 || $tugas_id <= 0) {
    set_flash('Parameter tidak valid.', 'danger');
    header('Location: /heavens/akun_assisten/aktivitas/tugas/index.php');
    exit;
}

// ===== Validasi akses praktikum =====
$stmt = $pdo->prepare("
    SELECT p.id AS praktikum_id, m.mata_kuliah, p.kelas, p.shift, p.hari
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

// ===== Ambil info tugas =====
$stT = $pdo->prepare("SELECT * FROM tb_tugas WHERE id = ? AND praktikum_id = ? LIMIT 1");
$stT->execute([$tugas_id, $praktikum_id]);
$tugas = $stT->fetch();

if (!$tugas) {
    set_flash('Tugas tidak ditemukan untuk praktikum ini.', 'danger');
    header('Location: index.php?praktikum_id=' . $praktikum_id);
    exit;
}

// ===== Ambil daftar peserta & tugas yang dikumpulkan =====
$ps = $pdo->prepare("
    SELECT pk.id AS praktikan_id, pk.nama, pk.nim
    FROM tb_peserta p
    JOIN tb_praktikan pk ON pk.id = p.praktikan_id
    WHERE p.praktikum_id = ?
    ORDER BY pk.nim ASC
");
$ps->execute([$praktikum_id]);
$peserta = $ps->fetchAll();

$km = $pdo->prepare("
    SELECT praktikan_id, file_kumpul
    FROM tb_kumpul_tugas
    WHERE tugas_id = ? AND praktikum_id = ?
");
$km->execute([$tugas_id, $praktikum_id]);
$kumpul_map = [];
foreach ($km->fetchAll() as $row) {
    $kumpul_map[$row['praktikan_id']] = $row['file_kumpul'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tugas Masuk - Detail</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body{ background:#f4f6f9; }
    .header{ padding:20px; background:linear-gradient(135deg,#0d6efd,#2274ff); color:white; border-radius:12px; margin-top:15px; margin-bottom:20px; }
    .badge-belum{ background:#dc3545; }
    .badge-ada{ background:#198754; }
</style>
</head>
<body>

<div class="container">

    <!-- HEADER -->
    <div class="header shadow-sm d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1"><i class="bi bi-inbox"></i> Tugas Masuk</h4>
            <div><?= htmlspecialchars($info['mata_kuliah']) ?> • Kelas <?= htmlspecialchars($info['kelas']) ?> • Shift <?= htmlspecialchars($info['shift']) ?> • <?= htmlspecialchars($info['hari']) ?></div>
        </div>
        <a href="index.php?praktikum_id=<?= $praktikum_id ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <!-- INFO TUGAS -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <strong>Pertemuan:</strong> Ke-<?= $tugas['pertemuan_ke'] ?><br>
            <strong>Judul Tugas:</strong> <?= htmlspecialchars($tugas['judul']) ?><br>
            <strong>Deadline:</strong> <?= date('d M Y H:i', strtotime($tugas['deadline'])) ?>
        </div>
    </div>

    <!-- TABLE -->
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <?php if (empty($peserta)): ?>
                <div class="alert alert-info">Belum ada peserta praktikum ini.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Mahasiswa</th>
                                <th>NIM</th>
                                <th>File Tugas</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach ($peserta as $mhs): 
                                $file = $kumpul_map[$mhs['praktikan_id']] ?? null;
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($mhs['nama']) ?></td>
                                    <td><?= htmlspecialchars($mhs['nim']) ?></td>
                                    <td>
                                        <?php if ($file): ?>
                                            <a href="<?= htmlspecialchars($file) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted"> - </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($file): ?>
                                            <span class="badge badge-ada">✅ Mengumpulkan</span>
                                        <?php else: ?>
                                            <span class="badge badge-belum">❌ Belum Mengumpulkan</span>
                                        <?php endif; ?>
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
