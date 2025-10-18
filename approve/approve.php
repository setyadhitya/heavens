<?php
require_once __DIR__ . '/../functions.php';
require_admin();
require_login_and_redirect();

// 🔐 Hanya admin yang boleh mengakses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Akses ditolak: halaman ini hanya untuk admin.');
}

// Ambil data awal (untuk initial render). Reload berikutnya via AJAX -> approve_action.php?action=list
$result = $mysqli->query("SELECT * FROM tb_pendaftaran_akun ORDER BY id DESC");
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Approve Akun Praktikan</title>
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
      <h5 class="mb-0">Approve Akun Praktikan</h5>
      <span class="badge bg-light text-primary">Admin Only</span>
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
              <th>Didaftarkan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody id="approveData">
          <?php if ($result && $result->num_rows > 0): $no=1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr data-id="<?= (int)$row['id']; ?>">
                <td class="text-center"><?= $no++; ?></td>
                <td><?= e($row['username']); ?></td>
                <td><?= e($row['nama']); ?></td>
                <td><?= e($row['nim']); ?></td>
                <td><?= e($row['nomorhp']); ?></td>
                <td class="text-center">
                  <?php if ($row['status'] === 'waiting'): ?>
                    <span class="badge bg-warning text-dark">waiting</span>
                  <?php else: ?>
                    <span class="badge bg-success">approve</span>
                  <?php endif; ?>
                </td>
                <td class="text-center"><?= e($row['created_at']); ?></td>
                <td class="text-center">
                  <?php if ($row['status'] === 'waiting'): ?>
                    <button class="btn btn-danger btn-sm btnApprove"
                            data-id="<?= (int)$row['id']; ?>"
                            data-username="<?= e($row['username']); ?>">
                      Approve
                    </button>
                  <?php else: ?>
                    <button class="btn btn-success btn-sm" disabled>Approved</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="8" class="text-center">Belum ada data pendaftaran.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  attachApproveListeners();

  // Re-attach listeners setiap kali tabel dimuat ulang
  function attachApproveListeners() {
    document.querySelectorAll('.btnApprove').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const uname = btn.dataset.username || '';
        const confirmMsg = `Yakin APPROVE akun "${uname}"? Tindakan ini permanen.`;
        if (!confirm(confirmMsg)) return;

        try {
          const form = new FormData();
          form.append('action', 'approve');
          form.append('id', id);
          form.append('csrf_token', '<?= e(csrf_token()); ?>');

          const res = await fetch('approve_action.php', { method: 'POST', body: form });
          const html = await res.text();
          document.getElementById('alertArea').innerHTML = html;

          // Reload tabel agar tombol berubah jadi hijau & disabled
          await loadApproveTable();
        } catch (err) {
          console.error(err);
          document.getElementById('alertArea').innerHTML =
            `<div class="alert alert-danger">Terjadi kesalahan jaringan: ${e.message || err}</div>`;
        }
      });
    });
  }

  async function loadApproveTable() {
    try {
      const res = await fetch('approve_action.php?action=list');
      const html = await res.text();
      document.getElementById('approveData').innerHTML = html;
      attachApproveListeners();
    } catch (err) {
      console.error("Gagal memuat tabel:", err);
    }
  }
});
</script>
</body>
</html>
