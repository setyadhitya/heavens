<?php
// FILE: heavens/fatman/rekap/index.php
require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Rekap Presensi</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .navbar-nav .nav-link {
    white-space: nowrap;
  }
</style>

</head>
<body class="bg-light">
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Rekap Presensi</h4>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">

      <div id="alertArea"></div>

      <!-- Filter -->
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1">Filter Kelas</label>
          <select id="filter_kelas" class="form-select">
            <option value="">— Semua Kelas —</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label mb-1">Nama Praktikum</label>
          <select id="select_praktikum" class="form-select">
            <option value="">— Pilih Praktikum —</option>
          </select>
        </div>
        <div class="col-md-4 text-end">
          <button id="btnExportPDF" class="btn btn-success" disabled>📄 Export PDF</button>
        </div>
      </div>

      <!-- Info praktikum -->
      <div id="infoBox" class="mt-3 d-none">
        <div class="row g-2 text-muted small">
          <div class="col-md-4"><b>Jurusan:</b> <span id="info_jurusan">-</span></div>
          <div class="col-md-4"><b>Semester:</b> <span id="info_semester">-</span></div>
          <div class="col-md-4"><b>Shift:</b> <span id="info_shift">-</span></div>
          <div class="col-md-4"><b>Jam:</b> <span id="info_jam">-</span></div>
          <div class="col-md-8"><b>Tanggal Pelaksanaan:</b> <span id="info_tanggal">-</span></div>
          <div class="col-12"><b>Asisten:</b> <span id="info_asisten">-</span></div>
        </div>
      </div>

      <hr class="my-3">

      <!-- Loading -->
      <div id="loading" class="text-muted d-none">Memuat data rekap...</div>

      <!-- TABEL 1: Rekap Praktikan -->
      <div id="tabel1Wrap" class="d-none">
        <h6 class="fw-semibold">Tabel 1 – Rekap Praktikan</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle table-sm" id="tabel1">
            <thead class="table-dark">
              <tr id="tabel1_head">
                <th>Nama</th>
                <!-- kolom pertemuan dinamis -->
                <th>Total</th>
                <th>% Hadir</th>
              </tr>
            </thead>
            <tbody id="tabel1_body"></tbody>
          </table>
        </div>
      </div>

      <!-- TABEL 2: Daftar Pertemuan -->
      <div id="tabel2Wrap" class="d-none mt-4">
        <h6 class="fw-semibold">Tabel 2 – Daftar Pertemuan</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle table-sm" id="tabel2">
            <thead class="table-dark">
              <tr>
                <th>Pertemuan</th>
                <th>Lokasi</th>
                <th>Tanggal</th>
              </tr>
            </thead>
            <tbody id="tabel2_body"></tbody>
          </table>
        </div>
      </div>

      <!-- TABEL 3: Asisten -->
      <div id="tabel3Wrap" class="d-none mt-4">
        <h6 class="fw-semibold">Tabel 3 – Asisten Praktikum</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle table-sm" id="tabel3">
            <thead class="table-dark">
              <tr id="tabel3_head">
                <th>Nama</th>
                <!-- kolom pertemuan dinamis -->
                <th>Total</th>
              </tr>
            </thead>
            <tbody id="tabel3_body"></tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- jsPDF + autotable -->
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>

<!-- Bootstrap JS (as you asked) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const csrfToken = '<?= e(csrf_token()); ?>';

const selectKelas = document.getElementById('filter_kelas');
const selectPraktikum = document.getElementById('select_praktikum');
const btnExportPDF = document.getElementById('btnExportPDF');
const loading = document.getElementById('loading');

let rekapData = null;     // cache JSON rekap terakhir
let pertemuanKeys = [];   // daftar pertemuan dinamis

// Load kelas dropdown
async function loadKelas() {
  const res = await fetch('rekap_action.php?action=kelas_list', { credentials: 'same-origin' });
  const data = await res.json();
  selectKelas.innerHTML = '<option value="">— Semua Kelas —</option>';
  data.forEach(k => {
    const opt = document.createElement('option');
    opt.value = k.kelas;
    opt.textContent = k.kelas;
    selectKelas.appendChild(opt);
  });
}

