<?php
require_once __DIR__ . '/../functions.php';
require_admin();

// Ambil data awal (render pertama), reload berikutnya via AJAX -> assisten_action.php?action=list
$result = $mysqli->query("SELECT * FROM tb_assisten ORDER BY id DESC");
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Data Assisten</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .table td, .table th { vertical-align: middle; }
    .modal { z-index: 1050 !important; }
    .modal-backdrop { z-index: 1040 !important; }
  </style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container mt-4">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Daftar Assisten</h5>
      <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah</button>
    </div>
    <div class="card-body">
      <div id="alertArea"></div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>#</th>
              <th>Username</th>
              <th>Nama</th>
              <th>NIM</th>
              <th>Nomor HP</th>
              <th>Status</th>
              <th>Dibuat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody id="assistenData">
          <?php if ($result && $result->num_rows > 0): $no=1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr data-id="<?= (int)$row['id']; ?>">
                <td class="text-center"><?= $no++; ?></td>
                <td><?= e($row['username']); ?></td>
                <td><?= e($row['nama']); ?></td>
                <td><?= e($row['nim']); ?></td>
                <td><?= e($row['nomorhp']); ?></td>
                <td class="text-center">
                  <?php if (($row['status'] ?? '') === 'aktif'): ?>
                    <span class="badge bg-success">aktif</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">nonaktif</span>
                  <?php endif; ?>
                </td>
                <td class="text-center"><?= e($row['created_at']); ?></td>
                <td class="text-center">
                  <button class="btn btn-warning btn-sm btnEdit" data-id="<?= (int)$row['id']; ?>">Edit</button>
                  <button class="btn btn-danger btn-sm btnHapus" data-id="<?= (int)$row['id']; ?>">Hapus</button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="8" class="text-center">Belum ada data.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<!-- 🔹 Modal Tambah (STATUS DIHILANGKAN, default aktif di server) -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formTambah" method="post" class="row g-2 mb-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="action" value="tambah">

        <div class="col-md-4">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Nama (huruf & spasi)</label>
          <input type="text" name="nama" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">NIM (angka saja)</label>
          <input type="text" name="nim" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Nomor HP (angka saja)</label>
          <input type="text" name="nomorhp" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <div class="col-12 d-grid mt-2">
          <button class="btn btn-primary">Tambah</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 🔹 Modal Edit (status tetap bisa diubah di sini) -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formEdit" method="post" class="row g-2 mb-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">

        <div class="col-md-4">
          <label class="form-label">Username</label>
          <input type="text" name="username" id="edit_username" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Nama (huruf & spasi)</label>
          <input type="text" name="nama" id="edit_nama" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">NIM (angka saja)</label>
          <input type="text" name="nim" id="edit_nim" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Nomor HP (angka saja)</label>
          <input type="text" name="nomorhp" id="edit_nomorhp" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Password (kosongkan jika tidak diubah)</label>
          <input type="password" name="password" id="edit_password" class="form-control" placeholder="(optional)">
        </div>

        <div class="col-md-4">
          <label class="form-label">Status</label>
          <select name="status" id="edit_status" class="form-select" required>
            <option value="aktif">aktif</option>
            <option value="nonaktif">nonaktif</option>
          </select>
        </div>

        <div class="col-12 d-grid mt-2">
          <button class="btn btn-warning">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  // Tambah
  const formTambah = document.getElementById('formTambah');
  if (formTambah) {
    formTambah.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const res = await fetch('assisten_action.php', { method: 'POST', body: new FormData(e.target) });
        const html = await res.text();
        document.getElementById('alertArea').innerHTML = html;

        const modalEl = document.getElementById('modalTambah');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
          modal.hide();
          setTimeout(async () => {
            document.activeElement.blur();
            await loadAssistenTable();
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
          }, 200);
        }
        e.target.reset();
      } catch (err) {
        console.error(err);
      }
    });
  }

  // Attach listeners to dynamic table
  function attachTableListeners() {
    document.querySelectorAll('.btnEdit').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        try {
          const res = await fetch('assisten_action.php?action=get&id=' + id);
          if (!res.ok) throw new Error('Gagal ambil data');
          const data = await res.json();

          document.getElementById('edit_id').value = data.id;
          document.getElementById('edit_username').value = data.username || '';
          document.getElementById('edit_nama').value = data.nama || '';
          document.getElementById('edit_nim').value = data.nim || '';
          document.getElementById('edit_nomorhp').value = data.nomorhp || '';
          document.getElementById('edit_password').value = ''; // kosongkan
          document.getElementById('edit_status').value = data.status || 'nonaktif';

          new bootstrap.Modal(document.getElementById('modalEdit')).show();
        } catch (err) {
          console.error(err);
          alert('Terjadi kesalahan saat mengambil data.');
        }
      });
    });

    document.querySelectorAll('.btnHapus').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Yakin hapus assisten ini? Tindakan ini tidak bisa dibatalkan.')) return;
        const form = new FormData();
        form.append('action', 'hapus');
        form.append('id', btn.dataset.id);
        form.append('csrf_token', '<?= e(csrf_token()); ?>');
        const res = await fetch('assisten_action.php', { method: 'POST', body: form });
        const html = await res.text();
        document.getElementById('alertArea').innerHTML = html;
        await loadAssistenTable();
      });
    });
  }

  // Submit Edit
  const formEdit = document.getElementById('formEdit');
  if (formEdit) {
    formEdit.addEventListener('submit', async (e) => {
      e.preventDefault();
      const res = await fetch('assisten_action.php', { method: 'POST', body: new FormData(e.target) });
      const html = await res.text();
      document.getElementById('alertArea').innerHTML = html;

      const modalEl = document.getElementById('modalEdit');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) {
        modal.hide();
        setTimeout(async () => {
          document.activeElement.blur();
          await loadAssistenTable();
          document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
          document.body.classList.remove('modal-open');
          document.body.style.overflow = '';
        }, 200);
      }
    });
  }

  async function loadAssistenTable() {
    try {
      const res = await fetch('assisten_action.php?action=list');
      const html = await res.text();
      document.getElementById('assistenData').innerHTML = html;
      attachTableListeners();
    } catch (err) {
      console.error('Gagal memuat tabel:', err);
    }
  }

  attachTableListeners();

  // Cleanup sisa modal
  document.addEventListener('hidden.bs.modal', () => {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
  });
});
</script>
</body>
</html>
