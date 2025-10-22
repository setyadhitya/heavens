<?php
// Public page – read-only
// Menggunakan koneksi PDO dari functions.php

$currentPage = 'jadwal'; // atau 'detail_modul', 'home', dll sesuai tb_helper.halaman
include __DIR__ . '/../components/helper_bubble.php';

require_once __DIR__ . '/../fatman/functions.php';
$pdo = db();

// Konstanta tampilan hari & shift
$hari_list  = ['senin'=>'Senin','selasa'=>'Selasa','rabu'=>'Rabu','kamis'=>'Kamis','jumat'=>'Jumat'];

// Di DB shift kamu berformat I, II, III, IV, V
// Kita tampilkan sebagai "Shift I" dst, tapi kunci internal tetap I..V
$shift_keys  = ['I','II','III','IV','V'];
$shift_label = ['I'=>'Shift I','II'=>'Shift II','III'=>'Shift III','IV'=>'Shift IV','V'=>'Shift V'];

// Ambil semua jadwal (gabung matkul & jurusan)
$sql = "
  SELECT 
    p.id, p.hari, p.shift, p.kelas, p.jurusan_id, p.semester,
    DATE_FORMAT(p.jam_mulai,'%H:%i') AS jam_mulai,
    DATE_FORMAT(p.jam_ahir,'%H:%i')  AS jam_ahir,
    p.catatan,
    m.mata_kuliah,
    j.jurusan
  FROM tb_praktikum p
  JOIN tb_matkul   m ON p.mata_kuliah = m.id
  LEFT JOIN tb_jurusan j ON j.id = p.jurusan_id
  ORDER BY FIELD(p.hari,'senin','selasa','rabu','kamis','jumat'),
           FIELD(p.shift,'I','II','III','IV','V')
";
$stmt = $pdo->query($sql);

// Susun ke matriks [shiftKey][hari] = row
$grid = [];
if ($stmt) {
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $shiftKey = $row['shift']; // I/II/III/IV/V
    $hariKey  = $row['hari'];  // senin..jumat
    $grid[$shiftKey][$hariKey] = $row;
  }
}

// warna semester (soft)
$semBg = [
  1 => '#dceeff', // biru muda
  2 => '#d9f9e1', // hijau muda
  3 => '#ffdede', // merah lembut
  4 => '#fff2ce', // kuning lembut
  5 => '#e3f2fd', // biru langit
  6 => '#f2f2f2', // abu lembut
];

