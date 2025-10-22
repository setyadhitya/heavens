<?php
require_once __DIR__ . '/../fatman/functions.php';
$pdo = db();

// Ambil daftar modul
$stmt = $pdo->query("SELECT * FROM modul ORDER BY id_modul DESC");
$moduls = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Modul Praktikum</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f5f7fa; }
    .modul-card { background: white; border-radius: 10px; padding: 15px; margin-bottom: 15px; box-shadow: 0 3px 6px rgba(0,0,0,.1); }
    .modul-thumb { width: 180px; height: 120px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
    .modul-title { font-size: 1.3rem; font-weight: 600; }
    .modul-desc { color: #555; font-size: .9rem; }
  </style>
</head>
<body>

<div class="container py-4">
  <h2 class="mb-4 text-center fw-bold">📚 Daftar Modul Praktikum</h2>
    <a href="index.php" class="btn btn-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Kembali ke Modul</a>

  <?php if (empty($moduls)): ?>
    <div class="alert alert-warning text-center">Belum ada modul yang tersedia.</div>
  <?php else: ?>
    <?php foreach ($moduls as $m): ?>
      <div class="modul-card d-flex gap-3">
        <div>
          <?php if (!empty($m['gambar_modul'])): ?>
            <img src="../guwambar/modul/<?= e($m['gambar_modul']) ?>" class="modul-thumb">
          <?php else: ?>
            <div class="d-flex align-items-center justify-content-center bg-light modul-thumb text-muted">
              <i class="bi bi-image text-danger fs-1"></i>
            </div>
          <?php endif; ?>
        </div>
        <div class="flex-grow-1">
          <div class="modul-title"><?= e($m['judul_modul']) ?></div>
          <div class="text-muted"><?= e($m['mata_kuliah']) ?></div>
          <p class="modul-desc">
            <?= $m['deskripsi_singkat'] ? e(substr($m['deskripsi_singkat'], 0, 170)) . '...' : '<i>(Tidak ada deskripsi)</i>' ?>
          </p>
          <a href="detail.php?id_modul=<?= $m['id_modul'] ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-eye"></i> Lihat Modul
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>
