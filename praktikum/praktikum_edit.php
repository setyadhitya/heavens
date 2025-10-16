<?php
require_once __DIR__ . '/../functions.php';
require_login_and_redirect();
include '../navbar.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $mysqli->prepare("SELECT * FROM tb_praktikum WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data tidak ditemukan.");

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    $errors[] = "CSRF token tidak valid.";
  } else {
    $mata_kuliah = trim($_POST['mata_kuliah']);
    $jurusan = trim($_POST['jurusan']);
    $kelas = trim($_POST['kelas']);
    $semester = trim($_POST['semester']);
    $hari = trim($_POST['hari']);
    $jam_mulai = $_POST['jam_mulai'];
    $jam_ahir = $_POST['jam_ahir'];
    $shift = trim($_POST['shift']);
    $assisten = trim($_POST['assisten']);
    $catatan = trim($_POST['catatan']);

    $stmt = $mysqli->prepare("UPDATE tb_praktikum SET mata_kuliah=?, jurusan=?, kelas=?, semester=?, hari=?, jam_mulai=?, jam_ahir=?, shift=?, assisten=?, catatan=? WHERE id=?");
    $stmt->bind_param("ssssssssssi", $mata_kuliah, $jurusan, $kelas, $semester, $hari, $jam_mulai, $jam_ahir, $shift, $assisten, $catatan, $id);
    if ($stmt->execute()) {
      $success = "Data berhasil diperbarui.";
      // refresh data
      $stmt = $mysqli->prepare("SELECT * FROM tb_praktikum WHERE id=?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $data = $stmt->get_result()->fetch_assoc();
    } else {
      $errors[] = "Gagal memperbarui data.";
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Edit Praktikum</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <div class="card shadow-sm">
    <div class="card-header bg-warning text-white">
      <h5 class="mb-0">Edit Data Praktikum</h5>
    </div>
    <div class="card-body">
      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?php echo e($err); ?></div>
      <?php endforeach; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Mata Kuliah</label>
            <input type="text" name="mata_kuliah" class="form-control" value="<?php echo e($data['mata_kuliah']); ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Jurusan</label>
            <input type="text" name="jurusan" class="form-control" value="<?php echo e($data['jurusan']); ?>">
          </div>
          <div class="col-md-1">
            <label class="form-label">Kelas</label>
            <input type="text" name="kelas" class="form-control" value="<?php echo e($data['kelas']); ?>">
          </div>
          <div class="col-md-1">
            <label class="form-label">Semester</label>
            <input type="text" name="semester" class="form-control" value="<?php echo e($data['semester']); ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Hari</label>
            <input type="text" name="hari" class="form-control" value="<?php echo e($data['hari']); ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Jam Mulai</label>
            <input type="time" name="jam_mulai" class="form-control" value="<?php echo e($data['jam_mulai']); ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Jam Akhir</label>
            <input type="time" name="jam_ahir" class="form-control" value="<?php echo e($data['jam_ahir']); ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Shift</label>
            <input type="text" name="shift" class="form-control" value="<?php echo e($data['shift']); ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Asisten</label>
            <input type="text" name="assisten" class="form-control" value="<?php echo e($data['assisten']); ?>">
          </div>
          <div class="col-md-12">
            <label class="form-label">Catatan</label>
            <textarea name="catatan" class="form-control" rows="2"><?php echo e($data['catatan']); ?></textarea>
          </div>
        </div>
        <div class="mt-3 d-flex justify-content-between">
          <a href="praktikum.php" class="btn btn-secondary">Kembali</a>
          <button type="submit" class="btn btn-warning">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
