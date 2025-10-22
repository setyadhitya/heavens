<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Modul Praktikum - Sys-ASLPDC-T2B2</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; display:flex; flex-direction:column; min-height:100vh; }
    .page-title {
      padding: 25px 20px;
      background: white;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    footer { color: gray; font-size: 0.9rem; margin-top:auto; padding: 10px 0 15px; }
  </style>
</head>
<body>

<div class="container mt-3">

  <div class="page-title d-flex justify-content-between align-items-center">
    <h4 class="mb-0"><i class="bi bi-journal-bookmark"></i> Modul Praktikum</h4>
    <a href="../" class="btn btn-secondary btn-sm"><i class="bi bi-house"></i> Beranda</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <div class="d-flex justify-content-between align-items-center">
        <span>Daftar Modul Praktikum</span>
        <a href="tambah.php" class="btn btn-light btn-sm"><i class="bi bi-plus-circle"></i> Tambah Modul</a>
      </div>
    </div>
    <div class="card-body">

      <table class="table table-bordered table-striped">
        <thead class="table-light">
          <tr>
            <th width="50">No</th>
            <th>Nama Modul</th>
            <th>Mata Kuliah</th>
            <th width="140">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td>Pengenalan Jaringan LAN</td>
            <td>Jaringan Komputer</td>
            <td class="text-center">
              <a href="edit.php?id=1" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
              <a href="hapus.php?id=1" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <tr>
            <td>2</td>
            <td>HTML Pemula</td>
            <td>Pemrograman Web</td>
            <td class="text-center">
              <a href="edit.php?id=2" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
              <a href="hapus.php?id=2" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
        </tbody>
      </table>

    </div>
  </div>

</div>

<footer class="text-center mt-3">
  © 2025 LabKom 3 Jaringan • Dibuat setengah semangat oleh PLP ☕ • Lab 3 Jaringan Komputer
</footer>

</body>
</html>
