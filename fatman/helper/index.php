<?php
// FILE: heavens/fatman/helper/index.php
require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

// Ambil data awal (render pertama), reload via AJAX -> helper_action.php?action=list
$stmt = $pdo->query("SELECT * FROM tb_helper ORDER BY id DESC");
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Data Helper (Panduan Halaman)</title>
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
<h5 class="mb-0">Daftar Helper (Panduan Halaman)</h5>
<button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah</button>
</div>
<div class="card-body">
<div id="alertArea"></div>

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle">
<thead class="table-dark text-center">
<tr>
<th>#</th>
<th>Halaman</th>
<th>Nama Panduan</th>
<th>Aksi</th>
</tr>
</thead>
<tbody id="helperData">
<?php if (!empty($rows)): $no=1; foreach ($rows as $row): ?>
<tr data-id="<?= (int)$row['id']; ?>">
<td class="text-center"><?= $no++; ?></td>
<td><?= e($row['halaman']); ?></td>
<td><?= e($row['nama']); ?></td>
<td class="text-center">
    <button class="btn btn-warning btn-sm btnEdit" data-id="<?= (int)$row['id']; ?>"><i class="bi bi-pencil-square"></i> Edit</button>
    <button class="btn btn-danger btn-sm btnHapus" data-id="<?= (int)$row['id']; ?>"><i class="bi bi-trash"></i> Hapus</button>
</td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="4" class="text-center">Belum ada data panduan.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

</div>
</div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<form id="formTambah" method="post" class="row g-2 mb-3">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
<input type="hidden" name="action" value="tambah">

<div class="col-md-6">
<label class="form-label">Nama Halaman</label>
<input type="text" name="halaman" class="form-control" placeholder="contoh: modul, detail_modul" required>
</div>

<div class="col-md-6">
<label class="form-label">Judul Panduan</label>
<input type="text" name="nama" class="form-control" placeholder="contoh: Panduan Halaman Modul" required>
</div>

<div class="col-12">
<label class="form-label">Deskripsi Panduan</label>
<textarea name="deskripsi" class="form-control" rows="4" placeholder="Tulis keterangan bantuan halaman" required></textarea>
</div>

<div class="col-12 d-grid mt-2">
<button class="btn btn-primary">Tambah</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<form id="formEdit" method="post" class="row g-2 mb-3">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="id" id="edit_id">

<div class="col-md-6">
<label class="form-label">Nama Halaman</label>
<input type="text" name="halaman" id="edit_halaman" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Judul Panduan</label>
<input type="text" name="nama" id="edit_nama" class="form-control" required>
</div>

<div class="col-12">
<label class="form-label">Deskripsi Panduan</label>
<textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="4" required></textarea>
</div>

<div class="col-12 d-grid mt-2">
<button class="btn btn-warning">Update</button>
</div>
</form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<script>
document.addEventListener('DOMContentLoaded', () => {

const formTambah = document.getElementById('formTambah');
if (formTambah) {
    formTambah.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('helper_action.php', { method: 'POST', body: new FormData(e.target) });
        const html = await res.text();
        document.getElementById('alertArea').innerHTML = html;

        // Tutup modal tambah
        const modalEl = document.getElementById('modalTambah');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        await loadHelperTable();
        formTambah.reset();
    });
}


const formEdit = document.getElementById('formEdit');
if (formEdit) {
    formEdit.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('helper_action.php', { method: 'POST', body: new FormData(e.target) });
        const html = await res.text();
        document.getElementById('alertArea').innerHTML = html;

        // Tutup modal edit
        const modalEl = document.getElementById('modalEdit');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        await loadHelperTable();
    });
}


function attachListeners() {
document.querySelectorAll('.btnEdit').forEach(btn => {
    btn.onclick = async () => {
        const id = btn.dataset.id;
        const res = await fetch('helper_action.php?action=get&id=' + id);
        const data = await res.json();
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_halaman').value = data.halaman;
        document.getElementById('edit_nama').value = data.nama;
        document.getElementById('edit_deskripsi').value = data.deskripsi;
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    };
});

document.querySelectorAll('.btnHapus').forEach(btn => {
    btn.onclick = async () => {
        if (!confirm('Yakin hapus bantuan ini?')) return;
        const form = new FormData();
        form.append('action', 'hapus');
        form.append('id', btn.dataset.id);
        form.append('csrf_token', '<?= e(csrf_token()); ?>');
        const res = await fetch('helper_action.php', { method: 'POST', body: form });
        const html = await res.text();
        document.getElementById('alertArea').innerHTML = html;
        await loadHelperTable();
    };
});
}

async function loadHelperTable() {
const res = await fetch('helper_action.php?action=list');
document.getElementById('helperData').innerHTML = await res.text();
attachListeners();
}

attachListeners();
});
</script>
</body>
</html>
