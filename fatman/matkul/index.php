<?php
// FILE: heavens/fatman/matkul/index.php
require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

// Ambil data awal (render pertama), reload via AJAX -> matkul_action.php?action=list
$stmt = $pdo->query("SELECT * FROM tb_matkul ORDER BY id DESC");
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Data Mata Kuliah</title>
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
    <h5 class="mb-0">Daftar Mata Kuliah</h5>
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
                    <th>Semester</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="matkulData">
                <?php if (!empty($rows)): $no=1; foreach ($rows as $row): ?>
                <tr data-id="<?= (int)$row['id']; ?>">
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= e($row['mata_kuliah']); ?></td>
                    <td class="text-center"><?= e($row['semester']); ?></td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm btnEdit" data-id="<?= (int)$row['id']; ?>">Edit</button>
                        <button class="btn btn-danger btn-sm btnHapus" data-id="<?= (int)$row['id']; ?>">Hapus</button>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="4" class="text-center">Belum ada data.</td></tr>
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

    <div class="col-md-8">
        <label class="form-label">Nama Mata Kuliah</label>
        <input type="text" name="mata_kuliah" class="form-control" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Semester</label>
        <select name="semester" class="form-select" required>
            <option value="">-- Pilih Semester --</option>
            <?php for($i=1;$i<=6;$i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
        </select>
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

    <div class="col-md-8">
        <label class="form-label">Nama Mata Kuliah</label>
        <input type="text" name="mata_kuliah" id="edit_mata_kuliah" class="form-control" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Semester</label>
        <select name="semester" id="edit_semester" class="form-select" required>
            <?php for($i=1;$i<=6;$i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
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

const formTambah = document.getElementById('formTambah');
if (formTambah) {
    formTambah.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('matkul_action.php', { method: 'POST', body: new FormData(e.target) });
        const html = await res.text();
        document.getElementById('alertArea').innerHTML = html;

        // Tutup modal tambah
        const modalEl = document.getElementById('modalTambah');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        await loadMatkulTable();
        formTambah.reset();
    });
}


const formEdit = document.getElementById('formEdit');
if (formEdit) {
    formEdit.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('matkul_action.php', { method: 'POST', body: new FormData(e.target) });
        const html = await res.text();
        document.getElementById('alertArea').innerHTML = html;

        // Tutup modal edit
        const modalEl = document.getElementById('modalEdit');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        await loadMatkulTable();
    });
}

function attachListeners() {
    document.querySelectorAll('.btnEdit').forEach(btn => {
        btn.onclick = async () => {
            const res = await fetch('matkul_action.php?action=get&id=' + btn.dataset.id);
            const data = await res.json();
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_mata_kuliah').value = data.mata_kuliah;
            document.getElementById('edit_semester').value = data.semester;
            new bootstrap.Modal(document.getElementById('modalEdit')).show();
        };
    });

    document.querySelectorAll('.btnHapus').forEach(btn => {
        btn.onclick = async () => {
            if (!confirm('Yakin hapus mata kuliah ini?')) return;
            const form = new FormData();
            form.append('action', 'hapus');
            form.append('id', btn.dataset.id);
            form.append('csrf_token', '<?= e(csrf_token()); ?>');
            const res = await fetch('matkul_action.php', { method: 'POST', body: form });
            const html = await res.text();
            document.getElementById('alertArea').innerHTML = html;
            await loadMatkulTable();
        };
    });
}

async function loadMatkulTable() {
    const res = await fetch('matkul_action.php?action=list');
    document.getElementById('matkulData').innerHTML = await res.text();
    attachListeners();
}

attachListeners();
});
</script>
</body>
</html>
