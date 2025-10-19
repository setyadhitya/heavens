<?php
// heavens/akun_assisten/aktivitas/nilai/detail.php
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
    header('Location: /heavens/akun_assisten/aktivitas/index.php');
    exit;
}

// ===== Validasi akses praktikum oleh asisten =====
$stmt = $pdo->prepare("
    SELECT 
        p.id AS praktikum_id,
        m.mata_kuliah,
        p.kelas, p.shift, p.hari
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

// ===== Ambil info tugas dan validasi milik praktikum =====
$stT = $pdo->prepare("SELECT id, praktikum_id, pertemuan_ke, judul, deadline FROM tb_tugas WHERE id = ? LIMIT 1");
$stT->execute([$tugas_id]);
$tugas = $stT->fetch();

if (!$tugas || (int)$tugas['praktikum_id'] !== $praktikum_id) {
    set_flash('Tugas tidak ditemukan atau bukan milik praktikum ini.', 'danger');
    header('Location: /heavens/akun_assisten/aktivitas/nilai/index.php?praktikum_id=' . $praktikum_id);
    exit;
}

// ===== Handle submit nilai per baris =====
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Sesi tidak valid (CSRF). Muat ulang halaman.';
    } else {
        $row_praktikan_id = (int)($_POST['praktikan_id'] ?? 0);
        $nilai_in         = trim($_POST['nilai'] ?? '');
        $catatan_in       = trim($_POST['catatan'] ?? '');

        if ($row_praktikan_id <= 0) {
            $errors[] = 'Data mahasiswa tidak valid.';
        } else {
            // Pastikan mahasiswa ini memang peserta praktikum
            $cekPs = $pdo->prepare("SELECT COUNT(*) FROM tb_peserta WHERE praktikum_id = ? AND praktikan_id = ?");
            $cekPs->execute([$praktikum_id, $row_praktikan_id]);
            if ((int)$cekPs->fetchColumn() === 0) {
                $errors[] = 'Mahasiswa bukan peserta praktikum ini.';
            }
        }

        // Validasi nilai: boleh kosong (set NULL) atau angka 0..100
        $nilai_to_save = null; // default NULL
        if ($nilai_in !== '') {
            if (!ctype_digit($nilai_in)) {
                $errors[] = 'Nilai harus berupa angka bulat 0-100.';
            } else {
                $ival = (int)$nilai_in;
                if ($ival < 0 || $ival > 100) {
                    $errors[] = 'Nilai harus di antara 0 sampai 100.';
                } else {
                    $nilai_to_save = $ival;
                }
            }
        }

        if (empty($errors)) {
            try {
                // Cek apakah sudah ada baris kumpul tugas mahasiswa ini
                $cek = $pdo->prepare("SELECT id FROM tb_kumpul_tugas WHERE tugas_id = ? AND praktikan_id = ? LIMIT 1");
                $cek->execute([$tugas_id, $row_praktikan_id]);
                $exist = $cek->fetch();

                if ($exist) {
                    // UPDATE nilai & catatan
                    $up = $pdo->prepare("UPDATE tb_kumpul_tugas SET nilai = ?, catatan = ?, status = 'dinilai' WHERE id = ?");
                    $up->execute([$nilai_to_save, ($catatan_in !== '' ? $catatan_in : null), $exist['id']]);
                } else {
                    // INSERT baris baru meski belum mengumpulkan file (file_kumpul = NULL)
                    $ins = $pdo->prepare("
                        INSERT INTO tb_kumpul_tugas (tugas_id, praktikan_id, praktikum_id, file_kumpul, nilai, catatan, status)
                        VALUES (?,?,?,?,?,?, 'dinilai')
                    ");
                    $ins->execute([
                        $tugas_id,
                        $row_praktikan_id,
                        $praktikum_id,
                        null,
                        $nilai_to_save,
                        ($catatan_in !== '' ? $catatan_in : null)
                    ]);
                }

                $success = 'Nilai berhasil disimpan.';
            } catch (Exception $e) {
                $errors[] = 'Gagal menyimpan nilai. Silakan coba lagi.';
            }
        }
    }
}

