<?php
$currentPage = 'usermoduldetail'; // atau 'detail_modul', 'home', dll sesuai tb_helper.halaman
include __DIR__ . '/../components/helper_bubble.php';
require_once __DIR__ . '/../fatman/functions.php';
$pdo = db();

$id_modul = (int)($_GET['id_modul'] ?? 0);
if ($id_modul <= 0) {
    die('ID modul tidak valid.');
}

// Ambil data modul
$stmt = $pdo->prepare("SELECT * FROM modul WHERE id_modul = ?");
$stmt->execute([$id_modul]);
$modul = $stmt->fetch();

if (!$modul) {
    die('Modul tidak ditemukan.');
}

// Ambil semua section
$sec = $pdo->prepare("SELECT * FROM modul_section WHERE id_modul = ? ORDER BY urutan ASC");
$sec->execute([$id_modul]);
$sections = $sec->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= e($modul['judul_modul']) ?> - Modul Praktikum</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .modul-header { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 3px 6px rgba(0,0,0,.1); margin-bottom: 20px; }
        .section-box { background: white; padding: 18px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid #0d6efd; box-shadow: 0 2px 5px rgba(0,0,0,.1); }
        .section-title { font-size: 18px; font-weight: bold; color: #0d6efd; display: flex; align-items: center; justify-content: space-between; }
        .btn-img-view { font-size: 14px; }
        .no-img { color: red; font-size: 30px; }
    </style>
</head>
<body>

<div class="container py-4">
    <a href="index.php" class="btn btn-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Kembali ke Modul</a>

    <!-- HEADER MODUL -->
    <div class="modul-header">
        <h2 class="fw-bold mb-1"><?= e($modul['judul_modul']) ?></h2>
        <p class="text-muted mb-2"><?= e($modul['mata_kuliah']) ?></p>

        <?php if (!empty($modul['gambar_modul'])): ?>
            <img src="../guwambar/modul/<?= e($modul['gambar_modul']) ?>" style="max-width:200px;border-radius:6px;border:1px solid #ddd;">
        <?php endif; ?>

        <p class="mt-2"><?= $modul['deskripsi_singkat'] ? nl2br(e($modul['deskripsi_singkat'])) : '<i>(Tidak ada deskripsi)</i>' ?></p>
    </div>

    <!-- SECTION LIST -->
    <?php if (empty($sections)): ?>
        <div class="alert alert-warning">Belum ada materi section pada modul ini.</div>
    <?php else: ?>
        <?php foreach ($sections as $s): ?>
            <div class="section-box">
                <div class="section-title">
                    <?= e($s['judul_section']) ?>

                    <?php if (!empty($s['gambar_section'])): ?>
                        <button class="btn btn-outline-primary btn-sm btn-img-view" data-bs-toggle="modal" data-bs-target="#imgModal<?= $s['id_section'] ?>">
                            <i class="bi bi-image"></i> Lihat Gambar
                        </button>
                    <?php else: ?>
                        <i class="bi bi-x-circle no-img"></i>
                    <?php endif; ?>
                </div>

                <p class="mt-2"><?= nl2br(e($s['isi_section'])) ?></p>
            </div>

            <!-- Modal Gambar -->
            <?php if (!empty($s['gambar_section'])): ?>
                <div class="modal fade" id="imgModal<?= $s['id_section'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <img src="../guwambar/modul/section/<?= e($s['gambar_section']) ?>" class="w-100">
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
