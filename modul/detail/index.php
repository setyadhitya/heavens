<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Modul Praktikum - Contoh Materi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; }
    .content-box { background:white; padding:25px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,.1); }
    h5 { display:flex; justify-content:space-between; align-items:center; }
    h5 a { text-decoration:none; font-size:18px; }
  </style>
</head>
<body>

<div class="container mt-4">

  <a href="./index.php" class="btn btn-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Kembali</a>

  <div class="content-box">
    <h3 class="fw-bold text-primary">Modul: Pengenalan HTML</h3>
    <p><i class="bi bi-journal-bookmark"></i> Mata Kuliah: Pemrograman Web</p>
    <hr>

    <!-- DESKRIPSI -->
    <h5>
      Deskripsi
      <a href="#" data-bs-toggle="modal" data-bs-target="#imgDeskripsi">
        <i class="bi bi-eye text-primary"></i>
      </a>
    </h5>
    <p>Modul ini membahas dasar-dasar HTML untuk membuat halaman web.</p>

    <!-- TUJUAN -->
    <h5>
      Tujuan Pembelajaran
      <a href="#" data-bs-toggle="modal" data-bs-target="#imgTujuan">
        <i class="bi bi-eye text-primary"></i>
      </a>
    </h5>
    <ul>
      <li>Memahami struktur HTML</li>
      <li>Mampu membuat dokumen HTML sederhana</li>
    </ul>

    <!-- ALAT & BAHAN -->
    <h5>
      Alat dan Bahan
      <a href="#" data-bs-toggle="modal" data-bs-target="#imgAlat">
        <i class="bi bi-eye text-primary"></i>
      </a>
    </h5>
    <ul>
      <li>VS Code</li>
      <li>Browser Chrome/Firefox</li>
    </ul>

    <!-- LANGKAH PRAKTIKUM -->
    <h5>
      Langkah Praktikum
      <a href="#" data-bs-toggle="modal" data-bs-target="#imgLangkah">
        <i class="bi bi-eye text-primary"></i>
      </a>
    </h5>
    <ol>
      <li>Buat folder project baru</li>
      <li>Buat file index.html</li>
      <li>Tulis struktur HTML dasar</li>
    </ol>

    <!-- HASIL -->
    <h5>
      Contoh Hasil
      <a href="#" data-bs-toggle="modal" data-bs-target="#imgHasil">
        <i class="bi bi-eye text-primary"></i>
      </a>
    </h5>
    <p>Berikut contoh tampilan hasil output HTML sederhana.</p>

    <hr>
    <p class="text-muted small mb-0">© 2025 LabKom 3 Jaringan • Modul Praktikum</p>
  </div>
</div>

<!-- MODALS GAMBAR -->

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