// Load praktikum dropdown (optional filter kelas)
async function loadPraktikum(kelas = '') {
  const url = new URL('rekap_action.php', location.href);
  url.searchParams.set('action', 'praktikum_dropdown');
  if (kelas) url.searchParams.set('kelas', kelas);
  const res = await fetch(url, { credentials: 'same-origin' });
  const data = await res.json();
  selectPraktikum.innerHTML = '<option value="">— Pilih Praktikum —</option>';
  data.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    opt.textContent = `${p.mata_kuliah} (${p.kelas})`;
    selectPraktikum.appendChild(opt);
  });
}

// Fetch rekap
async function fetchRekap(praktikumId) {
  loading.classList.remove('d-none');
  rekapData = null;
  try {
    const url = new URL('rekap_action.php', location.href);
    url.searchParams.set('action', 'rekap');
    url.searchParams.set('praktikum_id', praktikumId);
    const res = await fetch(url, { credentials: 'same-origin' });
    const data = await res.json();
    rekapData = data;
    renderRekap(data);
  } catch (e) {
    console.error(e);
    document.getElementById('alertArea').innerHTML =
      '<div class="alert alert-danger">Gagal memuat data rekap.</div>';
  } finally {
    loading.classList.add('d-none');
  }
}

// Render rekap -> info + tabel
function renderRekap(data) {
  // Info
  const infoWrap = document.getElementById('infoBox');
  document.getElementById('info_jurusan').textContent  = data.info.jurusan || '-';
  document.getElementById('info_semester').textContent = data.info.semester || '-';
  document.getElementById('info_shift').textContent    = data.info.shift || '-';
  document.getElementById('info_jam').textContent      = data.info.jam || '-';
  document.getElementById('info_tanggal').textContent  = data.info.tanggal_awal + ' – ' + data.info.tanggal_akhir;
  document.getElementById('info_asisten').textContent  = data.info.asisten || '-';
  infoWrap.classList.remove('d-none');

  // pertemuanKeys (unik & terurut ASC, max 10)
  pertemuanKeys = (data.pertemuan_keys || []).slice(0,10);

  // TABEL 1
  const t1Head = document.getElementById('tabel1_head');
  const t1Body = document.getElementById('tabel1_body');
  // reset head (Nama + dynamic pertemuan + Total + %)
  t1Head.innerHTML = '<th>Nama</th>';
  pertemuanKeys.forEach(k=>{
    const th = document.createElement('th');
    th.textContent = k;
    t1Head.appendChild(th);
  });
  t1Head.insertAdjacentHTML('beforeend','<th>Total</th><th>% Hadir</th>');

  // body
  t1Body.innerHTML = '';
  data.tabel1.forEach(row=>{
    let tr = document.createElement('tr');
    tr.innerHTML = `<td>${row.nama_praktikan}</td>`;
    pertemuanKeys.forEach(k=>{
      const val = row.detail[k] ?? 0;
      tr.insertAdjacentHTML('beforeend', `<td class="text-center">${val === 1 ? '✅' : '❌'}</td>`);
    });
    tr.insertAdjacentHTML('beforeend', `<td class="text-center fw-semibold">${row.total_hadir}</td>`);
    tr.insertAdjacentHTML('beforeend', `<td class="text-center">${row.persen_hadir}%</td>`);
    t1Body.appendChild(tr);
  });
  document.getElementById('tabel1Wrap').classList.remove('d-none');

  // TABEL 2
  const t2Body = document.getElementById('tabel2_body');
  t2Body.innerHTML = '';
  data.tabel2.forEach(r=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-center">${r.pertemuan_ke}</td>
      <td class="text-center">${r.lokasi}</td>
      <td class="text-center">${r.tanggal}</td>
    `;
    t2Body.appendChild(tr);
  });
  document.getElementById('tabel2Wrap').classList.remove('d-none');

  // TABEL 3
  const t3Head = document.getElementById('tabel3_head');
  const t3Body = document.getElementById('tabel3_body');
  t3Head.innerHTML = '<th>Nama</th>';
  pertemuanKeys.forEach(k=>{
    const th = document.createElement('th');
    th.textContent = k;
    t3Head.appendChild(th);
  });
  t3Head.insertAdjacentHTML('beforeend','<th>Total</th>');
  t3Body.innerHTML = '';
  data.tabel3.forEach(a=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${a.nama}</td>`;
    pertemuanKeys.forEach(k=>{
      tr.insertAdjacentHTML('beforeend','<td class="text-center">—</td>');
    });
    tr.insertAdjacentHTML('beforeend','<td class="text-center">—</td>');
    t3Body.appendChild(tr);
  });
  document.getElementById('tabel3Wrap').classList.remove('d-none');

  // Enable Export
  btnExportPDF.disabled = false;
}

