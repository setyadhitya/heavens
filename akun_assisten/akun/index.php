<?php
// /heavens/akun_assisten/akun/index.php
require_once __DIR__ . '/../../fatman/functions.php';

// ====== AUTH GUARD (khusus assisten) ======
if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'assisten') {
    set_flash('Silakan login sebagai assisten terlebih dahulu.', 'warning');
    header('Location: /heavens/akun_assisten/login/');
    exit;
}

$pdo = db();
$assisten_id = (int)($_SESSION['user_id'] ?? 0);
$errors = [];
$success = [];

// ====== Ambil data akun saat ini ======
function get_me(PDO $pdo, int $id) {
    $q = $pdo->prepare("SELECT id, username, nama, nim, nomorhp, password, role, status, created_at FROM tb_assisten WHERE id = ? LIMIT 1");
    $q->execute([$id]);
    return $q->fetch();
}
$me = get_me($pdo, $assisten_id);
if (!$me) {
    http_response_code(404);
    die('<div style="padding:20px;background:#fee;color:#900">Akun tidak ditemukan.</div>');
}

// ====== Handler Update Per-Field ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Sesi tidak valid (CSRF). Muat ulang halaman.';
    } else {
        $field = $_POST['field'] ?? '';

        try {
            // ----- Update Username -----
            if ($field === 'username') {
                $username = trim($_POST['username'] ?? '');
                // Validasi: a-z0-9_ tanpa spasi, panjang 3-100
                if ($username === '') {
                    $errors[] = 'Username tidak boleh kosong.';
                } elseif (!preg_match('/^[a-z0-9_]{3,100}$/', $username)) {
                    $errors[] = 'Username hanya boleh huruf kecil, angka, dan underscore (3-100 karakter, tanpa spasi).';
                } else {
                    // Cek unik (kecuali diri sendiri)
                    $st = $pdo->prepare("SELECT COUNT(*) FROM tb_assisten WHERE username = ? AND id <> ?");
                    $st->execute([$username, $assisten_id]);
                    if ((int)$st->fetchColumn() > 0) {
                        $errors[] = 'Username sudah dipakai. Gunakan username lain.';
                    }
                }

                if (empty($errors)) {
                    $u = $pdo->prepare("UPDATE tb_assisten SET username = ? WHERE id = ?");
                    $u->execute([$username, $assisten_id]);
                    $success[] = 'Username berhasil diperbarui.';
                    // Optional: update session jika perlu dipakai di UI lain
                    // $_SESSION['user_username'] = $username;
                }
            }

            // ----- Update Nama -----
            if ($field === 'nama') {
                $nama = trim($_POST['nama'] ?? '');
                if ($nama === '') {
                    $errors[] = 'Nama tidak boleh kosong.';
                } else {
                    // Tidak boleh ada angka
                    if (preg_match('/\d/', $nama)) {
                        $errors[] = 'Nama tidak boleh mengandung angka.';
                    }
                    // Boleh huruf + spasi + tanda baca ringan
                    if (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ'.\\-\\s]{2,150}$/u", $nama)) {
                        $errors[] = 'Nama mengandung karakter tidak valid.';
                    }
                }
                if (empty($errors)) {
                    $u = $pdo->prepare("UPDATE tb_assisten SET nama = ? WHERE id = ?");
                    $u->execute([$nama, $assisten_id]);
                    $success[] = 'Nama berhasil diperbarui.';
                    $_SESSION['user_nama'] = $nama; // sinkron sesi
                }
            }

            // ----- Update NIM -----
            if ($field === 'nim') {
                $nim = trim($_POST['nim'] ?? '');
                if ($nim === '') {
                    $errors[] = 'NIM tidak boleh kosong.';
                } elseif (!preg_match('/^[0-9]+$/', $nim)) {
                    $errors[] = 'NIM hanya boleh berisi angka.';
                } elseif (strlen($nim) > 50) {
                    $errors[] = 'NIM terlalu panjang (maks. 50).';
                } else {
                    $u = $pdo->prepare("UPDATE tb_assisten SET nim = ? WHERE id = ?");
                    $u->execute([$nim, $assisten_id]);
                    $success[] = 'NIM berhasil diperbarui.';
                    $_SESSION['user_nim'] = $nim; // sinkron sesi
                }
            }

            // ----- Update Nomor HP -----
            if ($field === 'nomorhp') {
                $hp = trim($_POST['nomorhp'] ?? '');
                if ($hp === '') {
                    $errors[] = 'Nomor HP tidak boleh kosong.';
                } elseif (!preg_match('/^[0-9]+$/', $hp)) {
                    // Sesuai permintaan: tidak boleh huruf → angka saja
                    $errors[] = 'Nomor HP hanya boleh berisi angka.';
                } elseif (strlen($hp) < 8 || strlen($hp) > 30) {
                    $errors[] = 'Nomor HP harus 8–30 digit.';
                } else {
                    $u = $pdo->prepare("UPDATE tb_assisten SET nomorhp = ? WHERE id = ?");
                    $u->execute([$hp, $assisten_id]);
                    $success[] = 'Nomor HP berhasil diperbarui.';
                    $_SESSION['user_nomorhp'] = $hp; // sinkron sesi
                }
            }

            // ----- Update Password (lama + baru minimal 6) -----
            if ($field === 'password') {
                $old = $_POST['old_password'] ?? '';
                $new = $_POST['new_password'] ?? '';

                if ($old === '' || $new === '') {
                    $errors[] = 'Password lama dan password baru wajib diisi.';
                } elseif (strlen($new) < 6) {
                    $errors[] = 'Password baru minimal 6 karakter.';
                } else {
                    // verifikasi password lama
                    if (!password_verify($old, $me['password'])) {
                        $errors[] = 'Password lama tidak cocok.';
                    }
                }

                if (empty($errors)) {
                    $hash = password_hash($new, PASSWORD_DEFAULT);
                    $u = $pdo->prepare("UPDATE tb_assisten SET password = ? WHERE id = ?");
                    $u->execute([$hash, $assisten_id]);
                    $success[] = 'Password berhasil diperbarui.';
                }
            }

        } catch (Exception $e) {
            $errors[] = 'Terjadi kesalahan server saat menyimpan. Coba lagi.';
        }
    }

    // Refresh data terbaru setelah update
    $me = get_me($pdo, $assisten_id);
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Profil Akun Assisten</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{background:#f4f6f9;}
  .header{padding:22px;background:linear-gradient(135deg,#0d6efd,#00b4d8);color:#fff;border-radius:12px;margin:20px 0 16px;}
  .card{border-radius:14px;}
  .rowline{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #eee;}
  .label{color:#6c757d;min-width:140px;}
  .value{font-weight:600;}
  .btn-link{padding:0;}
  .editbox{background:#f8f9fa;border-radius:12px;padding:14px;margin-top:10px;}
</style>
</head>
<body>

<?php show_flash(); ?>

<div class="container">
  <div class="header shadow-sm d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-1"><i class="bi bi-person-badge"></i> Profil Akun Assisten</h4>
      <div class="small">Ubah data akun Anda. Setiap bagian bisa diganti sendiri-sendiri.</div>
    </div>
    <a href="/heavens/akun_assisten/" class="btn btn-light btn-sm"><i class="bi bi-house"></i> Dashboard</a>
  </div>

  <div class="row justify-content-center">
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
            <div class="alert alert-success">
              <ul class="mb-0">
                <?php foreach ($success as $ok): ?>
                  <li><?= e($ok) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <!-- USERNAME -->
          <div class="rowline">
            <div>
              <div class="label">Username</div>
              <div class="value"><?= e($me['username']) ?></div>
            </div>
            <button class="btn btn-link" type="button" data-toggle-target="#edit-username">Ganti</button>
          </div>
          <div id="edit-username" class="editbox d-none">
            <form method="post" class="mb-0">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="field" value="username">
              <div class="mb-2">
                <label class="form-label">Username baru</label>
                <input type="text" name="username" class="form-control" required
                       minlength="3" maxlength="100" pattern="[a-z0-9_]{3,100}"
                       placeholder="contoh: syan_dev" value="<?= e($me['username']) ?>">
                <div class="form-text">Hanya huruf kecil, angka, dan underscore. Tanpa spasi.</div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-primary">Simpan</button>
                <button class="btn btn-outline-secondary" type="button" data-cancel="#edit-username">Batal</button>
              </div>
            </form>
          </div>

          <!-- NAMA -->
          <div class="rowline">
            <div>
              <div class="label">Nama</div>
              <div class="value"><?= e($me['nama']) ?></div>
            </div>
            <button class="btn btn-link" type="button" data-toggle-target="#edit-nama">Ganti</button>
          </div>
          <div id="edit-nama" class="editbox d-none">
            <form method="post" class="mb-0">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="field" value="nama">
              <div class="mb-2">
                <label class="form-label">Nama</label>
                <input type="text" name="nama" class="form-control" required maxlength="150"
                       placeholder="contoh: Nafisa" value="<?= e($me['nama']) ?>">
                <div class="form-text">Tidak boleh mengandung angka.</div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-primary">Simpan</button>
                <button class="btn btn-outline-secondary" type="button" data-cancel="#edit-nama">Batal</button>
              </div>
            </form>
          </div>

          <!-- NIM -->
          <div class="rowline">
            <div>
              <div class="label">NIM</div>
              <div class="value"><?= e($me['nim']) ?></div>
            </div>
            <button class="btn btn-link" type="button" data-toggle-target="#edit-nim">Ganti</button>
          </div>
          <div id="edit-nim" class="editbox d-none">
            <form method="post" class="mb-0">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="field" value="nim">
              <div class="mb-2">
                <label class="form-label">NIM</label>
                <input type="text" name="nim" class="form-control" required maxlength="50" pattern="[0-9]+"
                       placeholder="contoh: 22510015" value="<?= e($me['nim']) ?>">
                <div class="form-text">Hanya angka.</div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-primary">Simpan</button>
                <button class="btn btn-outline-secondary" type="button" data-cancel="#edit-nim">Batal</button>
              </div>
            </form>
          </div>

          <!-- NOMOR HP -->
          <div class="rowline">
            <div>
              <div class="label">Nomor HP</div>
              <div class="value"><?= e($me['nomorhp'] ?? '-') ?></div>
            </div>
            <button class="btn btn-link" type="button" data-toggle-target="#edit-hp">Ganti</button>
          </div>
          <div id="edit-hp" class="editbox d-none">
            <form method="post" class="mb-0">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="field" value="nomorhp">
              <div class="mb-2">
                <label class="form-label">Nomor HP</label>
                <input type="text" name="nomorhp" class="form-control" required minlength="8" maxlength="30" pattern="[0-9]+"
                       placeholder="contoh: 081234567890" value="<?= e($me['nomorhp'] ?? '') ?>">
                <div class="form-text">Hanya angka (8–30 digit).</div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-primary">Simpan</button>
                <button class="btn btn-outline-secondary" type="button" data-cancel="#edit-hp">Batal</button>
              </div>
            </form>
          </div>

          <!-- PASSWORD -->
          <div class="rowline">
            <div>
              <div class="label">Password</div>
              <div class="value">••••••••</div>
            </div>
            <button class="btn btn-link" type="button" data-toggle-target="#edit-pass">Ganti</button>
          </div>
          <div id="edit-pass" class="editbox d-none">
            <form method="post" class="mb-0">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="field" value="password">
              <div class="mb-2">
                <label class="form-label">Password lama</label>
                <input type="password" name="old_password" class="form-control" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Password baru</label>
                <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="Min. 6 karakter">
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-primary">Simpan</button>
                <button class="btn btn-outline-secondary" type="button" data-cancel="#edit-pass">Batal</button>
              </div>
            </form>
          </div>

        </div>
      </div>

      <div class="text-center mt-3">
        <a class="small text-muted" href="/heavens/akun_assisten/">&larr; Kembali ke Dashboard</a>
      </div>
    </div>
  </div>
</div>

<script>
// Toggle show/hide box ketika klik "Ganti"
document.querySelectorAll('[data-toggle-target]').forEach(btn => {
  btn.addEventListener('click', () => {
    const sel = btn.getAttribute('data-toggle-target');
    const box = document.querySelector(sel);
    if (box) box.classList.toggle('d-none');
  });
});
// Tombol Batal menutup box
document.querySelectorAll('[data-cancel]').forEach(btn => {
  btn.addEventListener('click', () => {
    const sel = btn.getAttribute('data-cancel');
    const box = document.querySelector(sel);
    if (box) box.classList.add('d-none');
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
