<?php
// heavens/akun_assisten/kode/status/index.php
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../../fatman/functions.php';

function js_alert_redirect(string $msg, string $to) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
    echo '<script>alert('.json_encode($msg).');window.location.href='.json_encode($to).';</script>';
    echo '</body></html>';
    exit;
}

$assisten_id = $_SESSION['user_id'] ?? null;
if (!$assisten_id) {
    js_alert_redirect('Silakan login sebagai asisten terlebih dahulu.', '/heavens/akun_assisten/login/');
}

$pdo = db();
$kid = (int)($_GET['kid'] ?? 0);
if (!$kid) js_alert_redirect('Parameter tidak valid.', '/heavens/akun_assisten/index.php');

/**
 * ========== AJAX expire handler ==========
 * Dipanggil oleh JS ketika countdown mencapai 0
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'expire') {
    // pastikan kode milik asisten ini
    $stmt = $pdo->prepare("SELECT generated_by_assisten_id, status FROM tb_kode_presensi WHERE id = ?");
    $stmt->execute([$kid]);
    $row = $stmt->fetch();

    header('Content-Type: application/json');
    if (!$row || (int)$row['generated_by_assisten_id'] !== (int)$assisten_id) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Forbidden']);
        exit;
    }

    if ($row['status'] === 'aktif') {
        $u = $pdo->prepare("UPDATE tb_kode_presensi SET status='expired' WHERE id=?");
        $u->execute([$kid]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// Ambil data kode + JOIN mata kuliah
$stmt = $pdo->prepare("
    SELECT
        k.id, k.praktikum_id, k.kode, k.status, k.pertemuan_ke, k.materi, k.lokasi,
        k.generated_by_assisten_id, k.created_at,
        m.mata_kuliah
    FROM tb_kode_presensi k
    JOIN tb_praktikum p ON p.id = k.praktikum_id
    JOIN tb_matkul m ON m.id = p.mata_kuliah
    WHERE k.id = ?
");
$stmt->execute([$kid]);
$kode = $stmt->fetch();

if (!$kode) js_alert_redirect('Data kode tidak ditemukan.', '/heavens/akun_assisten/index.php');
if ((int)$kode['generated_by_assisten_id'] !== (int)$assisten_id) {
    js_alert_redirect('Anda tidak berhak melihat status kode ini.', '/heavens/akun_assisten/index.php');
}

// Hitung sisa waktu server-side (5 menit)
$created_ts = strtotime($kode['created_at']);
// TOTAL DALAM DETIK -> 5 menit = 5 * 60
$total_seconds = 5 * 60;
$elapsed = time() - $created_ts;
$remain  = max(0, $total_seconds - $elapsed);

// Jika sudah kadaluarsa atau status bukan aktif, set expired & redirect
if ($remain <= 0 || $kode['status'] !== 'aktif') {
    if ($kode['status'] === 'aktif') {
        $u = $pdo->prepare("UPDATE tb_kode_presensi SET status='expired' WHERE id=?");
        $u->execute([$kid]);
    }
    js_alert_redirect('Kode presensi sudah kadaluarsa.', '/heavens/akun_assisten/index.php');
}

// Format untuk JS (pastikan integer)
$remain = (int)$remain;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Status Kode Presensi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{ background:#f4f6f9; }
.header{ padding:22px; background:linear-gradient(135deg,#0d6efd,#00b4d8); color:#fff; border-radius:12px; margin:20px 0 16px; }
.card{ border-radius:14px; }
.timebox{ font-weight:700; font-size:2.2rem; letter-spacing:1px; }
.badge-status{ font-size:.95rem; }
</style>
</head>
<body>
<div class="container">
  <div class="header shadow-sm d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-1"><i class="bi bi-qr-code"></i> Status Kode Presensi</h4>
      <div class="small">Kode aktif selama 5 menit sejak dibuat.</div>
    </div>
    <a href="/heavens/akun_assisten/index.php" class="btn btn-light btn-sm"><i class="bi bi-house"></i> Dashboard</a>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="fw-semibold">Mata Kuliah:</div>
              <div><?= e($kode['mata_kuliah']); ?></div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold">Pertemuan & Materi:</div>
              <div>Pertemuan: <?= (int)$kode['pertemuan_ke']; ?> | Materi: <?= e($kode['materi']); ?></div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold">Kode Presensi:</div>
              <div class="display-6"><?= e($kode['kode']); ?></div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold">Status:</div>
              <span class="badge bg-success badge-status">AKTIF</span>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold">Lokasi kode dibuat:</div>
              <div><?= e($kode['lokasi']); ?></div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold">Waktu tersisa</div>
              <div id="countdown" class="timebox">05:00</div>
            </div>
          </div>

          <div class="alert alert-warning" role="alert">
            <div class="fw-semibold">⚠️ Perhatian:</div>
            <ul class="mb-0">
              <li>Gunakan kode ini hanya untuk praktikan yang berhak.</li>
              <li>Lakukan presensi sebelum waktu habis!</li>
              <li>Login ke akun praktikan dan buka menu “Presensi”.</li>
              <li>Aktifkan lokasi di browser sebelum input kode.</li>
            </ul>
          </div>

          <div class="text-end">
            <a href="/heavens/akun_assisten/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Remaining seconds dari server (integer)
let remain = <?= $remain ?>;

// Format ke mm:ss
function toMMSS(sec) {
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return (m < 10 ? '0'+m : m) + ':' + (s < 10 ? '0'+s : s);
}

const el = document.getElementById('countdown');
el.textContent = toMMSS(remain);

// Panggil server untuk menandai expired
function markExpiredThenRedirect() {
  // POST ke halaman yang sama (action=expire)
  const form = new URLSearchParams();
  form.append('action', 'expire');
  fetch(location.pathname + location.search, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: form.toString()
  }).finally(() => {
    alert('Kode presensi sudah kadaluarsa');
    window.location.href = '/heavens/akun_assisten/index.php';
  });
}

function tick(){
  remain--;
  if (remain <= 0) {
    markExpiredThenRedirect();
    return;
  }
  el.textContent = toMMSS(remain);
  setTimeout(tick, 1000);
}
setTimeout(tick, 1000);

// Fallback hard redirect (server truth) sedikit lebih lama dari sisa waktu
setTimeout(function(){
  markExpiredThenRedirect();
}, (remain + 2) * 1000);
</script>
</body>
</html>