// ===== Ambil daftar peserta dan (jika ada) datanya di tb_kumpul_tugas =====
$stPes = $pdo->prepare("
    SELECT pk.id AS praktikan_id, pk.nama, pk.nim
    FROM tb_peserta ps
    JOIN tb_praktikan pk ON pk.id = ps.praktikan_id
    WHERE ps.praktikum_id = ?
    ORDER BY pk.nim ASC
");
$stPes->execute([$praktikum_id]);
$peserta = $stPes->fetchAll();

// Map kumpul_tugas per praktikan untuk tugas ini
$stKt = $pdo->prepare("
    SELECT praktikan_id, file_kumpul, nilai, catatan
    FROM tb_kumpul_tugas
    WHERE tugas_id = ? AND praktikum_id = ?
");
$stKt->execute([$tugas_id, $praktikum_id]);
$kumpul_map = [];
foreach ($stKt->fetchAll() as $row) {
    $kumpul_map[(int)$row['praktikan_id']] = $row;
}

// Helper format tanggal
function e_dt($dt) {
    if (!$dt) return '-';
    $ts = strtotime($dt);
    if ($ts === false) return e($dt);
    return date('d M Y H:i', $ts);
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Nilai Tugas – Detail</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{background:#f4f6f9;}
  .header{padding:22px;background:linear-gradient(135deg,#0d6efd,#2274ff);color:#fff;border-radius:12px;margin:20px 0 16px;}
  .card{border-radius:14px;}
  .small-muted{color:#6c757d;font-size:.9rem;}
  .badge-belum{background:#dc3545; color:#fff;}
  input[type=number]{width:90px;}
  input[type=text]{max-width:260px;}
</style>
</head>
<body>

<div class="container">
  <div class="header shadow-sm d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-1"><i class="bi bi-pencil-square"></i> Penilaian Tugas</h4>
      <div class="small">
        <?= e($info['mata_kuliah']); ?> • Kelas <?= e($info['kelas']); ?> • Shift <?= e($info['shift']); ?> • <?= e($info['hari']); ?>
      </div>
      <div class="small mt-1">
        <strong>Pertemuan:</strong> Ke-<?= (int)$tugas['pertemuan_ke'] ?> • 
        <strong>Judul:</strong> <?= e($tugas['judul']) ?> • 
        <strong>Deadline:</strong> <?= e_dt($tugas['deadline']) ?>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="/heavens/akun_assisten/aktivitas/nilai/index.php?praktikum_id=<?= (int)$info['praktikum_id'] ?>" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-4">

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $er): ?>
              <li><?= e($er) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
      <?php endif; ?>

      <?php if (empty($peserta)): ?>
        <div class="alert alert-info mb-0">Belum ada peserta di praktikum ini.</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:60px;">No</th>
              <th>Nama</th>
              <th style="width:140px;">NIM</th>
              <th style="width:160px;">File Tugas</th>
              <th style="width:120px;">Nilai (0–100)</th>
              <th style="min-width:220px;">Catatan</th>
              <th style="width:120px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; foreach ($peserta as $mhs): 
                $pid = (int)$mhs['praktikan_id'];
                $km  = $kumpul_map[$pid] ?? null;
                $file = $km['file_kumpul'] ?? null;
                $nilai_cur = $km['nilai'] ?? null;
                $catatan_cur = $km['catatan'] ?? '';
            ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= e($mhs['nama']) ?></td>
              <td><?= e($mhs['nim']) ?></td>
              <td>
                <?php if (!empty($file)): ?>
                  <a href="<?= e($file) ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                    <i class="bi bi-download"></i> Download
                  </a>
                <?php else: ?>
                  <span class="badge badge-belum">BELUM MENGUMPUL</span>
                <?php endif; ?>
              </td>
              <td>
                <form method="post" class="d-flex align-items-center gap-2">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                  <input type="hidden" name="praktikan_id" value="<?= $pid ?>">
                  <input type="number" name="nilai" class="form-control form-control-sm" min="0" max="100" 
                         value="<?= ($nilai_cur !== null ? (int)$nilai_cur : '') ?>" placeholder="0-100">
              </td>
              <td>
                  <input type="text" name="catatan" class="form-control form-control-sm" maxlength="255"
                         value="<?= e($catatan_cur) ?>" placeholder="(opsional)">
              </td>
              <td>
                  <button class="btn btn-sm btn-primary">
                    <i class="bi bi-save"></i> Simpan
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

</body>
</html>
