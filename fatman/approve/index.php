<?php
// FILE: heavens/fatman/approve/index.php  (✅ FULL PDO VERSION)
require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

// Ambil data awal
$stmt = $pdo->query("SELECT * FROM tb_pendaftaran_akun ORDER BY id DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Approve Akun Praktikan</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container mt-4">
<div class="card shadow-sm">
<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
    <h5>Approve Akun Praktikan</h5>
    <span class="badge bg-light text-primary">Admin Only</span>
</div>
<div class="card-body">
<div id="alertArea"></div>

<table class="table table-bordered align-middle">
<thead class="table-dark text-center">
<tr>
    <th>#</th><th>Username</th><th>Nama</th><th>NIM</th><th>HP</th>
    <th>Status</th><th>Didaftarkan</th><th>Aksi</th>
</tr>
</thead>
<tbody id="approveData">
<?php if ($rows): $no=1; foreach ($rows as $r): ?>
<tr>
    <td><?= $no++; ?></td>
    <td><?= e($r['username']); ?></td>
    <td><?= e($r['nama']); ?></td>
    <td><?= e($r['nim']); ?></td>
    <td><?= e($r['nomorhp']); ?></td>
    <td class="text-center">
        <?php if ($r['status']=='waiting'): ?>
            <span class="badge bg-warning text-dark">waiting</span>
        <?php else: ?>
            <span class="badge bg-success">approve</span>
        <?php endif; ?>
    </td>
    <td><?= e($r['created_at']); ?></td>
    <td class="text-center">
        <?php if ($r['status']=='waiting'): ?>
            <button class="btn btn-danger btn-sm btnApprove"
                data-id="<?= $r['id']; ?>"
                data-username="<?= e($r['username']); ?>">Approve</button>
        <?php else: ?>
            <button class="btn btn-success btn-sm" disabled>Approved</button>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="8" class="text-center">Belum ada pendaftaran.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<script>
function attachApprove(){
    document.querySelectorAll('.btnApprove').forEach(btn=>{
        btn.onclick = async ()=>{
            if(!confirm(`Approve akun "${btn.dataset.username}" ?`)) return;
            let fd = new FormData();
            fd.append('action','approve');
            fd.append('id',btn.dataset.id);
            fd.append('csrf_token','<?= csrf_token(); ?>');
            let res = await fetch('approve_action.php',{method:'POST',body:fd});
            document.getElementById('alertArea').innerHTML = await res.text();
            loadTable();
        };
    });
}
async function loadTable(){
    let res = await fetch('approve_action.php?action=list');
    document.getElementById('approveData').innerHTML = await res.text();
    attachApprove();
}
attachApprove();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
