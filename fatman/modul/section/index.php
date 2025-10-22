<?php
// FILE: heavens/fatman/modul/section/index.php
require_once __DIR__ . '/../../functions.php';
require_admin();

$pdo = db();

$id_modul = (int)($_GET['id_modul'] ?? 0);
if ($id_modul <= 0) {
  die('ID modul tidak valid.');
}

$mod = $pdo->prepare("SELECT id_modul, judul_modul, mata_kuliah FROM modul WHERE id_modul = ? LIMIT 1");
$mod->execute([$id_modul]);
$modul = $mod->fetch();
if (!$modul) {
  die('Modul tidak ditemukan.');
}

// Ambil data awal (render pertama), reload berikutnya via AJAX -> section_action.php?action=list&id_modul=...
$stmt = $pdo->prepare("SELECT * FROM modul_section WHERE id_modul = ? ORDER BY urutan ASC, id_section ASC");
$stmt->execute([$id_modul]);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Section Modul: <?= e($modul['judul_modul']); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.table td, .table th { vertical-align: middle; }
.modal { z-index: 1050 !important; }
.modal-backdrop { z-index: 1040 !important; }
.img-thumb { width: 70px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
</style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/../../navbar.php'; ?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <a href="../index.php" class="btn btn-secondary btn-sm">&laquo; Kembali ke Daftar Modul</a>
    <div class="text-end">
      <div class="fw-semibold"><?= e($modul['judul_modul']); ?></div>
      <div class="text-muted small"><?= e($modul['mata_kuliah']); ?></div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Daftar Section</h5>
      <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah Section</button>
    </div>
    <div class="card-body">
      <div id="alertArea"></div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>#</th>
              <th>Urutan</th>
              <th>Judul Section</th>
              <th>Gambar</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody id="sectionData">
          <?php if (!empty($rows)): $no=1; foreach ($rows as $row): ?>
            <tr data-id="<?= (int)$row['id_section']; ?>">
              <td class="text-center"><?= $no++; ?></td>
              <td class="text-center"><?= (int)$row['urutan']; ?></td>
              <td>
                <div class="fw-semibold"><?= e($row['judul_section']); ?></div>
                <div class="text-muted small text-truncate" style="max-width:500px;"><?= e(mb_substr($row['isi_section'],0,120)); ?><?= (mb_strlen($row['isi_section'])>120?'…':''); ?></div>
              </td>
              <td class="text-center">
                <?php if (!empty($row['gambar_section'])): ?>
                  <img class="img-thumb" src="<?= e('../../../guwambar/modul/section/' . $row['gambar_section']); ?>" alt="gambar">
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <button class="btn btn-warning btn-sm btnEdit" data-id="<?= (int)$row['id_section']; ?>"><i class="bi bi-pencil-square"></i> Edit</button>
                <button class="btn btn-danger btn-sm btnHapus" data-id="<?= (int)$row['id_section']; ?>"><i class="bi bi-trash"></i> Hapus</button>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="5" class="text-center">Belum ada section.</td></tr>
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
      <form id="formTambah" method="post" enctype="multipart/form-data" class="row g-2 mb-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="action" value="tambah">
        <input type="hidden" name="id_modul" value="<?= (int)$id_modul; ?>">

        <div class="col-md-2">
          <label class="form-label">Urutan</label>
          <input type="number" name="urutan" class="form-control" value="1" min="1" required>
        </div>

        <div class="col-md-10">
          <label class="form-label">Judul Section</label>
          <input type="text" name="judul_section" class="form-control" required>
        </div>

        <div class="col-12">
          <label class="form-label">Isi Section</label>
          <textarea name="isi_section" class="form-control" rows="5" required></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label">Gambar (jpg/jpeg/png) <span class="text-muted">(opsional)</span></label>
          <input type="file" name="gambar_section" class="form-control" accept=".jpg,.jpeg,.png,image/*">
        </div>

        <div class="col-12 d-grid mt-2">
          <button class="btn btn-primary">Tambah</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 🔹 Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formEdit" method="post" enctype="multipart/form-data" class="row g-2 mb-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id_modul" value="<?= (int)$id_modul; ?>">
        <input type="hidden" name="id_section" id="edit_id_section">

        <div class="col-md-2">
          <label class="form-label">Urutan</label>
          <input type="number" name="urutan" id="edit_urutan" class="form-control" min="1" required>
        </div>

        <div class="col-md-10">
          <label class="form-label">Judul Section</label>
          <input type="text" name="judul_section" id="edit_judul_section" class="form-control" required>
        </div>

        <div class="col-12">
          <label class="form-label">Isi Section</label>
          <textarea name="isi_section" id="edit_isi_section" class="form-control" rows="5" required></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label">Ganti Gambar (opsional)</label>
          <input type="file" name="gambar_section" class="form-control" accept=".jpg,.jpeg,.png,image/*">
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" value="1" id="hapus_gambar" name="hapus_gambar">
            <label class="form-check-label" for="hapus_gambar">Hapus gambar saat disimpan</label>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label d-block">Gambar Saat Ini</label>
          <img id="preview_gambar" src="" class="img-thumb" alt="(kosong)">
          <div class="text-muted small" id="preview_gambar_ket">-</div>
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
      const res = await fetch('section_action.php', { method: 'POST', body: new FormData(e.target) });
      const html = await res.text();
      document.getElementById('alertArea').innerHTML = html;

      const modalEl = document.getElementById('modalTambah');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) {
        modal.hide();
        setTimeout(async () => {
          document.activeElement.blur();
          await loadSectionTable();
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

function attachTableListeners() {
  document.querySelectorAll('.btnEdit').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id_section = btn.dataset.id;
      try {
        const res = await fetch('section_action.php?action=get&id_section=' + id_section);
        if (!res.ok) throw new Error('Gagal ambil data');
        const data = await res.json();

        document.getElementById('edit_id_section').value = data.id_section ?? '';
        document.getElementById('edit_urutan').value = data.urutan ?? 1;
        document.getElementById('edit_judul_section').value = data.judul_section || '';
        document.getElementById('edit_isi_section').value = data.isi_section || '';

        const imgEl = document.getElementById('preview_gambar');
        const ketEl = document.getElementById('preview_gambar_ket');
        if (data.gambar_section) {
          imgEl.src = '../../../guwambar/modul/section/' + data.gambar_section;
          ketEl.textContent = data.gambar_section;
        } else {
          imgEl.src = '';
          ketEl.textContent = '(tidak ada gambar)';
        }
        document.getElementById('hapus_gambar').checked = false;

        new bootstrap.Modal(document.getElementById('modalEdit')).show();
      } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan saat mengambil data.');
      }
    });
  });

  document.querySelectorAll('.btnHapus').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (!confirm('Yakin hapus section ini? Tindakan ini tidak bisa dibatalkan.')) return;
      const form = new FormData();
      form.append('action', 'hapus');
      form.append('id_section', btn.dataset.id);
      form.append('csrf_token', '<?= e(csrf_token()); ?>');
      const res = await fetch('section_action.php', { method: 'POST', body: form });
      const html = await res.text();
      document.getElementById('alertArea').innerHTML = html;
      await loadSectionTable();
    });
  });
}

const formEdit = document.getElementById('formEdit');
if (formEdit) {
  formEdit.addEventListener('submit', async (e) => {
    e.preventDefault();
    const res = await fetch('section_action.php', { method: 'POST', body: new FormData(e.target) });
    const html = await res.text();
    document.getElementById('alertArea').innerHTML = html;

    const modalEl = document.getElementById('modalEdit');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
      modal.hide();
      setTimeout(async () => {
        document.activeElement.blur();
        await loadSectionTable();
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
      }, 200);
    }
  });
}

async function loadSectionTable() {
  try {
    const res = await fetch('section_action.php?action=list&id_modul=<?= (int)$id_modul; ?>');
    const html = await res.text();
    document.getElementById('sectionData').innerHTML = html;
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
<!-- Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html>
