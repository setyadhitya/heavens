<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

// Ambil data untuk dropdown
$praktikumList = $pdo->query("
    SELECT p.id, m.mata_kuliah
    FROM tb_praktikum p
    JOIN tb_matkul m ON p.mata_kuliah = m.id
    ORDER BY p.id DESC
")->fetchAll();

$assistenList = $pdo->query("
    SELECT id, nim, nama
    FROM tb_assisten
    WHERE status = 'aktif'
    ORDER BY id DESC
")->fetchAll();
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
<th>Assisten</th>
<th style="width:140px;">Aksi</th>
</tr>
</thead>
<tbody id="pesertaData">
<?php
$q = $pdo->query("
    SELECT ps.id,
           m.mata_kuliah AS praktikum_nama,
           pr.nim, pr.nama AS assisten_nama
    FROM tb_assisten_praktikum ps
    JOIN tb_praktikum p ON ps.praktikum_id = p.id
    JOIN tb_matkul m ON p.mata_kuliah = m.id
    JOIN tb_assisten pr ON ps.assisten_id = pr.id
    ORDER BY ps.id DESC
");
$rows = $q->fetchAll();
if ($rows):
$no=1; foreach($rows as $row):
?>
<tr data-id="<?= (int)$row['id']; ?>">
<td class="text-center"><?= $no++; ?></td>
<td><?= e($row['praktikum_nama']); ?></td>
<td><?= e($row['nim'] . ' - ' . $row['assisten_nama']); ?></td>
<td class="text-center">
<button class="btn btn-danger btn-sm btnHapus" data-id="<?= (int)$row['id']; ?>">Hapus</button>
</td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="4" class="text-center">Belum ada data peserta.</td></tr>
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
<form id="formTambah" method="post" class="p-3">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
<input type="hidden" name="action" value="tambah_multi">

<div class="mb-3">
<label class="form-label">Pilih Praktikum</label>
<select name="praktikum_id" class="form-select" required>
<option value="">-- Pilih Praktikum --</option>
<?php foreach ($praktikumList as $p): ?>
<option value="<?= (int)$p['id']; ?>"><?= e($p['mata_kuliah']); ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="mb-2 d-flex justify-content-between align-items-center">
<label class="form-label mb-0">Daftar Assisten</label>
<button type="button" id="btnAddRow" class="btn btn-outline-primary btn-sm">+ Tambah Assisten</button>
</div>

<div id="assistenRows" class="vstack gap-2"></div>

<div class="mt-3 d-grid">
<button class="btn btn-primary">Simpan Peserta</button>
</div>
</form>
</div>
</div>
</div>

<template id="rowTemplate">
<div class="row g-2 assisten-row align-items-center">
<div class="col-md-10">
<select name="assisten_ids[]" class="form-select" required>
<option value="">-- Pilih Assisten --</option>
<?php foreach ($assistenList as $pr): ?>
<option value="<?= (int)$pr['id']; ?>"><?= e($pr['nim'].' - '.$pr['nama']); ?></option>
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
const rowsContainer = document.getElementById('assistenRows');
const tpl = document.getElementById('rowTemplate');

function addRow() {
    const node = tpl.content.cloneNode(true);
    rowsContainer.appendChild(node);
    attachRemoveButtons();
}
function attachRemoveButtons() {
    document.querySelectorAll('.btnRemoveRow').forEach(btn => {
        btn.onclick = () => {
            if (document.querySelectorAll('.assisten-row').length <= 1) {
                alert('Minimal satu assisten.');
                return;
            }
            btn.closest('.assisten-row').remove();
        };
    });
}

document.getElementById('modalTambah').addEventListener('show.bs.modal', () => {
    rowsContainer.innerHTML = '';
    addRow();
});
document.getElementById('btnAddRow').onclick = addRow;

document.getElementById('formTambah').onsubmit = async e => {
    e.preventDefault();
    const res = await fetch('assisten_action.php', { method:'POST', body:new FormData(e.target)});
    document.getElementById('alertArea').innerHTML = await res.text();
    bootstrap.Modal.getInstance(document.getElementById('modalTambah')).hide();
    loadTable();
};

async function loadTable() {
    const res = await fetch('assisten_action.php?action=list');
    document.getElementById('pesertaData').innerHTML = await res.text();
    attachDeleteButtons();
}

function attachDeleteButtons() {
    document.querySelectorAll('.btnHapus').forEach(btn=>{
        btn.onclick = async () => {
            if (!confirm('Yakin hapus peserta ini?')) return;
            const form = new FormData();
            form.append('action','hapus');
            form.append('csrf_token','<?= e(csrf_token()); ?>');
            form.append('id',btn.dataset.id);
            await fetch('assisten_action.php',{method:'POST',body:form});
            loadTable();
        }
    });
}
attachDeleteButtons();
});
</script>
</body>
</html>
