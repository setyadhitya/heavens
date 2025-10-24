<?php
// heavens/akun_assisten/beri_tugas/index.php
$currentPage = 'beritugas';
include __DIR__ . '/../../components/helper_bubble.php';
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../fatman/functions.php';

// ===== AUTH (login assisten) =====
if (!is_logged_in()) {
    set_flash('Silakan login sebagai assisten terlebih dahulu.', 'warning');
    header('Location: /heavens/akun_assisten/login/');
    exit;
}
$assisten_id = (int)($_SESSION['user_id'] ?? 0);
if (!$assisten_id) {
    set_flash('Sesi assisten tidak valid.', 'danger');
    header('Location: /heavens/akun_assisten/login/');
    exit;
}

$pdo = db();
$errors = [];
$success = null;

// ===== Ambil daftar praktikum yang diampu assisten =====
try {
    $q = $pdo->prepare("
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
    $q->execute([$assisten_id]);
    $praktikum_list = $q->fetchAll();
} catch (Exception $e) {
    $praktikum_list = [];
    $errors[] = "Gagal memuat daftar praktikum: " . $e->getMessage();
}

$has_praktikum = !empty($praktikum_list);

// ===== Handle Submit =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Sesi tidak valid (CSRF). Muat ulang halaman.';
    }

    // Ambil input
    $praktikum_id  = (int)($_POST['praktikum_id'] ?? 0);
    $pertemuan_ke  = (int)($_POST['pertemuan_ke'] ?? 0);
    $judul         = trim($_POST['judul'] ?? '');
    $deskripsi     = trim($_POST['deskripsi'] ?? '');
    $deadline_in   = trim($_POST['deadline'] ?? ''); // "YYYY-MM-DDTHH:MM"
    $file_path_web = null;

    // Validasi dasar
    if ($praktikum_id <= 0)  $errors[] = 'Silakan pilih praktikum.';
    if ($pertemuan_ke < 1 || $pertemuan_ke > 10) $errors[] = 'Pertemuan harus antara 1 sampai 10.';
    if ($judul === '' || mb_strlen($judul) < 3) $errors[] = 'Judul tugas minimal 3 karakter.';
    if ($deskripsi === '')   $errors[] = 'Deskripsi tugas wajib diisi.';
    if ($deadline_in === '') $errors[] = 'Deadline wajib diisi.';

    // Validasi kewenangan praktikum
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM tb_assisten_praktikum WHERE assisten_id = ? AND praktikum_id = ?");
        $chk->execute([$assisten_id, $praktikum_id]);
        if ((int)$chk->fetchColumn() === 0) {
            $errors[] = 'Anda tidak berwenang memberi tugas pada praktikum tersebut.';
        }
    }

    // Konversi deadline ke DATETIME
    $deadline_dt = null;
    if (empty($errors)) {
        $ts = strtotime($deadline_in);
        if ($ts === false) {
            $errors[] = 'Format deadline tidak valid.';
        } else {
            $deadline_dt = date('Y-m-d H:i:00', $ts);
            if ($ts <= time()) {
                $errors[] = 'Deadline harus di masa depan.';
            }
        }
    }

    // Validasi maksimal 2 tugas per (praktikum_id, pertemuan_ke)
    if (empty($errors)) {
        $dup = $pdo->prepare("SELECT COUNT(*) FROM tb_tugas WHERE praktikum_id = ? AND pertemuan_ke = ?");
        $dup->execute([$praktikum_id, $pertemuan_ke]);
        if ((int)$dup->fetchColumn() >= 2) {
            $errors[] = 'Tugas untuk pertemuan ke-' . $pertemuan_ke . ' pada praktikum ini sudah mencapai batas maksimal (2 tugas).';
        }
    }

    // Upload file (opsional)
    // Aturan: ekstensi pdf/rar, max 2MB, cek MIME
    $ALLOWED_EXT  = ['pdf', 'rar'];
    $ALLOWED_MIME = [
        'application/pdf',
        'application/x-rar-compressed',
        'application/vnd.rar',
        'application/octet-stream' // RAR kadang terdeteksi ini
    ];
    $MAX_BYTES     = 2 * 1024 * 1024; // 2MB
    $UPLOAD_DIR_WEB = '/heavens/uploads/tugas/beri_tugas/'; // untuk href
    $UPLOAD_DIR_FS  = realpath(__DIR__ . '/../../../') . '/uploads/tugas/beri_tugas/'; // path fisik

    if (!is_dir($UPLOAD_DIR_FS)) {
        @mkdir($UPLOAD_DIR_FS, 0775, true);
    }

    if (!empty($_FILES['file_tugas']['name'])) {
        $f = $_FILES['file_tugas'];
        if (!is_dir($UPLOAD_DIR_FS) || !is_writable($UPLOAD_DIR_FS)) {
            $errors[] = 'Folder upload tidak tersedia/tertulis: ' . $UPLOAD_DIR_FS;
        } else {
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Gagal upload file (error code: ' . $f['error'] . ').';
            } else {
                if ($f['size'] > $MAX_BYTES) {
                    $errors[] = 'Ukuran file melebihi 2MB.';
                } else {
                    $orig_name = $f['name'];
                    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                    if (!in_array($ext, $ALLOWED_EXT, true)) {
                        $errors[] = 'Ekstensi file tidak diizinkan. Hanya PDF atau RAR.';
                    } else {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime  = $finfo->file($f['tmp_name']) ?: 'application/octet-stream';
                        if ($ext === 'pdf' && $mime !== 'application/pdf') {
                            $errors[] = 'File bukan PDF yang valid.';
                        }
                        // RAR sering octet-stream atau application/x-rar-compressed → sudah diizinkan

                        // Nama asli + timestamp
                        $base = pathinfo($orig_name, PATHINFO_FILENAME);
                        $base = preg_replace('/[^A-Za-z0-9_\-\. ]+/', '_', $base);
                        $base = trim($base);
                        if ($base === '') $base = 'lampiran';
                        $final_name = $base . '_' . date('Ymd_His') . '.' . $ext;

                        $dest = $UPLOAD_DIR_FS . $final_name;
                        if (!move_uploaded_file($f['tmp_name'], $dest)) {
                            $errors[] = 'Gagal menyimpan file upload.';
                        } else {
                            $file_path_web = $UPLOAD_DIR_WEB . $final_name;
                        }
                    }
                }
            }
        }
    }

    // Simpan ke DB
    if (empty($errors)) {
        try {
            $ins = $pdo->prepare("
                INSERT INTO tb_tugas
                    (praktikum_id, pertemuan_ke, judul, deskripsi, file_tugas, deadline, dibuat_oleh)
                VALUES (?,?,?,?,?,?,?)
            ");
            $ok = $ins->execute([
                $praktikum_id,
                $pertemuan_ke,
                $judul,
                $deskripsi,
                $file_path_web,
                $deadline_dt,
                $assisten_id
            ]);

            if ($ok) {
                $success = 'Tugas berhasil dibuat.';
            } else {
                $errors[] = 'Gagal menyimpan tugas.';
            }
        } catch (Exception $e) {
            $errors[] = 'Kesalahan server saat menyimpan tugas.';
        }
    }
}

