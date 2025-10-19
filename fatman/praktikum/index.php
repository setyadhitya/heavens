<?php
require_once __DIR__ . '/../functions.php';
require_admin();
include '../navbar.php';

// === Ambil data dropdown ===
$matkulData = $mysqli->query("SELECT * FROM tb_matkul ORDER BY mata_kuliah ASC")->fetch_all(MYSQLI_ASSOC);
$jurusanData = $mysqli->query("SELECT * FROM tb_jurusan ORDER BY jurusan ASC")->fetch_all(MYSQLI_ASSOC);
$assistenListData = $mysqli->query("SELECT id, nama, nim FROM tb_assisten WHERE status='aktif' ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);

// === Waktu dropdown (07:30 - 19:30) ===
$jam_list = [];
for ($h = 7; $h <= 19; $h++) foreach ([0, 30] as $m) $jam_list[] = sprintf("%02d:%02d", $h, $m);

// === Ambil semua data praktikum ===
$result = $mysqli->query("
  SELECT p.*, m.mata_kuliah AS nama_matkul,
         (
           SELECT GROUP_CONCAT(a.nama ORDER BY ap.id ASC SEPARATOR ', ')
           FROM tb_assisten_praktikum ap
           JOIN tb_assisten a ON ap.assisten_id = a.id
           WHERE ap.praktikum_id = p.id
         ) AS daftar_assisten
  FROM tb_praktikum p
  JOIN tb_matkul m ON p.mata_kuliah = m.id
  ORDER BY p.id DESC
");
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Data Praktikum</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.modal { z-index: 1050 !important; }
.modal-backdrop { z-index: 1040 !important; }
</style>
</head>

<body class="bg-light">
<div class="container mt-4">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Daftar Praktikum</h5>
      <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah</button>
    </div>
    <div class="card-body">
      <div id="alertArea"></div>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>#</th><th>Mata Kuliah</th><th>Jurusan</th><th>Kelas</th><th>Semester</th>
              <th>Hari</th><th>Jam</th><th>Shift</th><th>Assisten</th><th>Catatan</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody id="praktikumData">
            <?php if ($result->num_rows): $no=1;
              while ($r=$result->fetch_assoc()): ?>
              <tr data-id="<?= $r['id'] ?>">
                <td><?= $no++ ?></td>
                <td><?= e($r['nama_matkul']) ?></td>
                <td><?= e($r['jurusan']) ?></td>
                <td><?= e($r['kelas']) ?></td>
                <td><?= e($r['semester']) ?></td>
                <td><?= e($r['hari']) ?></td>
                <td><?= e($r['jam_mulai']) ?> - <?= e($r['jam_ahir']) ?></td>
                <td><?= e($r['shift']) ?></td>
                <td><?= e($r['daftar_assisten'] ?? '-') ?></td>
                <td><?= e($r['catatan']) ?></td>
                <td class="text-center">
                  <button class="btn btn-warning btn-sm btnEdit" data-id="<?= $r['id'] ?>">Edit</button>
                  <button class="btn btn-danger btn-sm btnHapus" data-id="<?= $r['id'] ?>">Hapus</button>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="11" class="text-center">Belum ada data.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- === MODAL TAMBAH === -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formTambah" method="post" class="row g-2 mb-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="tambah">

        <!-- Dropdown -->
        <div class="col-md-3">
          <select name="mata_kuliah" class="form-select" required>
            <option value="">-- Mata Kuliah --</option>
            <?php foreach ($matkulData as $m): ?>
              <option value="<?= $m['id'] ?>"><?= e($m['mata_kuliah']) ?> (Smt <?= e($m['semester']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="jurusan" class="form-select" required>
            <option value="">-- Jurusan --</option>
            <?php foreach ($jurusanData as $j): ?>
              <option value="<?= e($j['jurusan']) ?>"><?= e($j['jurusan']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="kelas" class="form-select" required>
            <option value="">-- Kelas --</option>
            <option value="Reguler">Reguler</option>
            <option value="Karyawan">Karyawan</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="hari" class="form-select" required>
            <option value="">-- Hari --</option>
            <option>senin</option><option>selasa</option><option>rabu</option>
            <option>kamis</option><option>jumat</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="jam_mulai" class="form-select" required>
            <option value="">-- Jam --</option>
            <?php foreach ($jam_list as $j): ?><option><?= $j ?></option><?php endforeach; ?>
          </select>
        </div>

        <!-- === Assisten Multi === -->
        <div class="col-md-6 mt-2">
          <div class="input-group">
            <select id="assisten_select" class="form-select">
              <option value="">-- Pilih Assisten --</option>
              <?php foreach ($assistenListData as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['nama']) ?> (<?= e($a['nim']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <button type="button" id="btnAddAssisten" class="btn btn-outline-primary">+ Tambah</button>
          </div>
          <ul id="selected_assisten_list" class="list-group list-group-flush mt-2"></ul>
          <input type="hidden" name="assisten_ids" id="assisten_ids">
        </div>

        <div class="col-md-4 mt-2">
          <input type="text" name="catatan" class="form-control" placeholder="Catatan (opsional)">
        </div>

        <div class="col-md-2 mt-2 d-grid">
          <button class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- === MODAL EDIT === -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formEdit" method="post" class="row g-2 mb-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">

        <!-- kolom edit -->
        <div class="col-md-3">
          <select name="mata_kuliah" id="edit_mata_kuliah" class="form-select" required>
            <?php foreach ($matkulData as $m): ?>
              <option value="<?= $m['id'] ?>"><?= e($m['mata_kuliah']) ?> (Smt <?= e($m['semester']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2"><input type="text" id="edit_jurusan" name="jurusan" class="form-control" required></div>
        <div class="col-md-2"><input type="text" id="edit_kelas" name="kelas" class="form-control" required></div>
        <div class="col-md-2"><input type="text" id="edit_hari" name="hari" class="form-control" required></div>
        <div class="col-md-2"><input type="time" id="edit_jam_mulai" name="jam_mulai" class="form-control" required></div>

        <!-- assisten edit -->
        <div class="col-md-6 mt-2">
          <label>Assisten</label>
          <div class="input-group">
            <select id="edit_assisten_select" class="form-select">
              <option value="">-- Pilih Assisten --</option>
              <?php foreach ($assistenListData as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['nama']) ?> (<?= e($a['nim']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <button type="button" id="btnEditAddAssisten" class="btn btn-outline-primary">+ Tambah</button>
          </div>
          <ul id="edit_selected_assisten_list" class="list-group list-group-flush mt-2"></ul>
        </div>

        <div class="col-md-4 mt-2"><input type="text" id="edit_catatan" name="catatan" class="form-control"></div>
        <div class="col-md-2 mt-2 d-grid"><button class="btn btn-warning">Update</button></div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* === TAMBAH assisten === */
const selectedSet = new Set();
const assistenSelect = document.getElementById('assisten_select');
const listContainer = document.getElementById('selected_assisten_list');
const hiddenInput = document.getElementById('assisten_ids');

function renderSelected() {
  listContainer.innerHTML = '';
  [...selectedSet].forEach(id => {
    const opt = assistenSelect.querySelector(`option[value="${id}"]`);
    const text = opt ? opt.textContent : 'Asisten #' + id;
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `<span>${text}</span><button type="button" class="btn btn-sm btn-outline-danger" data-id="${id}">Hapus</button>`;
    li.querySelector('button').onclick = () => { selectedSet.delete(id); renderSelected(); syncHidden(); };
    listContainer.appendChild(li);
  });
}
function syncHidden() { hiddenInput.value = [...selectedSet].join(','); }
document.getElementById('btnAddAssisten').onclick = () => {
  const id = assistenSelect.value;
  if (id && !selectedSet.has(id)) { selectedSet.add(id); renderSelected(); syncHidden(); }
};

/* === EDIT assisten === */
async function loadAssistenEdit(id) {
  const res = await fetch(`praktikum_action.php?action=get_assisten_praktikum&id=${id}`);
  const data = await res.json();
  const list = document.getElementById('edit_selected_assisten_list');
  list.innerHTML = '';
  data.forEach(a => {
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `<span>${a.nama} (${a.nim})</span>
                    <button class="btn btn-sm btn-outline-danger" data-map="${a.map_id}">Hapus</button>`;
    li.querySelector('button').onclick = async () => {
      const form = new FormData();
      form.append('action','remove_praktikum_assisten');
      form.append('csrf_token','<?= e(csrf_token()) ?>');
      form.append('map_id', a.map_id);
      await fetch('praktikum_action.php',{method:'POST',body:form});
      loadAssistenEdit(id);
    };
    list.appendChild(li);
  });
}

document.getElementById('btnEditAddAssisten').onclick = async () => {
  const praktikumId = document.getElementById('edit_id').value;
  const asistenId = document.getElementById('edit_assisten_select').value;
  if (!asistenId) return;
  const form = new FormData();
  form.append('action','add_praktikum_assisten');
  form.append('csrf_token','<?= e(csrf_token()) ?>');
  form.append('praktikum_id', praktikumId);
  form.append('assisten_id', asistenId);
  await fetch('praktikum_action.php',{method:'POST',body:form});
  loadAssistenEdit(praktikumId);
};

/* === Klik Edit === */
document.querySelectorAll('.btnEdit').forEach(btn=>{
  btn.onclick = async ()=>{
    const id = btn.dataset.id;
    const res = await fetch(`praktikum_action.php?action=get&id=${id}`);
    const data = await res.json();
    for (const k in data) {
      const el = document.getElementById('edit_'+k);
      if (el) el.value = data[k];
    }
    document.getElementById('edit_id').value = id;
    await loadAssistenEdit(id);
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
  };
});

/* === Hapus Praktikum === */
document.querySelectorAll('.btnHapus').forEach(btn=>{
  btn.onclick = async ()=>{
    if(!confirm('Yakin hapus data ini?'))return;
    const form=new FormData();
    form.append('action','hapus');
    form.append('id',btn.dataset.id);
    form.append('csrf_token','<?= e(csrf_token()) ?>');
    const res=await fetch('praktikum_action.php',{method:'POST',body:form});
    document.getElementById('alertArea').innerHTML=await res.text();
    location.reload();
  };
});

/* === Tambah Praktikum === */
document.getElementById('formTambah').onsubmit=async e=>{
  e.preventDefault();
  const res=await fetch('praktikum_action.php',{method:'POST',body:new FormData(e.target)});
  document.getElementById('alertArea').innerHTML=await res.text();
  setTimeout(()=>location.reload(),500);
};
</script>
</body>
</html>
