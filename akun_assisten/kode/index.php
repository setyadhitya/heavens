<?php
// heavens/akun_assisten/kode/index.php
require_once __DIR__ . '/../../fatman/functions.php';

function js_alert_redirect(string $msg, string $to) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
    echo '<script>alert('.json_encode($msg).');window.location.href='.json_encode($to).';</script>';
    echo '</body></html>';
    exit;
}

// ===== AUTH =====
$assisten_id = $_SESSION['user_id'] ?? null;
if (!$assisten_id) {
    js_alert_redirect('Silakan login sebagai assisten terlebih dahulu.', '/heavens/akun_assisten/login/');
}

$nama     = $_SESSION['user_nama']    ?? 'Assisten';
$nim      = $_SESSION['user_nim']     ?? '-';
$nomorhp  = $_SESSION['user_nomorhp'] ?? '-';

$pdo = db();

// ===== CEK KODE AKTIF (ambil yang terbaru) =====
$cekAktif = $pdo->prepare("
    SELECT id, pertemuan_ke
    FROM tb_kode_presensi
    WHERE generated_by_assisten_id = ? AND status = 'aktif'
    ORDER BY id DESC
    LIMIT 1
");
$cekAktif->execute([$assisten_id]);
$aktif = $cekAktif->fetch();
if ($aktif) {
    js_alert_redirect(
        'Masih ada kode presensi yang aktif (Pertemuan ke-' . $aktif['pertemuan_ke'] . '). Harap selesaikan dulu.',
        '/heavens/akun_assisten/kode/status/index.php?kid=' . $aktif['id']
    );
}

// ===== AMBIL DAFTAR PRAKTIKUM (JOIN tb_matkul agar tampil nama matkul) =====
try {
    $stmt = $pdo->prepare("
        SELECT
            p.id,
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
    $stmt->execute([$assisten_id]);
    $praktikum_list = $stmt->fetchAll();
} catch (Exception $e) {
    js_alert_redirect('Gagal memuat data praktikum: ' . $e->getMessage(), '/heavens/akun_assisten/index.php');
}

$has_praktikum = !empty($praktikum_list);

// ===== HANDLE SUBMIT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $praktikum_id = (int)($_POST['praktikum_id'] ?? 0);
    $pertemuan    = (int)($_POST['pertemuan'] ?? 0);
    $materi       = trim($_POST['materi'] ?? '');
    $kode         = strtolower(trim($_POST['kode'] ?? ''));
    $lokasi       = trim($_POST['lokasi'] ?? ''); // "lat,lng"

    if (!$praktikum_id) js_alert_redirect('Silakan pilih praktikum.', './index.php');

    // Pastikan praktikum milik assisten
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_assisten_praktikum WHERE assisten_id = ? AND praktikum_id = ?");
    $stmt->execute([$assisten_id, $praktikum_id]);
    if ((int)$stmt->fetchColumn() === 0) js_alert_redirect('Anda tidak terdaftar pada praktikum tersebut.', './index.php');

    // Validasi dasar
    if ($pertemuan < 1 || $pertemuan > 10) js_alert_redirect('Pertemuan harus 1 sampai 10.', './index.php');
    if ($materi === '') js_alert_redirect('Materi tidak boleh kosong.', './index.php');

    if ($kode === '' || strlen($kode) > 5 || !preg_match('/^[a-z0-9]{1,5}$/', $kode)) {
        js_alert_redirect('Kode harus 1–5 karakter alfanumerik (tanpa spasi).', './index.php');
    }
    if ($lokasi === '' || !preg_match('/^-?\d+\.\d+,-?\d+\.\d+$/', $lokasi)) {
        js_alert_redirect('Lokasi belum terbaca. Aktifkan GPS browser dan izinkan akses lokasi.', './index.php');
    }

    // Aturan pertemuan berurutan (MAX + 1)
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(pertemuan_ke),0) FROM tb_kode_presensi WHERE praktikum_id = ?");
    $stmt->execute([$praktikum_id]);
    $maxp = (int)$stmt->fetchColumn();
    $next = $maxp === 0 ? 1 : $maxp + 1;
    if ($pertemuan !== $next) {
        js_alert_redirect('Pertemuan tidak boleh melompat. Pertemuan berikutnya: ke-' . $next . '.', './index.php');
    }

    // Kombinasi praktikum + pertemuan harus unik
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_kode_presensi WHERE praktikum_id = ? AND pertemuan_ke = ?");
    $stmt->execute([$praktikum_id, $pertemuan]);
    if ((int)$stmt->fetchColumn() > 0) js_alert_redirect('Kode untuk pertemuan tersebut sudah pernah dibuat.', './index.php');

    // Kode harus unik global
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_kode_presensi WHERE kode = ?");
    $stmt->execute([$kode]);
    if ((int)$stmt->fetchColumn() > 0) js_alert_redirect('Kode sudah digunakan. Gunakan kode lain.', './index.php');

    // Insert
    try {
        $ins = $pdo->prepare("
            INSERT INTO tb_kode_presensi
            (praktikum_id, kode, status, pertemuan_ke, materi, lokasi, generated_by_assisten_id)
            VALUES (?,?,?,?,?,?,?)
        ");
        $ok = $ins->execute([$praktikum_id, $kode, 'aktif', $pertemuan, $materi, $lokasi, $assisten_id]);
        if ($ok) {
            $kid = $pdo->lastInsertId();
            header('Location: /heavens/akun_assisten/kode/status/index.php?kid=' . urlencode($kid));
            exit;
        }
        js_alert_redirect('Gagal menyimpan kode presensi.', './index.php');
    } catch (Exception $e) {
        js_alert_redirect('Gagal menyimpan: ' . $e->getMessage(), './index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Buat Kode Presensi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f4f6f9; }
.header { padding:22px; background:linear-gradient(135deg,#0d6efd,#00b4d8); color:#fff; border-radius:12px; margin:20px 0 16px; }
.card { border-radius:14px; }
.small-muted{ color:#6c757d; font-size:.9rem; }
.code-input { letter-spacing:2px; text-transform:lowercase; }
</style>
</head>
<body>
<div class="container">
  <div class="header shadow-sm">
    <h4 class="mb-1"><i class="bi bi-qr-code"></i> Buat Kode Presensi</h4>
    <div class="small">Kode berlaku 5 menit setelah dibuat.</div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="mb-3">
            <div class="fw-semibold">Halo, <?= e($nama) ?> (Assisten)</div>
            <div class="small-muted">NIM: <?= e($nim) ?> | No. HP: <?= e($nomorhp) ?></div>
          </div>

          <?php if (!$has_praktikum): ?>
            <div class="alert alert-warning">Anda belum terdaftar pada praktikum manapun. Hubungi koordinator untuk penugasan.</div>
            <a href="/heavens/akun_assisten/index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
          <?php else: ?>
          <form method="post" onsubmit="return ensureLocation()">
            <div class="mb-3">
              <label class="form-label">Mata Kuliah / Praktikum</label>
              <select name="praktikum_id" class="form-select" required>
                <option value="">— Pilih Praktikum —</option>
                <?php foreach ($praktikum_list as $p): ?>
                  <?php
                    $label = $p['mata_kuliah'];
                    if (!empty($p['kelas'])) $label .= ' • Kelas ' . e($p['kelas']);
                    if (!empty($p['shift'])) $label .= ' • Shift ' . e($p['shift']);
                    if (!empty($p['hari']))  $label .= ' • ' . e($p['hari']);
                  ?>
                  <option value="<?= (int)$p['id'] ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="small-muted mt-1">Daftar otomatis dari penugasan Anda.</div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Pertemuan</label>
                <select name="pertemuan" class="form-select" required>
                  <?php for ($i=1; $i<=10; $i++): ?>
                    <option value="<?= $i ?>">Pertemuan <?= $i ?></option>
                  <?php endfor; ?>
                </select>
                <div class="small-muted mt-1">Tidak boleh melompat (harus berurutan).</div>
              </div>
              <div class="col-md-8">
                <label class="form-label">Materi</label>
                <input type="text" name="materi" class="form-control" maxlength="100" placeholder="contoh: JOIN dasar, trigger" required>
              </div>
            </div>

            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label class="form-label">Kode (maks 5 karakter)</label>
                <input type="text" name="kode" class="form-control code-input" maxlength="5" pattern="[A-Za-z0-9]{1,5}" placeholder="mis. 4h5x9" required oninput="this.value=this.value.toLowerCase()">
                <div class="small-muted mt-1">Hanya huruf/angka, tanpa spasi.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Lokasi</label>
                <div class="form-control" id="locPreview" style="background:#f8f9fa">Mencari lokasi...</div>
                <input type="hidden" name="lokasi" id="lokasi" value="">
                <div class="small-muted mt-1">Aktifkan lokasi (GPS) di browser, lalu izinkan saat diminta.</div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
              <a href="/heavens/akun_assisten/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Batal</a>
              <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Buat Kode</button>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function ensureLocation(){
  const val = document.getElementById('lokasi').value.trim();
  if(!val){
    alert('Lokasi belum terbaca. Izinkan akses lokasi pada browser.');
    return false;
  }
  return true;
}
(function getLocation(){
  if (!navigator.geolocation){
    document.getElementById('locPreview').textContent = 'Geolocation tidak didukung.';
    return;
  }
  navigator.geolocation.getCurrentPosition(function(pos){
    const lat = pos.coords.latitude.toFixed(7);
    const lng = pos.coords.longitude.toFixed(7);
    document.getElementById('lokasi').value = lat+','+lng;
    document.getElementById('locPreview').textContent = lat+','+lng;
  }, function(err){
    document.getElementById('locPreview').textContent = 'Lokasi gagal dibaca ('+err.message+').';
  }, {enableHighAccuracy:true, timeout:10000, maximumAge:0});
})();
</script>
</body>
</html>