// helper escape kecil
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// dapatkan warna semester
function sem_color($semester, $map) {
  $sem = (int)$semester;
  return $map[$sem] ?? '#ffffff';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Jadwal Praktikum</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; }
    .table th, .table td { vertical-align: middle; text-align: center; }
    .cell-empty { color:#bbb; font-style: italic; }
    .matkul-btn {
      display: block;
      width: 100%;
      padding: 6px;
      border: none;
      background: rgba(255,255,255,0.7);
      border-radius: 6px;
      font-weight: 600;
      color: #333;
      cursor: pointer;
      transition: 0.2s;
      text-align: center;
    }
    .matkul-btn:hover {
      background: rgba(255,255,255,0.95);
      box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    }
    .legend .badge { min-width: 90px; }
    .legend-box {
      display:inline-block; width:14px; height:14px; border-radius:3px; margin-right:6px; border:1px solid rgba(0,0,0,.08);
      vertical-align:middle;
    }
  </style>
</head>
<body>
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Jadwal Praktikum</h3>
    <a href="/heavens/" class="btn btn-outline-secondary btn-sm">Beranda</a>
  </div>

  <!-- Legend Semester -->
  <div class="card mb-3 shadow-sm">
    <div class="card-body py-2">
      <div class="legend">
        <?php for($i=1;$i<=6;$i++): ?>
          <span class="me-3">
            <span class="legend-box" style="background: <?= h($semBg[$i] ?? '#fff'); ?>;"></span>
            Semester <?= $i; ?>
          </span>
        <?php endfor; ?>
      </div>
    </div>
  </div>

  <!-- Tabel Jadwal -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th style="width:110px">Shift</th>
              <?php foreach ($hari_list as $label): ?>
                <th><?= h($label); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($shift_keys as $sKey): ?>
              <tr>
                <th><?= h($shift_label[$sKey]); ?></th>
                <?php foreach ($hari_list as $hariKey => $hariLabel): ?>
                  <?php if (!empty($grid[$sKey][$hariKey])): 
                    $row = $grid[$sKey][$hariKey];
                    $bg  = sem_color($row['semester'], $semBg);
                  ?>
                    <td style="background: <?= h($bg); ?>;">
                      <button class="matkul-btn"
                              data-id="<?= (int)$row['id']; ?>"
                              data-bs-toggle="modal"
                              data-bs-target="#detailModal">
                        <?= h($row['mata_kuliah']); ?> (<?= h($row['kelas']); ?>)
                      </button>
                    </td>
                  <?php else: ?>
                    <td><span class="cell-empty">-</span></td>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <footer class="text-center text-muted small mt-3">
    © 2025 LabKom 3 Jaringan • Dibuat setengah semangat oleh PLP ☕ • Lab 3 Jaringan Komputer
  </footer>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailTitle">Detail Praktikum</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="detailBody">
          <div class="text-center text-muted py-3">Memuat...</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.matkul-btn');
  if (!btn) return;

  const id = btn.dataset.id;
  const titleEl = document.getElementById('detailTitle');
  const bodyEl  = document.getElementById('detailBody');
  titleEl.textContent = 'Detail Praktikum';
  bodyEl.innerHTML = '<div class="text-center text-muted py-3">Memuat...</div>';

  try {
    const res = await fetch('jadwal_action.php?id=' + encodeURIComponent(id));
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    titleEl.textContent = data.mata_kuliah || 'Detail Praktikum';

    const assisten = (data.assisten && data.assisten.length) ? data.assisten.join(', ') : '-';
    const pesertaCount = (data.peserta && data.peserta.length) ? data.peserta.length : 0;
    const pesertaList = (data.peserta && data.peserta.length)
      ? '<ol class="mb-0"><li>' + data.peserta.map(escapeHtml).join('</li><li>') + '</li></ol>'
      : '-';

    const html = `
      <div class="row">
        <div class="col-md-6">
          <div class="mb-2"><strong>Jurusan:</strong> ${escapeHtml(data.jurusan || '-')}</div>
          <div class="mb-2"><strong>Kelas:</strong> ${escapeHtml(data.kelas || '-')}</div>
          <div class="mb-2"><strong>Semester:</strong> ${escapeHtml(String(data.semester || '-'))}</div>
          <div class="mb-2"><strong>Hari:</strong> ${escapeHtml(capitalize(data.hari || '-'))}</div>
          <div class="mb-2"><strong>Shift:</strong> ${escapeHtml(data.shift_label || data.shift || '-')}</div>
          <div class="mb-2"><strong>Jam:</strong> ${escapeHtml((data.jam_mulai || '-') + ' - ' + (data.jam_ahir || '-'))}</div>
          <div class="mb-2"><strong>Assisten:</strong> ${escapeHtml(assisten)}</div>
        </div>
        <div class="col-md-6">
          <div class="mb-2"><strong>Peserta:</strong> ${pesertaCount}</div>
          <div class="mb-2"><strong>Nama Peserta:</strong><br>${pesertaList}</div>
          <div class="mb-2"><strong>Catatan:</strong> ${escapeHtml(data.catatan || '-')}</div>
        </div>
      </div>
    `;
    bodyEl.innerHTML = html;
  } catch (err) {
    bodyEl.innerHTML = '<div class="text-danger">Gagal memuat detail. ' + escapeHtml(err.message) + '</div>';
    console.error(err);
  }
});

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'",'&#039;');
}
function capitalize(s){ s = String(s||''); return s ? s.charAt(0).toUpperCase()+s.slice(1) : s; }
</script>
</body>
</html>