// Export PDF
btnExportPDF.addEventListener('click', () => {
  if (!rekapData) return;
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation: 'landscape' });

  // Title
  doc.setFontSize(14);
  doc.text('Rekap Presensi Praktikum', 14, 15);
  // Info
  doc.setFontSize(10);
  doc.text(`Jurusan: ${rekapData.info.jurusan}`, 14, 25);
  doc.text(`Semester: ${rekapData.info.semester}`, 14, 30);
  doc.text(`Shift: ${rekapData.info.shift}`, 14, 35);
  doc.text(`Jam: ${rekapData.info.jam}`, 14, 40);
  doc.text(`Asisten: ${rekapData.info.asisten}`, 14, 45);
  doc.text(`Tanggal Pelaksanaan: ${rekapData.info.tanggal_awal} – ${rekapData.info.tanggal_akhir}`, 14, 50);

  // Table 1
  doc.text('Tabel 1 – Rekap Praktikan', 14, 60);
  const head1 = [['Nama', ...pertemuanKeys.map(String), 'Total', '% Hadir']];
  const body1 = rekapData.tabel1.map(p => {
    const cols = pertemuanKeys.map(k => (p.detail[k] === 1 ? '✅' : '❌'));
    return [p.nama_praktikan, ...cols, String(p.total_hadir), String(p.persen_hadir)];
  });
  doc.autoTable({ startY: 65, head: head1, body: body1, styles: { fontSize: 8 }, theme: 'grid' });

  // Table 2
  let finalY = doc.lastAutoTable.finalY + 10;
  doc.text('Tabel 2 – Daftar Pertemuan', 14, finalY);
  const head2 = [['Pertemuan','Lokasi','Tanggal']];
  const body2 = rekapData.tabel2.map(r => [String(r.pertemuan_ke), r.lokasi, r.tanggal]);
  doc.autoTable({ startY: finalY + 5, head: head2, body: body2, styles: { fontSize: 8 }, theme: 'grid' });

  // Table 3
  finalY = doc.lastAutoTable.finalY + 10;
  doc.text('Tabel 3 – Asisten Praktikum', 14, finalY);
  const head3 = [['Nama', ...pertemuanKeys.map(String), 'Total']];
  const body3 = rekapData.tabel3.map(a => [a.nama, ...pertemuanKeys.map(()=> '—'), '—']);
  doc.autoTable({ startY: finalY + 5, head: head3, body: body3, styles: { fontSize: 8 }, theme: 'grid' });

  const namaFile = `Rekap_Presensi_${rekapData.info.jurusan}_${rekapData.info.shift}.pdf`;
  doc.save(namaFile.replace(/\s+/g,'_'));
});

// Events
selectKelas.addEventListener('change', async (e) => {
  await loadPraktikum(e.target.value);
  document.getElementById('infoBox').classList.add('d-none');
  document.getElementById('tabel1Wrap').classList.add('d-none');
  document.getElementById('tabel2Wrap').classList.add('d-none');
  document.getElementById('tabel3Wrap').classList.add('d-none');
  btnExportPDF.disabled = true;
});

selectPraktikum.addEventListener('change', async (e) => {
  const id = e.target.value;
  if (!id) {
    btnExportPDF.disabled = true;
    return;
  }
  await fetchRekap(id);
});

// Init
(async function init(){
  await loadKelas();
  await loadPraktikum('');
})();
</script>
</body>
</html>
