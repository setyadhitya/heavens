<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

// Dropdown Praktikum
$praktikumList = $pdo->query("
    SELECT p.id, m.mata_kuliah
    FROM tb_praktikum p
    JOIN tb_matkul m ON p.mata_kuliah = m.id
    ORDER BY p.id DESC
")->fetchAll();

// Dropdown Praktikan
$praktikanList = $pdo->query("
    SELECT id, nim, nama
    FROM tb_praktikan
    WHERE status = 'aktif'
    ORDER BY id DESC
")->fetchAll();

// Tabel awal
$dataPeserta = $pdo->query("
    SELECT ps.id,
           m.mata_kuliah AS praktikum_nama,
           pr.nim, pr.nama AS praktikan_nama
    FROM tb_peserta ps
    JOIN tb_praktikum p ON ps.praktikum_id = p.id
    JOIN tb_matkul m ON p.mata_kuliah = m.id
    JOIN tb_praktikan pr ON ps.praktikan_id = pr.id
    ORDER BY ps.id DESC
")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Peserta Praktikum</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container mt-4">
<div class="card shadow-sm">
<div class="card-header bg-primary text-white d-flex justify-content-between">
    <h5>Peserta Praktikum</h5>
    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah</button>
</div>
<div class="card-body">
<div id="alertArea"></div>

<table class="table table-bordered align-middle">
<thead class="table-dark text-center">
<tr><th>#</th><th>Praktikum</th><th>Praktikan</th><th>Aksi</th></tr>
</thead>
<tbody id="pesertaData">
<?php if ($dataPeserta): $no=1; foreach ($dataPeserta as $row): ?>
<tr>
    <td class="text-center"><?= $no++; ?></td>
    <td><?= e($row['praktikum_nama']); ?></td>
    <td><?= e($row['nim']) . " - " . e($row['praktikan_nama']); ?></td>
    <td class="text-center"><button data-id="<?= $row['id'] ?>" class="btn btn-danger btn-sm btnHapus">Hapus</button></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="4" class="text-center">Belum ada peserta.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<form id="formTambah" method="post" class="p-3">
<input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
<input type="hidden" name="action" value="tambah_multi">

<label>Pilih Praktikum</label>
<select name="praktikum_id" class="form-select mb-3" required>
    <option value="">-- Pilih Praktikum --</option>
    <?php foreach ($praktikumList as $p): ?>
    <option value="<?= $p['id']; ?>"><?= e($p['mata_kuliah']); ?></option>
    <?php endforeach; ?>
</select>

<div class="d-flex justify-content-between">
    <label>Daftar Praktikan</label>
    <button type="button" id="btnAddRow" class="btn btn-outline-primary btn-sm">+ Tambah</button>
</div>

<div id="praktikanRows" class="vstack gap-2 mt-2"></div>

<div class="mt-3">
    <button class="btn btn-primary w-100">Simpan</button>
</div>
</form>
</div>
</div>
</div>

<template id="rowTemplate">
<div class="row g-2 praktikan-row">
    <div class="col-md-10">
        <select name="praktikan_ids[]" class="form-select" required>
            <option value="">-- Pilih Praktikan --</option>
            <?php foreach ($praktikanList as $pr): ?>
            <option value="<?= $pr['id']; ?>"><?= e($pr['nim'].' - '.$pr['nama']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="button" class="btn btn-outline-danger btnRemoveRow">X</button>
    </div>
</div>
</template>

<script>
function attachDelete() {
    document.querySelectorAll(".btnHapus").forEach(btn=>{
        btn.onclick = async ()=>{
            if (!confirm("Yakin hapus?")) return;
            let fd=new FormData();
            fd.append('csrf_token','<?= csrf_token(); ?>');
            fd.append('action','hapus');
            fd.append('id',btn.dataset.id);
            await fetch('peserta_action.php',{method:"POST",body:fd});
            location.reload();
        };
    });
}
attachDelete();

document.getElementById('btnAddRow').onclick = ()=>{
    let tpl = document.getElementById('rowTemplate').content.cloneNode(true);
    document.getElementById('praktikanRows').appendChild(tpl);
};
document.getElementById('praktikanRows').addEventListener('click', e=>{
    if(e.target.classList.contains('btnRemoveRow')){
        if(document.querySelectorAll('.praktikan-row').length > 1){
            e.target.closest('.praktikan-row').remove();
        }
    }
});
document.getElementById('modalTambah').addEventListener('show.bs.modal',()=>{
    document.getElementById('praktikanRows').innerHTML='';
    document.getElementById('btnAddRow').click();
});
document.getElementById('formTambah').onsubmit = async e=>{
    e.preventDefault();
    await fetch('peserta_action.php',{method:"POST",body:new FormData(e.target)});
    location.reload();
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
