<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

// Ambil data awal
$stmt = $pdo->query("SELECT * FROM tb_praktikan ORDER BY id DESC");
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Data Praktikan</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container mt-4">
<div class="card shadow-sm">
<div class="card-header bg-primary text-white d-flex justify-content-between">
    <h5 class="mb-0">Daftar Praktikan</h5>
    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah</button>
</div>
<div class="card-body">
<div id="alertArea"></div>

<table class="table table-bordered table-hover align-middle">
<thead class="table-dark text-center">
<tr>
    <th>#</th><th>Username</th><th>Nama</th><th>NIM</th>
    <th>Nomor HP</th><th>Status</th><th>Dibuat</th><th>Aksi</th>
</tr>
</thead>
<tbody id="praktikanData">
<?php if ($rows): $no=1; foreach ($rows as $r): ?>
<tr>
    <td class="text-center"><?= $no++; ?></td>
    <td><?= e($r['username']); ?></td>
    <td><?= e($r['nama']); ?></td>
    <td><?= e($r['nim']); ?></td>
    <td><?= e($r['nomorhp']); ?></td>
    <td class="text-center">
        <span class="badge bg-<?= $r['status']=='aktif'?'success':'secondary'; ?>">
            <?= e($r['status']); ?>
        </span>
    </td>
    <td class="text-center"><?= e($r['created_at']); ?></td>
    <td class="text-center">
        <button class="btn btn-warning btn-sm btnEdit" data-id="<?= $r['id']; ?>">Edit</button>
        <button class="btn btn-danger btn-sm btnHapus" data-id="<?= $r['id']; ?>">Hapus</button>
    </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="8" class="text-center">Belum ada data.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<form id="formTambah" method="post">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
<input type="hidden" name="action" value="tambah">

<div class="p-3 row g-2">
    <div class="col-md-4"><label>Username</label><input name="username" class="form-control" required></div>
    <div class="col-md-4"><label>Nama</label><input name="nama" class="form-control" required></div>
    <div class="col-md-4"><label>NIM</label><input name="nim" class="form-control" required></div>
    <div class="col-md-4"><label>Nomor HP</label><input name="nomorhp" class="form-control" required></div>
    <div class="col-md-4"><label>Password</label><input type="password" name="password" class="form-control" required></div>
    <div class="col-12"><button class="btn btn-primary w-100 mt-2">Tambah</button></div>
</div>
</form>
</div>
</div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<form id="formEdit" method="post">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="id" id="edit_id">

<div class="p-3 row g-2">
    <div class="col-md-4"><label>Username</label><input name="username" id="edit_username" class="form-control"></div>
    <div class="col-md-4"><label>Nama</label><input name="nama" id="edit_nama" class="form-control"></div>
    <div class="col-md-4"><label>NIM</label><input name="nim" id="edit_nim" class="form-control"></div>
    <div class="col-md-4"><label>Nomor HP</label><input name="nomorhp" id="edit_nomorhp" class="form-control"></div>
    <div class="col-md-4"><label>Password (Opsional)</label><input type="password" name="password" id="edit_password" class="form-control"></div>
    <div class="col-md-4">
        <label>Status</label>
        <select name="status" id="edit_status" class="form-select">
            <option value="aktif">aktif</option>
            <option value="nonaktif">nonaktif</option>
        </select>
    </div>
    <div class="col-12"><button class="btn btn-warning w-100 mt-2">Update</button></div>
</div>
</form>
</div>
</div>
</div>

<script>
async function loadPraktikanTable(){
    let res = await fetch('praktikan_action.php?action=list');
    document.getElementById('praktikanData').innerHTML = await res.text();
    attachListeners();
}

function attachListeners(){
    document.querySelectorAll('.btnEdit').forEach(btn=>{
        btn.onclick = async ()=>{
            const res = await fetch('praktikan_action.php?action=get&id='+btn.dataset.id);
            const d = await res.json();
            edit_id.value = d.id;
            edit_username.value = d.username;
            edit_nama.value = d.nama;
            edit_nim.value = d.nim;
            edit_nomorhp.value = d.nomorhp;
            edit_status.value = d.status;
            new bootstrap.Modal(document.getElementById('modalEdit')).show();
        };
    });

    document.querySelectorAll('.btnHapus').forEach(btn=>{
        btn.onclick = async ()=>{
            if(confirm('Yakin hapus?')){
                let fd = new FormData();
                fd.append('action','hapus');
                fd.append('id',btn.dataset.id);
                fd.append('csrf_token','<?= csrf_token(); ?>');
                await fetch('praktikan_action.php',{method:'POST',body:fd});
                loadPraktikanTable();
            }
        };
    });
}

document.getElementById('formTambah').onsubmit = async e=>{
    e.preventDefault();
    await fetch('praktikan_action.php',{method:'POST',body:new FormData(e.target)});
    loadPraktikanTable();
    bootstrap.Modal.getInstance(document.getElementById('modalTambah')).hide();
};

document.getElementById('formEdit').onsubmit = async e=>{
    e.preventDefault();
    await fetch('praktikan_action.php',{method:'POST',body:new FormData(e.target)});
    loadPraktikanTable();
    bootstrap.Modal.getInstance(document.getElementById('modalEdit')).hide();
};

attachListeners();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
