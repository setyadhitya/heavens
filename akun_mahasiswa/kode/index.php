<?php
// heavens/akun_mahasiswa/kode/index.php
require_once __DIR__ . '/../../fatman/functions.php';

// ========== AUTH ==========
if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'praktikan') {
    set_flash('Silakan login sebagai praktikan terlebih dahulu.', 'warning');
    header('Location: /heavens/akun_mahasiswa/login/');
    exit;
}

$pdo            = db();
$praktikan_id   = (int)($_SESSION['user_id'] ?? 0);
$errors         = [];
$success_msg    = null;

// ====== HELPER: Haversine distance in meters ======
function haversine_distance_m($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000.0; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2)
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
       * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

// ========== HANDLE SUBMIT ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token invalid.';
    } else {
        $kode_input = strtolower(trim($_POST['kode'] ?? ''));
        $lokasi     = trim($_POST['lokasi'] ?? ''); // "lat,lng"

        if ($kode_input === '') {
            $errors[] = 'Kode presensi harus diisi.';
        } elseif (!preg_match('/^[a-z0-9]{1,5}$/', $kode_input)) {
            $errors[] = 'Kode presensi tidak valid.';
        }

        if ($lokasi === '' || !preg_match('/^-?\d+\.\d+,-?\d+\.\d+$/', $lokasi)) {
            $errors[] = 'Aktifkan/ijinkan lokasi pada perangkat anda.';
        }

        if (empty($errors)) {
            try {
                // 1) Ambil data kode presensi
                $stmt = $pdo->prepare("
                    SELECT k.id, k.praktikum_id, k.kode, k.status, k.pertemuan_ke, k.materi, k.lokasi AS lokasi_kode,
                           k.generated_by_assisten_id AS assisten_id, k.created_at
                    FROM tb_kode_presensi k
                    WHERE k.kode = ?
                    LIMIT 1
                ");
                $stmt->execute([$kode_input]);
                $kode = $stmt->fetch();

                if (!$kode) {
                    $errors[] = 'Kode presensi tidak ditemukan.';
                } else {
                    // 2) Cek masa aktif (5 menit) & status
                    $created_ts     = strtotime($kode['created_at']);
                    $total_seconds  = 5 * 60; // 5 menit
                    $elapsed        = time() - $created_ts;
                    $remain         = max(0, $total_seconds - $elapsed);

                    if ($remain <= 0 || $kode['status'] !== 'aktif') {
                        // Jika masih bertanda aktif tapi sudah habis waktu, set expired
                        if ($remain <= 0 && $kode['status'] === 'aktif') {
                            $u = $pdo->prepare("UPDATE tb_kode_presensi SET status='expired' WHERE id=?");
                            $u->execute([$kode['id']]);
                        }
                        $errors[] = 'Kode presensi sudah kadaluarsa.';
                    } else {
                        // 3) Pastikan praktikan terdaftar pada praktikum terkait kode (tb_peserta)
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) FROM tb_peserta
                            WHERE praktikan_id = ? AND praktikum_id = ?
                        ");
                        $stmt->execute([$praktikan_id, $kode['praktikum_id']]);
                        if ((int)$stmt->fetchColumn() === 0) {
                            $errors[] = 'Anda tidak terdaftar pada praktikum ini.';
                        } else {
                            // 4) Cegah presensi dobel (berdasarkan kode_id ATAU kombinasi praktikum + pertemuan)
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) FROM tb_presensi
                                WHERE praktikan_id = ? AND (kode_id = ? OR (praktikum_id = ? AND pertemuan_ke = ?))
                            ");
                            $stmt->execute([
                                $praktikan_id,
                                $kode['id'],
                                $kode['praktikum_id'],
                                $kode['pertemuan_ke']
                            ]);
                            if ((int)$stmt->fetchColumn() > 0) {
                                $errors[] = 'Anda sudah melakukan presensi untuk pertemuan ini.';
                            } else {
                                // 5) Hitung jarak dari lokasi kode
                                // lokasi mahasiswa
                                [$latS, $lngS] = array_map('floatval', explode(',', $lokasi));
                                // lokasi saat kode dibuat
                                [$latK, $lngK] = array_map('floatval', explode(',', $kode['lokasi_kode']));
                                $jarak_m = haversine_distance_m($latS, $lngS, $latK, $lngK);

                                // 6) Tentukan status: <= 20m Hadir, > 20m NA
                                $status_kehadiran = ($jarak_m <= 20.0) ? 'Hadir' : 'NA';

                                // 7) Simpan presensi
                                $ins = $pdo->prepare("
                                    INSERT INTO tb_presensi
                                        (praktikan_id, praktikum_id, kode_id, pertemuan_ke, status, lokasi, assisten_id)
                                    VALUES (?,?,?,?,?,?,?)
                                ");
                                $ok = $ins->execute([
                                    $praktikan_id,
                                    $kode['praktikum_id'],
                                    $kode['id'],
                                    $kode['pertemuan_ke'],
                                    $status_kehadiran,
                                    $lokasi,
                                    $kode['assisten_id']
                                ]);

                                if ($ok) {
                                    // Pesan sukses informatif
                                    if ($status_kehadiran === 'Hadir') {
                                        $success_msg = 'Presensi berhasil. Status: Hadir.';
                                    } else {
                                        $success_msg = 'Presensi tersimpan, namun lokasi terdeteksi lebih dari 20 meter. Status: NA.';
                                    }
                                } else {
                                    $errors[] = 'Gagal menyimpan presensi.';
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $errors[] = 'Terjadi kesalahan server. Silakan coba lagi.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Presensi Praktikum</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{background:#f4f6f9;}
  .header{padding:22px;background:linear-gradient(135deg,#0d6efd,#00b4d8);color:#fff;border-radius:12px;margin:20px 0 16px;}
  .card{border-radius:14px;}
  .small-muted{color:#6c757d;font-size:.9rem;}
  .code-input{letter-spacing:2px;text-transform:lowercase;}
</style>
</head>
<body>

<?php show_flash(); ?>

<div class="container">
  <div class="header shadow-sm d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-1"><i class="bi bi-qr-code"></i> Presensi Praktikum</h4>
      <div class="small">Masukkan kode presensi dari assisten. Kode aktif selama 5 menit sejak dibuat.</div>
    </div>
    <a href="/heavens/akun_mahasiswa/" class="btn btn-light btn-sm"><i class="bi bi-house"></i> Dashboard</a>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body p-4">

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                  <li><?= e($e) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if ($success_msg): ?>
            <div class="alert alert-success d-flex justify-content-between align-items-center">
              <span><?= e($success_msg) ?></span>
              <a href="/heavens/akun_mahasiswa/" class="btn btn-sm btn-outline-success">Ke Dashboard</a>
            </div>
          <?php endif; ?>

          <form method="post" onsubmit="return ensureLocationAndLock();" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

            <div class="mb-3">
              <label class="form-label">Kode Presensi</label>
              <input type="text" name="kode" class="form-control code-input" maxlength="5" pattern="[A-Za-z0-9]{1,5}" placeholder="mis. 4h5x9" required oninput="this.value=this.value.toLowerCase()">
              <div class="small-muted mt-1">Gunakan kode yang diberikan assisten. Hanya huruf/angka, tanpa spasi.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Lokasi Anda</label>
              <div class="form-control" id="locPreview" style="background:#f8f9fa">Mencari lokasi...</div>
              <input type="hidden" name="lokasi" id="lokasi" value="">
              <div class="small-muted mt-1">Pastikan GPS/Location aktif dan izinkan akses lokasi pada browser.</div>
            </div>

            <div class="alert alert-warning">
              <div class="fw-semibold">⚠️ Peringatan:</div>
              <ul class="mb-0">
                <li>Hindari menggunakan aplikasi <em>fake location</em> dan sejenisnya.</li>
                <li>Lokasi lebih dari <strong>20 meter</strong> dari titik pembuatan kode akan dianggap <strong>tidak hadir (NA)</strong>.</li>
              </ul>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
              <a href="/heavens/akun_mahasiswa/" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Batal</a>
              <button id="submitBtn" type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Kirim Presensi</button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap Icons (opsional jika dipakai di header) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<script>
// Lock submit agar tidak dobel
function ensureLocationAndLock(){
  const loc = document.getElementById('lokasi').value.trim();
  if(!loc){
    // alert Bootstrap sudah ditangani server; namun kita cegah submit kosong
    const p = document.getElementById('locPreview');
    if (p) p.textContent = 'Aktifkan/ijinkan lokasi pada perangkat anda.';
    return false;
  }
  const btn = document.getElementById('submitBtn');
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Memproses...';
  }
  return true;
}

// Ambil lokasi dari browser
(function getLocation(){
  const preview = document.getElementById('locPreview');
  const input   = document.getElementById('lokasi');
  if (!navigator.geolocation){
    preview.textContent = 'Geolocation tidak didukung.';
    return;
  }
  navigator.geolocation.getCurrentPosition(function(pos){
    const lat = pos.coords.latitude.toFixed(7);
    const lng = pos.coords.longitude.toFixed(7);
    input.value = lat+','+lng;
    preview.textContent = lat+','+lng;
  }, function(err){
    preview.textContent = 'Aktifkan/ijinkan lokasi pada perangkat anda ('+err.message+').';
  }, {enableHighAccuracy:true, timeout:10000, maximumAge:0});
})();
</script>
</body>
</html>
