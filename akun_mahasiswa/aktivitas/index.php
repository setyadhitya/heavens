<?php
// heavens/akun_mahasiswa/aktivitas/index.php
$currentPage = 'aktivitasmahasiswa';
include __DIR__ . '/../../components/helper_bubble.php';
require_once __DIR__ . '/../../fatman/functions.php';

// ===== AUTH (khusus praktikan) =====
if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'praktikan') {
    set_flash('Silakan login sebagai praktikan terlebih dahulu.', 'warning');
    header('Location: /heavens/akun_mahasiswa/login/');
    exit;
}

$pdo = db();
$praktikan_id = (int)($_SESSION['user_id'] ?? 0);

// ===== Ambil daftar praktikum yang diikuti mahasiswa (untuk dropdown) =====
$stPrak = $pdo->prepare("
    SELECT 
        p.id AS praktikum_id,
        m.mata_kuliah,
        p.kelas, p.shift, p.hari
    FROM tb_peserta ps
    JOIN tb_praktikum p ON p.id = ps.praktikum_id
    JOIN tb_matkul m ON m.id = p.mata_kuliah
    WHERE ps.praktikan_id = ?
    ORDER BY m.mata_kuliah ASC
");
$stPrak->execute([$praktikan_id]);
$praktikum_list = $stPrak->fetchAll();

if (empty($praktikum_list)) {
    // Mahasiswa belum terdaftar di praktikum manapun
    ?>
    <!doctype html>
    <html lang="id">
    <head>
    <meta charset="utf-8">
    <title>Aktivitas Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <div class="container py-5">
      <div class="alert alert-warning">Anda belum terdaftar pada praktikum manapun.</div>
      <a href="/heavens/akun_mahasiswa/" class="btn btn-secondary btn-sm">&larr; Kembali</a>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// ===== Tentukan praktikum terpilih (GET) dan validasi milik mahasiswa =====
$selected_id = (int)($_GET['praktikum_id'] ?? 0);
if ($selected_id <= 0) {
    $selected_id = (int)$praktikum_list[0]['praktikum_id']; // default: pertama
}
$allowed_ids = array_column($praktikum_list, 'praktikum_id');
if (!in_array($selected_id, array_map('intval', $allowed_ids), true)) {
    // fallback aman
    $selected_id = (int)$praktikum_list[0]['praktikum_id'];
}

// Ambil info praktikum terpilih
$info = null;
foreach ($praktikum_list as $r) {
    if ((int)$r['praktikum_id'] === $selected_id) { $info = $r; break; }
}

// ===== Ambil peserta kelas (semua mahasiswa di praktikum ini) =====
$stPes = $pdo->prepare("
    SELECT pk.id AS pid, pk.nama, pk.nim
    FROM tb_peserta ps
    JOIN tb_praktikan pk ON pk.id = ps.praktikan_id
    WHERE ps.praktikum_id = ?
    ORDER BY pk.nim ASC
");
$stPes->execute([$selected_id]);
$peserta = $stPes->fetchAll();

// ===== Total pertemuan (untuk persentase kehadiran) =====
$stTotPert = $pdo->prepare("SELECT COUNT(DISTINCT pertemuan_ke) FROM tb_kode_presensi WHERE praktikum_id = ?");
$stTotPert->execute([$selected_id]);
$total_pertemuan = (int)$stTotPert->fetchColumn();

// ===== Rekap hadir per mahasiswa (bulk) =====
$hadir_map = [];
if ($total_pertemuan > 0) {
    $stHadir = $pdo->prepare("
        SELECT praktikan_id, COUNT(*) AS jml
        FROM tb_presensi
        WHERE praktikum_id = ? AND status = 'Hadir'
        GROUP BY praktikan_id
    ");
    $stHadir->execute([$selected_id]);
    foreach ($stHadir->fetchAll() as $h) {
        $hadir_map[(int)$h['praktikan_id']] = (int)$h['jml'];
    }
}

// ===== Ambil 5 tugas pertama berdasarkan pertemuan_ke (1..5) =====
// N1..N4..Responsi = tugas pertemuan 1..5
$stTugas = $pdo->prepare("
    SELECT id, pertemuan_ke
    FROM tb_tugas
    WHERE praktikum_id = ?
    ORDER BY pertemuan_ke ASC, id ASC
");
$stTugas->execute([$selected_id]);
$tugas_all = $stTugas->fetchAll();

// Petakan tugas id utk pertemuan 1..5
$tugas_map = [1 => null, 2 => null, 3 => null, 4 => null, 5 => null];
foreach ($tugas_all as $t) {
    $p = (int)$t['pertemuan_ke'];
    if ($p >= 1 && $p <= 5 && $tugas_map[$p] === null) {
        $tugas_map[$p] = (int)$t['id'];
    }
}
$tugas_ids = array_values(array_filter($tugas_map));

// ===== Ambil nilai kumpul tugas untuk 5 tugas tsb (bulk) =====
$nilai_map = []; // [praktikan_id][tugas_id] = nilai (bisa NULL)
if (!empty($tugas_ids)) {
    $in = implode(',', array_fill(0, count($tugas_ids), '?'));
    $params = $tugas_ids;
    array_unshift($params, $selected_id); // praktikum_id di depan
    $stNil = $pdo->prepare("
        SELECT praktikan_id, tugas_id, nilai
        FROM tb_kumpul_tugas
        WHERE praktikum_id = ? AND tugas_id IN ($in)
    ");
    $stNil->execute($params);
    foreach ($stNil->fetchAll() as $n) {
        $nilai_map[(int)$n['praktikan_id']][(int)$n['tugas_id']] = is_null($n['nilai']) ? null : (int)$n['nilai'];
    }
}

// ===== Helper fungsi =====
function e2($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function fmt_index_huruf(float $avg): string {
    if ($avg >= 85) return 'A';
    if ($avg >= 75) return 'B';
    if ($avg >= 65) return 'C';
    if ($avg >= 50) return 'D';
    return 'E';
}
function badge_index(string $idx): string {
    // Warna maroon style: gunakan badge dengan outline/kontras
    $color = [
        'A'=>'success','B'=>'primary','C'=>'warning text-dark','D'=>'secondary','E'=>'danger'
    ][$idx] ?? 'secondary';
    return '<span class="badge bg-'.$color.'">'.$idx.'</span>';
}
function badge_ket(bool $lulus): string {
    return $lulus
        ? '<span class="badge" style="background:#198754">LULUS</span>'
        : '<span class="badge" style="background:#dc3545">TIDAK LULUS</span>';
}

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Aktivitas Mahasiswa</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{ background:#f6f1f1; } /* soft background */
  .header{
    padding:20px;
    background: linear-gradient(135deg, #6b0f1a, #b91372); /* maroon-ish */
    color:#fff; border-radius:12px; margin:20px 0 16px;
  }
  .card{ border-radius:14px; }
  .table-sticky thead th{
    position: sticky; top:0; z-index: 5;
    background:#fff; /* white header */
    box-shadow: 0 1px 0 rgba(0,0,0,.06);
  }
  .table thead th { white-space: nowrap; }
  .small-muted{ color:#6c757d; font-size:.9rem; }
</style>
</head>
<body>

<div class="container">
  <div class="header shadow-sm d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-1"><i class="bi bi-activity"></i> Aktivitas Mahasiswa</h4>
      <div class="small">Lihat nilai dan kehadiran kelas dengan tampilan sederhana & transparan.</div>
    </div>
    <a href="/heavens/akun_mahasiswa/" class="btn btn-light btn-sm">
      <i class="bi bi-house"></i> Beranda
    </a>
  </div>

  <!-- Pilih Praktikum -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="get" class="row g-2 align-items-end">
        <div class="col-md-8">
          <label class="form-label mb-1">Pilih Praktikum</label>
          <select name="praktikum_id" class="form-select" onchange="this.form.submit()">
            <?php foreach ($praktikum_list as $p): 
              $label = $p['mata_kuliah'];
              if (!empty($p['kelas'])) $label .= ' • Kelas ' . e2($p['kelas']);
              if (!empty($p['shift'])) $label .= ' • Shift ' . e2($p['shift']);
              if (!empty($p['hari']))  $label .= ' • ' . e2($p['hari']);
            ?>
              <option value="<?= (int)$p['praktikum_id'] ?>" <?= ((int)$p['praktikum_id']===$selected_id?'selected':'') ?>>
                <?= e2($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 text-end">
          <div class="small-muted">Menampilkan peserta satu kelas yang sama.</div>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Peserta & Nilai -->
  <div class="card shadow-sm">
    <div class="card-body p-3 p-md-4">
      <?php if (empty($peserta)): ?>
        <div class="alert alert-info mb-0">Belum ada peserta pada praktikum ini.</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped align-middle table-sticky">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama</th>
              <th>NIM</th>
              <th>Kehadiran</th>
              <th>Nilai 1</th>
              <th>Nilai 2</th>
              <th>Nilai 3</th>
              <th>Nilai 4</th>
              <th>Responsi</th>
              <th>Rata-rata</th>
              <th>Index</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no=1;
            foreach ($peserta as $mhs):
                $pid = (int)$mhs['pid'];

                // Kehadiran %
                $hadir = $hadir_map[$pid] ?? 0;
                $keh_pct = ($total_pertemuan > 0) ? round(($hadir / $total_pertemuan) * 100) : 0;

                // Ambil nilai untuk tugas pertemuan 1..5 (N1..N4..Resp)
                $getNil = function($pid, $tid) use ($nilai_map) {
                    if (!$tid) return null;
                    return $nilai_map[$pid][$tid] ?? null;
                };
                $n1 = $getNil($pid, $tugas_map[1]);
                $n2 = $getNil($pid, $tugas_map[2]);
                $n3 = $getNil($pid, $tugas_map[3]);
                $n4 = $getNil($pid, $tugas_map[4]);
                $nr = $getNil($pid, $tugas_map[5]); // responsi

                // Tampilkan nilai (null -> '-'), tapi hitung 0
                $disp = function($v){ return is_null($v) ? '-' : (int)$v; };
                $v1 = is_null($n1) ? 0 : (int)$n1;
                $v2 = is_null($n2) ? 0 : (int)$n2;
                $v3 = is_null($n3) ? 0 : (int)$n3;
                $v4 = is_null($n4) ? 0 : (int)$n4;
                $vr = is_null($nr) ? 0 : (int)$nr;

                // Rata-rata (6 komponen: N1..N4 + Responsi + Kehadiran)
                $avg = round(($v1 + $v2 + $v3 + $v4 + $vr + $keh_pct) / 6, 2);
                $idx = fmt_index_huruf($avg);

                // Keterangan by kehadiran
                $lulus = ($keh_pct >= 75);
            ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= e2($mhs['nama']) ?></td>
              <td><?= e2($mhs['nim']) ?></td>
              <td><?= $keh_pct ?>%</td>
              <td><?= $disp($n1) ?></td>
              <td><?= $disp($n2) ?></td>
              <td><?= $disp($n3) ?></td>
              <td><?= $disp($n4) ?></td>
              <td><?= $disp($nr) ?></td>
              <td><?= number_format($avg, 2) ?></td>
              <td><?= badge_index($idx) ?></td>
              <td><?= badge_ket($lulus) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="small text-muted mt-2">
        * Rata-rata = (Nilai1 + Nilai2 + Nilai3 + Nilai4 + Responsi + Kehadiran) / 6.
        Nilai kosong ditampilkan “-” namun dihitung 0 pada rata-rata. Index: A(≥85), B(≥75), C(≥65), D(≥50), E(<50).
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
