<?php
require_once __DIR__ . '/../functions.php';
require_login_and_redirect();
include '../navbar.php';

// Ambil semua data praktikum
$result = $mysqli->query("SELECT * FROM tb_praktikum ORDER BY id DESC");
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Data Praktikum</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
              <th>#</th>
              <th>Mata Kuliah</th>
              <th>Jurusan</th>
              <th>Kelas</th>
              <th>Semester</th>
              <th>Hari</th>
              <th>Jam</th>
              <th>Shift</th>
              <th>Asisten</th>
              <th>Catatan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody id="praktikumData">
            <?php if ($result->num_rows > 0): $no=1; ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr data-id="<?= $row['id']; ?>">
                  <td><?= $no++; ?></td>
                  <td><?= e($row['mata_kuliah']); ?></td>
                  <td><?= e($row['jurusan']); ?></td>
                  <td><?= e($row['kelas']); ?></td>
                  <td><?= e($row['semester']); ?></td>
                  <td><?= e($row['hari']); ?></td>
                  <td><?= e($row['jam_mulai']); ?> - <?= e($row['jam_ahir']); ?></td>
                  <td><?= e($row['shift']); ?></td>
                  <td><?= e($row['assisten']); ?></td>
                  <td><?= e($row['catatan']); ?></td>
                  <td class="text-center">
                    <button class="btn btn-warning btn-sm btnEdit" data-id="<?= $row['id']; ?>">Edit</button>
                    <button class="btn btn-danger btn-sm btnHapus" data-id="<?= $row['id']; ?>">Hapus</button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="11" class="text-center">Belum ada data.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- 🔹 Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formTambah">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Tambah Data Praktikum</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="tambah">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
          <div class="row g-2">
            <div class="col-md-4"><input name="mata_kuliah" class="form-control" placeholder="Mata Kuliah" required></div>
            <div class="col-md-3"><input name="jurusan" class="form-control" placeholder="Jurusan" required></div>
            <div class="col-md-1"><input name="kelas" class="form-control" placeholder="Kelas" required></div>
            <div class="col-md-1"><input name="semester" class="form-control" placeholder="Semester" required></div>
            <div class="col-md-3"><input name="hari" class="form-control" placeholder="Hari"></div>
            <div class="col-md-3"><input type="time" name="jam_mulai" class="form-control" placeholder="Mulai"></div>
            <div class="col-md-3"><input type="time" name="jam_ahir" class="form-control" placeholder="Akhir"></div>
            <div class="col-md-2"><input name="shift" class="form-control" placeholder="Shift"></div>
            <div class="col-md-4"><input name="assisten" class="form-control" placeholder="Asisten"></div>
            <div class="col-md-12"><textarea name="catatan" class="form-control" placeholder="Catatan"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 🔹 Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formEdit">
        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title">Edit Data Praktikum</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
          <input type="hidden" name="id" id="edit_id">
          <div class="row g-2">
            <div class="col-md-4"><input id="edit_mata_kuliah" name="mata_kuliah" class="form-control" placeholder="Mata Kuliah" required></div>
            <div class="col-md-3"><input id="edit_jurusan" name="jurusan" class="form-control" placeholder="Jurusan" required></div>
            <div class="col-md-1"><input id="edit_kelas" name="kelas" class="form-control" placeholder="Kelas" required></div>
            <div class="col-md-1"><input id="edit_semester" name="semester" class="form-control" placeholder="Semester" required></div>
            <div class="col-md-3"><input id="edit_hari" name="hari" class="form-control" placeholder="Hari"></div>
            <div class="col-md-3"><input type="time" id="edit_jam_mulai" name="jam_mulai" class="form-control"></div>
            <div class="col-md-3"><input type="time" id="edit_jam_ahir" name="jam_ahir" class="form-control"></div>
            <div class="col-md-2"><input id="edit_shift" name="shift" class="form-control" placeholder="Shift"></div>
            <div class="col-md-4"><input id="edit_assisten" name="assisten" class="form-control" placeholder="Asisten"></div>
            <div class="col-md-12"><textarea id="edit_catatan" name="catatan" class="form-control" placeholder="Catatan"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-warning">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Tambah Data
  document.getElementById('formTambah').addEventListener('submit', async e => {
    e.preventDefault();
    const res = await fetch('praktikum_action.php', {method:'POST', body:new FormData(e.target)});
    const html = await res.text();
    document.getElementById('alertArea').innerHTML = html;
    e.target.reset();
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalTambah'));
    modal.hide();
    location.reload();
  });

  // Hapus Data
  document.querySelectorAll('.btnHapus').forEach(btn=>{
    btn.addEventListener('click', async()=>{
      if(confirm('Yakin hapus data ini?')){
        const form = new FormData();
        form.append('action','hapus');
        form.append('id',btn.dataset.id);
        form.append('csrf_token','<?= e(csrf_token()); ?>');
        const res = await fetch('praktikum_action.php',{method:'POST',body:form});
        location.reload();
      }
    });
  });

  // Edit Data
  document.querySelectorAll('.btnEdit').forEach(btn=>{
    btn.addEventListener('click', async()=>{
      const id = btn.dataset.id;
      const res = await fetch('praktikum_action.php?action=get&id='+id);
      const data = await res.json();
      // isi form edit
      for(let key in data){
        const el = document.getElementById('edit_'+key);
        if(el) el.value = data[key];
      }
      new bootstrap.Modal(document.getElementById('modalEdit')).show();
    });
  });

  // Submit Edit
  document.getElementById('formEdit').addEventListener('submit', async e=>{
    e.preventDefault();
    const res = await fetch('praktikum_action.php',{method:'POST',body:new FormData(e.target)});
    const html = await res.text();
    document.getElementById('alertArea').innerHTML = html;
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEdit'));
    modal.hide();
    location.reload();
  });
});
</script>
</body>
</html>
