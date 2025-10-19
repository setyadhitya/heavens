<?php
// heavens/akun_mahasiswa/kirim_tugas/index.php
require_once __DIR__ . '/../../fatman/functions.php';

// ====== AUTH GUARD (khusus praktikan) ======
if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'praktikan') {
    set_flash('Silakan login sebagai praktikan terlebih dahulu.', 'warning');
    header('Location: /heavens/akun_mahasiswa/login/');
    exit;
}

$pdo = db();
$praktikan_id = (int)($_SESSION['user_id'] ?? 0);
$errors = [];
$success = null;

// ====== Ambil daftar tugas yang masih dibuka untuk praktikan ini ======
// Filter: praktikum dari tb_peserta + deadline > NOW()
try {
    $q = $pdo->prepare("
        SELECT 
          t.id AS tugas_id,
          t.pertemuan_ke,
          t.judul,
          t.deskripsi,
          t.file_tugas,
          t.deadline,
          t.praktikum_id,
          m.mata_kuliah,
          p.kelas, p.shift, p.hari
        FROM tb_tugas t
        JOIN tb_praktikum p ON p.id = t.praktikum_id
        JOIN tb_matkul m ON m.id = p.mata_kuliah
        JOIN tb_peserta ps ON ps.praktikum_id = t.praktikum_id AND ps.praktikan_id = ?
        WHERE t.deadline > NOW()
        ORDER BY t.deadline ASC, t.id DESC
    ");
    $q->execute([$praktikan_id]);
    $tugas_terbuka = $q->fetchAll();
} catch (Exception $e) {
    $tugas_terbuka = [];
}

// ====== Handle Submit Upload ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Sesi tidak valid (CSRF). Muat ulang halaman.';
    } else {
        $tugas_id = (int)($_POST['tugas_id'] ?? 0);

        if ($tugas_id <= 0) {
            $errors[] = 'Silakan pilih tugas.';
        }

        // Ambil detail tugas untuk validasi
        if (empty($errors)) {
            $st = $pdo->prepare("
                SELECT 
                    t.id AS tugas_id, t.praktikum_id, t.deadline, t.file_tugas,
                    t.pertemuan_ke, t.judul, t.deskripsi,
                    m.mata_kuliah, p.kelas, p.shift, p.hari
                FROM tb_tugas t
                JOIN tb_praktikum p ON p.id = t.praktikum_id
                JOIN tb_matkul m ON m.id = p.mata_kuliah
                WHERE t.id = ?
                LIMIT 1
            ");
            $st->execute([$tugas_id]);
            $tugas = $st->fetch();

            if (!$tugas) {
                $errors[] = 'Tugas tidak ditemukan.';
            } else {
                // Validasi peserta: harus terdaftar di praktikum tugas tsb
                $cekPes = $pdo->prepare("SELECT COUNT(*) FROM tb_peserta WHERE praktikan_id = ? AND praktikum_id = ?");
                $cekPes->execute([$praktikan_id, $tugas['praktikum_id']]);
                if ((int)$cekPes->fetchColumn() === 0) {
                    $errors[] = 'Anda tidak terdaftar pada praktikum untuk tugas ini.';
                }

                // Validasi deadline: harus sebelum deadline
                $deadline_ts = strtotime($tugas['deadline']);
                if ($deadline_ts !== false && time() > $deadline_ts) {
                    $errors[] = 'Deadline tugas sudah lewat. Pengumpulan ditolak.';
                }
            }
        }

        // Validasi & proses upload file
        $UPLOAD_DIR_WEB = '/heavens/uploads/tugas/kumpul_tugas/';
        $UPLOAD_DIR_FS  = realpath(__DIR__ . '/../../../') . '/uploads/tugas/kumpul_tugas/';

        if (!is_dir($UPLOAD_DIR_FS)) {
            @mkdir($UPLOAD_DIR_FS, 0775, true);
        }

        $ALLOWED_EXT = ['pdf', 'doc', 'docx', 'zip', 'rar'];
        $ALLOWED_MIME = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/vnd.rar',
            'application/octet-stream' // fallback (beberapa server memberi ini untuk zip/rar)
        ];
        $MAX_BYTES = 2 * 1024 * 1024; // 2MB

        $file_kumpul_web = null;

        if (empty($errors)) {
            if (empty($_FILES['file_jawaban']['name'])) {
                $errors[] = 'Silakan pilih file jawaban untuk diunggah.';
            } else {
                $f = $_FILES['file_jawaban'];
                if (!is_dir($UPLOAD_DIR_FS) || !is_writable($UPLOAD_DIR_FS)) {
                    $errors[] = 'Folder upload tidak tersedia/tertulis.';
                } else {
                    if ($f['error'] !== UPLOAD_ERR_OK) {
                        $errors[] = 'Gagal upload file (error code: ' . $f['error'] . ').';
                    } elseif ($f['size'] > $MAX_BYTES) {
                        $errors[] = 'Ukuran file melebihi 2MB.';
                    } else {
                        $orig = $f['name'];
                        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                        if (!in_array($ext, $ALLOWED_EXT, true)) {
                            $errors[] = 'Ekstensi file tidak diizinkan. Gunakan pdf, doc, docx, zip, atau rar.';
                        } else {
                            $finfo = new finfo(FILEINFO_MIME_TYPE);
                            $mime  = $finfo->file($f['tmp_name']) ?: 'application/octet-stream';
                            if (!in_array($mime, $ALLOWED_MIME, true)) {
                                $errors[] = 'Tipe file tidak diizinkan.';
                            } else {
                                // Nama file: username/nim + judul pendek + timestamp (rapi)
                                $me_user = preg_replace('/[^A-Za-z0-9_\-]/', '_', $_SESSION['user_nama'] ?? 'praktikan');
                                $base    = pathinfo($orig, PATHINFO_FILENAME);
                                $base    = preg_replace('/[^A-Za-z0-9_\-\. ]+/', '_', $base);
                                if ($base === '') $base = 'jawaban';
                                $final   = $me_user . '_tugas' . $tugas_id . '_' . date('Ymd_His') . '.' . $ext;

                                $dest = $UPLOAD_DIR_FS . $final;
                                if (!move_uploaded_file($f['tmp_name'], $dest)) {
                                    $errors[] = 'Gagal menyimpan file upload.';
                                } else {
                                    $file_kumpul_web = $UPLOAD_DIR_WEB . $final;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Simpan / overwrite ke tb_kumpul_tugas
        if (empty($errors)) {
            try {
                // cek exist
                $cek = $pdo->prepare("SELECT id, file_kumpul FROM tb_kumpul_tugas WHERE tugas_id = ? AND praktikan_id = ? LIMIT 1");
                $cek->execute([$tugas_id, $praktikan_id]);
                $exist = $cek->fetch();

                if ($exist) {
                    // Overwrite: hapus file lama (kalau ada), update baris
                    if (!empty($exist['file_kumpul'])) {
                        $oldFs = realpath(__DIR__ . '/../../../') . str_replace('/heavens', '', $exist['file_kumpul']);
                        // Jika struktur /heavens/... tidak cocok di servermu, kamu bisa menyesuaikan path ini
                        // Untuk amannya gunakan uploads/tugas/kumpul_tugas relatif:
                        $maybe = realpath(__DIR__ . '/../../../') . '/uploads/tugas/kumpul_tugas/' . basename($exist['file_kumpul']);
                        if (is_file($maybe)) @unlink($maybe);
                        elseif ($oldFs && is_file($oldFs)) @unlink($oldFs);
                    }
                    $up = $pdo->prepare("UPDATE tb_kumpul_tugas SET file_kumpul = ?, status = 'dikirim', created_at = NOW() WHERE id = ?");
                    $up->execute([$file_kumpul_web, $exist['id']]);
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO tb_kumpul_tugas (tugas_id, praktikan_id, praktikum_id, file_kumpul, status)
                        VALUES (?,?,?,?, 'dikirim')
                    ");
                    $ins->execute([$tugas_id, $praktikan_id, $tugas['praktikum_id'], $file_kumpul_web]);
                }

                $success = 'Tugas berhasil dikirim.';
                // refresh list tugas terbuka (mungkin masih sama)
                $q->execute([$praktikan_id]);
                $tugas_terbuka = $q->fetchAll();

            } catch (Exception $e) {
                $errors[] = 'Kesalahan server saat menyimpan pengumpulan tugas.';
            }
        }
    }
}

// ====== Riwayat pengumpulan user ini ======
try {
    $r = $pdo->prepare("
        SELECT 
          kt.id, kt.file_kumpul, kt.status, kt.created_at,
          t.id AS tugas_id, t.judul, t.pertemuan_ke, t.deadline, t.file_tugas,
          m.mata_kuliah, p.kelas, p.shift, p.hari
        FROM tb_kumpul_tugas kt
        JOIN tb_tugas t ON t.id = kt.tugas_id
        JOIN tb_praktikum p ON p.id = t.praktikum_id
        JOIN tb_matkul m ON m.id = p.mata_kuliah
        WHERE kt.praktikan_id = ?
        ORDER BY kt.id DESC
    ");
    $r->execute([$praktikan_id]);
    $riwayat = $r->fetchAll();
} catch (Exception $e) {
    $riwayat = [];
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
<title>Kirim Tugas Praktikum</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{background:#f4f6f9;}
  .header{padding:22px;background:linear-gradient(135deg,#0d6efd,#00b4d8);color:#fff;border-radius:12px;margin:20px 0 16px;}
  .card{border-radius:14px;}
  .small-muted{color:#6c757d;font-size:.9rem;}
  a.disabled, .btn.disabled, .btn:disabled { pointer-events: none; opacity: .65; }
  .desc-box{background:#f8f9fa;border-radius:12px;padding:12px;}
</style>
</head>
<body>

<?php show_flash(); ?>

<div class="container">
  <div class="header shadow-sm d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-1"><i class="bi bi-upload"></i> Kirim Tugas Praktikum</h4>
      <div class="small">Silakan pilih tugas yang masih dibuka, lalu upload file jawaban Anda.</div>
    </div>
    <a href="/heavens/akun_mahasiswa/" class="btn btn-light btn-sm"><i class="bi bi-house"></i> Dashboard</a>
  </div>

  <div class="row g-3">
    <!-- Form kirim tugas -->
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

          <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
          <?php endif; ?>

          <?php if (empty($tugas_terbuka)): ?>
            <div class="alert alert-info mb-0">
              Tidak ada tugas yang terbuka untuk Anda saat ini, atau semua sudah melewati deadline.
            </div>
          <?php else: ?>
          <form method="post" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

            <div class="mb-3">
              <label class="form-label">Pilih Tugas</label>
              <select name="tugas_id" id="tugasSelect" class="form-select" required>
                <option value="">— Pilih Tugas —</option>
                <?php foreach ($tugas_terbuka as $t): 
                    $praktikum_label = $t['mata_kuliah'];
                    if (!empty($t['kelas'])) $praktikum_label .= ' • Kelas ' . e($t['kelas']);
                    if (!empty($t['shift'])) $praktikum_label .= ' • Shift ' . e($t['shift']);
                    if (!empty($t['hari']))  $praktikum_label .= ' • ' . e($t['hari']);
                    $is_closed = (time() > strtotime($t['deadline']));
                ?>
                  <option
                    value="<?= (int)$t['tugas_id'] ?>"
                    data-mk="<?= e($praktikum_label) ?>"
                    data-judul="<?= e($t['judul']) ?>"
                    data-pertemuan="<?= (int)$t['pertemuan_ke'] ?>"
                    data-deskripsi="<?= e($t['deskripsi']) ?>"
                    data-deadline="<?= e_dt($t['deadline']) ?>"
                    data-file="<?= e($t['file_tugas'] ?? '') ?>"
                    data-closed="<?= $is_closed ? '1' : '0' ?>"
                  >
                    [Pert. <?= (int)$t['pertemuan_ke'] ?>] <?= e($t['judul']) ?> — <?= e_dt($t['deadline']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Tugas yang tampil hanya yang masih dibuka (sebelum deadline).</div>
            </div>

            <div id="tugasInfo" class="mb-3 d-none">
              <div class="desc-box">
                <div class="fw-semibold mb-1">Detail Tugas</div>
                <div><span class="small-muted">Mata Kuliah:</span> <span id="infMk">-</span></div>
                <div><span class="small-muted">Pertemuan:</span> <span id="infPert">-</span></div>
                <div><span class="small-muted">Judul:</span> <span id="infJudul">-</span></div>
                <div><span class="small-muted">Deadline:</span> <span id="infDeadline">-</span></div>
                <div class="mt-2"><span class="small-muted">Deskripsi:</span>
                  <div id="infDesk" class="mt-1"></div>
                </div>
                <div class="mt-2">
                  <a id="btnLampiran" href="#" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Download Lampiran Tugas</a>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Upload File Jawaban</label>
              <input type="file" name="file_jawaban" class="form-control" required accept=".pdf,.doc,.docx,.zip,.rar">
              <div class="form-text">Format: pdf, doc, docx, zip, rar. Maks 2MB.</div>
            </div>

            <div class="d-flex justify-content-end">
              <button class="btn btn-primary"><i class="bi bi-send-check"></i> Kirim Tugas</button>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Riwayat kirim -->
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h5 class="mb-3">Riwayat Pengumpulan Saya</h5>
          <?php if (empty($riwayat)): ?>
            <div class="alert alert-info mb-0">Belum ada pengumpulan tugas.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Matkul / Pert.</th>
                    <th>Judul</th>
                    <th>Dikirim</th>
                    <th>File</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $no=1; foreach ($riwayat as $k): 
                      $praktikum_label = $k['mata_kuliah'];
                      if (!empty($k['kelas'])) $praktikum_label .= ' • Kelas ' . e($k['kelas']);
                      if (!empty($k['shift'])) $praktikum_label .= ' • Shift ' . e($k['shift']);
                      if (!empty($k['hari']))  $praktikum_label .= ' • ' . e($k['hari']);
                  ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><?= e($praktikum_label) ?><br><span class="small-muted">Pert. <?= (int)$k['pertemuan_ke'] ?></span></td>
                      <td><?= e($k['judul']) ?></td>
                      <td><?= e_dt($k['created_at']) ?></td>
                      <td>
                        <?php if (!empty($k['file_kumpul'])): ?>
                          <a class="btn btn-outline-primary btn-sm" href="<?= e($k['file_kumpul']) ?>" target="_blank" rel="noopener">Lihat</a>
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
      <div class="small text-muted mt-2">Catatan: Mengirim ulang tugas yang sama akan menimpa (overwrite) kiriman sebelumnya.</div>
    </div>
  </div>
</div>

<script>
const sel = document.getElementById('tugasSelect');
const box = document.getElementById('tugasInfo');
const infMk = document.getElementById('infMk');
const infPert = document.getElementById('infPert');
const infJudul = document.getElementById('infJudul');
const infDeadline = document.getElementById('infDeadline');
const infDesk = document.getElementById('infDesk');
const btnLampiran = document.getElementById('btnLampiran');

function updateInfo() {
  const opt = sel.options[sel.selectedIndex];
  if (!opt || !opt.value) {
    box.classList.add('d-none');
    return;
  }
  const mk   = opt.getAttribute('data-mk') || '-';
  const pert = opt.getAttribute('data-pertemuan') || '-';
  const jud  = opt.getAttribute('data-judul') || '-';
  const dead = opt.getAttribute('data-deadline') || '-';
  const desk = opt.getAttribute('data-deskripsi') || '-';
  const file = opt.getAttribute('data-file') || '';
  const closed = opt.getAttribute('data-closed') === '1';

  infMk.textContent = mk;
  infPert.textContent = pert;
  infJudul.textContent = jud;
  infDeadline.textContent = dead;
  infDesk.textContent = desk;

  if (file) {
    btnLampiran.href = file;
    btnLampiran.classList.toggle('disabled', closed);
  } else {
    btnLampiran.href = '#';
    btnLampiran.classList.add('disabled');
  }

  box.classList.remove('d-none');
}
if (sel) sel.addEventListener('change', updateInfo);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
