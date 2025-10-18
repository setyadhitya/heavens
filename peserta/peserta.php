<?php
require_once __DIR__ . '/../functions.php';
require_admin();

// Ambil data untuk dropdown
$praktikumRes = $mysqli->query("
  SELECT p.id, m.mata_kuliah
  FROM tb_praktikum p
  JOIN tb_matkul m ON p.mata_kuliah = m.id
  ORDER BY p.id DESC
");

$praktikumList = $praktikumRes ? $praktikumRes->fetch_all(MYSQLI_ASSOC) : [];

$praktikanRes = $mysqli->query("
  SELECT id, nim, nama
  FROM tb_praktikan
  WHERE status = 'aktif'
  ORDER BY id DESC
");
$praktikanList = $praktikanRes ? $praktikanRes->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Peserta Praktikum</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .table td, .table th { vertical-align: middle; }
    .modal { z-index: 1050 !important; }
    .modal-backdrop { z-index: 1040 !important; }
    .small-muted { font-size: .9rem; color:#666; }
  </style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container mt-4">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Peserta Praktikum</h5>
      <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah</button>
    </div>
    <div class="card-body">
      <div id="alertArea"></div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th style="width:60px;">#</th>
              <th>Praktikum</th>
              <th>Praktikan</th>
              <th style="width:140px;">Aksi</th>
            </tr>
          </thead>
          <tbody id="pesertaData">
            <?php
            $q = $mysqli->query("
              SELECT ps.id,
                     m.mata_kuliah AS praktikum_nama,
                     pr.nim, pr.nama AS praktikan_nama
              FROM tb_peserta ps
              JOIN tb_praktikum p   ON ps.praktikum_id = p.id
              JOIN tb_matkul m      ON p.mata_kuliah = m.id
              JOIN tb_praktikan pr  ON ps.praktikan_id = pr.id
              ORDER BY ps.id DESC
            ");
            if ($q && $q->num_rows > 0):
              $no=1; while($row=$q->fetch_assoc()):
            ?>
              <tr data-id="<?= (int)$row['id']; ?>">
                <td class="text-center"><?= $no++; ?></td>
                <td><?= e($row['praktikum_nama']); ?></td>
                <td><?= e($row['nim'] . ' - ' . $row['praktikan_nama']); ?></td>
                <td class="text-center">
                  <button class="btn btn-danger btn-sm btnHapus" data-id="<?= (int)$row['id']; ?>">Hapus</button>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="4" class="text-center">Belum ada data peserta.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<!-- 🔹 Modal Tambah (Pilih 1 Praktikum, bisa tambah banyak Praktikan) -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formTambah" method="post" class="p-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="action" value="tambah_multi">

        <!-- Praktikum -->
        <div class="mb-3">
          <label class="form-label">Pilih Praktikum</label>
          <select name="praktikum_id" class="form-select" required>
            <option value="">-- Pilih Praktikum --</option>
            <?php foreach ($praktikumList as $p): ?>
              <option value="<?= (int)$p['id']; ?>"><?= e($p['mata_kuliah']); ?></option>
            <?php endforeach; ?>
          </select>
          <div class="small-muted">Pilih satu praktikum, lalu tambahkan beberapa praktikan sekaligus.</div>
        </div>

        <!-- Daftar Praktikan (dinamis) -->
        <div class="mb-2 d-flex justify-content-between align-items-center">
          <label class="form-label mb-0">Daftar Praktikan</label>
          <button type="button" id="btnAddRow" class="btn btn-outline-primary btn-sm">+ Tambah Praktikan</button>
        </div>

        <div id="praktikanRows" class="vstack gap-2">
          <!-- Row template akan disisipkan via JS -->
        </div>

        <div class="mt-3 d-grid">
          <button class="btn btn-primary">Simpan Peserta</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Template row (hidden) -->
<template id="rowTemplate">
  <div class="row g-2 praktikan-row align-items-center">
    <div class="col-md-10">
      <select name="praktikan_ids[]" class="form-select" required>
        <option value="">-- Pilih Praktikan --</option>
        <?php foreach ($praktikanList as $pr): ?>
          <option value="<?= (int)$pr['id']; ?>">
            <?= e($pr['nim'] . ' - ' . $pr['nama']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-grid">
      <button type="button" class="btn btn-outline-danger btnRemoveRow">Hapus</button>
    </div>
  </div>
</template>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // --- Dinamis rows ---
  const rowsContainer = document.getElementById('praktikanRows');
  const tpl = document.getElementById('rowTemplate');

  function addRow() {
    const node = tpl.content.cloneNode(true);
    rowsContainer.appendChild(node);
    attachRemoveRowHandlers();
  }

  function attachRemoveRowHandlers() {
    rowsContainer.querySelectorAll('.btnRemoveRow').forEach(btn => {
      btn.onclick = () => {
        const row = btn.closest('.praktikan-row');
        if (row) {
          if (rowsContainer.querySelectorAll('.praktikan-row').length <= 1) {
            // minimal satu baris tetap ada
            alert('Minimal satu praktikan.');
            return;
          }
          row.remove();
        }
      };
    });
  }

  // Tambah baris pertama saat modal dibuka
  const modalTambah = document.getElementById('modalTambah');
  modalTambah.addEventListener('show.bs.modal', () => {
    rowsContainer.innerHTML = '';
    addRow();
  });

  document.getElementById('btnAddRow').addEventListener('click', addRow);

  // --- Submit Tambah Multi ---
  const formTambah = document.getElementById('formTambah');
  formTambah.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      const res = await fetch('peserta_action.php', { method: 'POST', body: new FormData(e.target) });
      const html = await res.text();
      document.getElementById('alertArea').innerHTML = html;

      const modal = bootstrap.Modal.getInstance(modalTambah);
      if (modal) {
        modal.hide();
        setTimeout(async () => {
          document.activeElement.blur();
          await loadTable();
          document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
          document.body.classList.remove('modal-open');
          document.body.style.overflow = '';
        }, 200);
      }
    } catch (err) {
      console.error(err);
    }
  });

  // --- Hapus ---
  function attachTableListeners() {
    document.querySelectorAll('.btnHapus').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Yakin hapus peserta ini?')) return;
        const form = new FormData();
        form.append('action', 'hapus');
        form.append('id', btn.dataset.id);
        form.append('csrf_token', '<?= e(csrf_token()); ?>');
        const res = await fetch('peserta_action.php', { method: 'POST', body: form });
        const html = await res.text();
        document.getElementById('alertArea').innerHTML = html;
        await loadTable();
      });
    });
  }

  async function loadTable() {
    try {
      const res = await fetch('peserta_action.php?action=list');
      const html = await res.text();
      document.getElementById('pesertaData').innerHTML = html;
      attachTableListeners();
    } catch (err) {
      console.error('Gagal memuat tabel:', err);
    }
  }

  // initial
  attachTableListeners();

  // cleanup backdrop sisa modal
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