// ===== Ambil daftar tugas yang dibuat oleh assisten ini =====
try {
    $q2 = $pdo->prepare("
        SELECT 
            t.id, t.pertemuan_ke, t.judul, t.deskripsi, t.file_tugas, t.deadline, t.created_at,
            p.kelas, p.shift, p.hari,
            m.mata_kuliah
        FROM tb_tugas t
        JOIN tb_praktikum p ON p.id = t.praktikum_id
        JOIN tb_matkul m ON m.id = p.mata_kuliah
        WHERE t.dibuat_oleh = ?
        ORDER BY t.id DESC
    ");
    $q2->execute([$assisten_id]);
    $tugas_list = $q2->fetchAll();
} catch (Exception $e) {
    $tugas_list = [];
}

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
<title>Beri Tugas Praktikum</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{background:#f4f6f9;}
  .header{padding:22px;background:linear-gradient(135deg,#0d6efd,#00b4d8);color:#fff;border-radius:12px;margin:20px 0 16px;}
  .card{border-radius:14px;}
  .small-muted{color:#6c757d;font-size:.9rem;}
  a.disabled, .btn.disabled, .btn:disabled { pointer-events: none; opacity: .65; }
</style>
</head>
<body>

<?php show_flash(); ?>

<div class="container">
  <div class="header shadow-sm d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-1"><i class="bi bi-clipboard-plus"></i> Beri Tugas Praktikum</h4>
      <div class="small">Buat tugas dan tetapkan deadline. Lampiran opsional (PDF/RAR, maks 2MB). Maksimal 3 tugas per pertemuan.</div>
    </div>
    <a href="/heavens/akun_assisten/" class="btn btn-light btn-sm"><i class="bi bi-house"></i> Dashboard</a>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
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

          <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
          <?php endif; ?>

          <?php if (!$has_praktikum): ?>
            <div class="alert alert-warning">
              Anda belum ditugaskan pada praktikum manapun. Hubungi koordinator.
            </div>
          <?php else: ?>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

            <div class="mb-3">
              <label class="form-label">Praktikum</label>
              <select name="praktikum_id" class="form-select" required>
                <option value="">— Pilih Praktikum —</option>
                <?php foreach ($praktikum_list as $p): 
                    $label = $p['mata_kuliah'];
                    if (!empty($p['kelas'])) $label .= ' • Kelas ' . e($p['kelas']);
                    if (!empty($p['shift'])) $label .= ' • Shift ' . e($p['shift']);
                    if (!empty($p['hari']))  $label .= ' • ' . e($p['hari']);
                ?>
                  <option value="<?= (int)$p['praktikum_id'] ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Pertemuan</label>
              <select name="pertemuan_ke" class="form-select" required>
                <option value="">— Pilih Pertemuan —</option>
                <?php for ($i=1; $i<=10; $i++): ?>
                  <option value="<?= $i ?>">Pertemuan <?= $i ?></option>
                <?php endfor; ?>
              </select>
              <div class="form-text">Maksimal 3 tugas per pertemuan pada praktikum yang sama.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Judul Tugas</label>
              <input type="text" name="judul" class="form-control" maxlength="200" placeholder="mis. Tugas 1: Normalisasi & ERD" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Deskripsi Tugas</label>
              <textarea name="deskripsi" class="form-control" rows="5" placeholder="Instruksi tugas secara detail..." required></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Lampiran File (opsional)</label>
              <input type="file" name="file_tugas" class="form-control" accept=".pdf,.rar">
              <div class="form-text">Hanya PDF atau RAR, ukuran maks 2MB.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Deadline</label>
              <input type="datetime-local" name="deadline" class="form-control" required>
              <div class="form-text">Tugas otomatis ditutup setelah melewati deadline.</div>
            </div>

            <div class="d-flex justify-content-end">
              <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Simpan Tugas</button>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h5 class="mb-3">Daftar Tugas Saya</h5>

          <?php if (empty($tugas_list)): ?>
            <div class="alert alert-info mb-0">Belum ada tugas yang dibuat.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Praktikum</th>
                    <th>Pert.</th>
                    <th>Judul</th>
                    <th>Deadline</th>
                    <th>File</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $no=1; foreach ($tugas_list as $t): 
                      $praktikum_label = $t['mata_kuliah'];
                      if (!empty($t['kelas'])) $praktikum_label .= ' • Kelas ' . e($t['kelas']);
                      if (!empty($t['shift'])) $praktikum_label .= ' • Shift ' . e($t['shift']);
                      if (!empty($t['hari']))  $praktikum_label .= ' • ' . e($t['hari']);

                      $is_closed = (time() > strtotime($t['deadline']));
                  ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><?= e($praktikum_label) ?></td>
                      <td><?= (int)$t['pertemuan_ke'] ?></td>
                      <td><?= e($t['judul']) ?></td>
                      <td>
                        <?= e_dt($t['deadline']) ?>
                        <?php if ($is_closed): ?>
                          <span class="badge bg-danger ms-1">Sudah lewat</span>
                        <?php else: ?>
                          <span class="badge bg-success ms-1">Masih dibuka</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($t['file_tugas'])): ?>
                          <?php if ($is_closed): ?>
                            <a class="btn btn-outline-secondary btn-sm disabled" href="<?= e($t['file_tugas']) ?>" tabindex="-1" aria-disabled="true">Download</a>
                          <?php else: ?>
                            <a class="btn btn-outline-primary btn-sm" href="<?= e($t['file_tugas']) ?>" target="_blank" rel="noopener">Download</a>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
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
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
