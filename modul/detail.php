<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Modul Praktikum - Detail</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background:#f4f6f9; }
    .content-box{background:white;padding:25px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1);}
    .section-title{margin-top:25px;font-weight:600;padding-left:10px;border-left:6px solid;border-image:linear-gradient(to bottom,#007bff,#00d9ff) 1;}
    .btn-image{text-align:center;margin-top:10px;}
  </style>
</head>
<body>

<div class="container mt-4">
  <a href="index.php" class="btn btn-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Kembali</a>

  <div class="content-box">
    <h3 class="fw-bold text-primary">Modul: Pengenalan HTML</h3>
    <p><i class="bi bi-journal-bookmark"></i> Mata Kuliah: Pemrograman Web</p>
    <hr>

    <div class="section-title">Deskripsi</div>
    <p>Modul ini membahas dasar-dasar struktur HTML dan cara membuat halaman web.</p>
    <div class="text-center"><button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#imgDeskripsi"><i class="bi bi-eye"></i> Lihat Gambar</button></div>

    <div class="section-title">Tujuan Pembelajaran</div>
    <ul>
      <li>Mahasiswa memahami struktur HTML</li>
      <li>Mahasiswa mampu membuat halaman HTML sederhana</li>
    </ul>
    <div class="text-center"><button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#imgTujuan"><i class="bi bi-eye"></i> Lihat Gambar</button></div>

    <div class="section-title">Alat dan Bahan</div>
    <ul>
      <li>PC/Laptop</li>
      <li>VS Code</li>
      <li>Browser Chrome</li>
    </ul>
    <div class="text-center"><button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#imgAlat"><i class="bi bi-eye"></i> Lihat Gambar</button></div>

    <div class="section-title">Langkah Praktikum</div>
    <ol>
      <li>Buat folder project</li>
      <li>Buat file <b>index.html</b></li>
      <li>Tulis struktur HTML dasar</li>
    </ol>
    <div class="text-center"><button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#imgLangkah"><i class="bi bi-eye"></i> Lihat Gambar</button></div>

    <div class="section-title">Hasil Output</div>
    <p>Berikut tampilan hasil HTML dasar di browser.</p>
    <div class="text-center"><button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#imgHasil"><i class="bi bi-eye"></i> Lihat Gambar</button></div>

    <hr>
    <p class="text-muted small mb-0 text-center">© 2025 LabKom 3 Jaringan • Modul Praktikum</p>
  </div>
</div>

<div class="modal fade" id="imgDeskripsi" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <img src="../heavens/guwambar/modul/deskripsi.jpg" class="img-fluid rounded">
  </div>
</div>

<div class="modal fade" id="imgTujuan" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <img src="../heavens/guwambar/modul/tujuan.jpg" class="img-fluid rounded">
  </div>
</div>

<div class="modal fade" id="imgAlat" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <img src="../heavens/guwambar/modul/alat.jpg" class="img-fluid rounded">
  </div>
</div>

<div class="modal fade" id="imgLangkah" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <img src="../heavens/guwambar/modul/langkah.jpg" class="img-fluid rounded">
  </div>
</div>

<div class="modal fade" id="imgHasil" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <img src="../heavens/guwambar/modul/hasil.jpg" class="img-fluid rounded">
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
