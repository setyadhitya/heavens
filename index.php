<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Sys-ASLPDC-T2B2</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; display:flex; flex-direction:column; min-height:100vh; }
    .hero {
      padding: 35px 20px;
      text-align: center;
      background: linear-gradient(135deg, #007bff, #00c6ff);
      color: white;
      border-radius: 8px;
      margin-bottom: 25px;
    }
    .menu-card {
      border: none;
      padding: 18px;
      border-radius: 10px;
      transition: .25s;
      cursor: pointer;
      background: white;
    }
    .menu-card:hover { transform: translateY(-6px); box-shadow: 0 8px 18px rgba(0,0,0,0.15); }
    footer { color: gray; font-size: 0.9rem; margin-top:auto; padding: 10px 0 15px; }
  </style>
</head>
<body>

<div class="container mt-3">

  <div class="hero shadow-sm">
    <h2 class="fw-bold mb-1">Sys-ASLPDC-T2B2</h2>
    <p class="mb-0 small">Sistem Akses Seluruh Layanan Praktikum Cepat, Terintegrasi, Tanpa Basa-Basi</p>
  </div>

  <div class="row g-3 justify-content-center">

    <div class="col-6 col-md-4">
      <a href="jadwal.php" class="text-decoration-none text-dark">
        <div class="menu-card text-center">
          <i class="bi bi-calendar3 display-6 text-primary"></i>
          <div class="fw-semibold mt-2">Jadwal Praktikum</div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4">
      <a href="project.php" class="text-decoration-none text-dark">
        <div class="menu-card text-center">
          <i class="bi bi-cpu display-6 text-danger"></i>
          <div class="fw-semibold mt-2">Project IOT</div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4">
      <a href="modul.php" class="text-decoration-none text-dark">
        <div class="menu-card text-center">
          <i class="bi bi-journal-bookmark display-6 text-success"></i>
          <div class="fw-semibold mt-2">Modul Praktikum</div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4">
      <a href="akun_assisten.php" class="text-decoration-none text-dark">
        <div class="menu-card text-center">
          <i class="bi bi-people-fill display-6 text-warning"></i>
          <div class="fw-semibold mt-2">Akun Asisten</div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-4">
      <a href="akun_mahasiswa.php" class="text-decoration-none text-dark">
        <div class="menu-card text-center">
          <i class="bi bi-person-vcard display-6 text-info"></i>
          <div class="fw-semibold mt-2">Akun Mahasiswa</div>
        </div>
      </a>
    </div>

  </div>
</div>

<footer class="text-center mt-2">
  © 2025 LabKom 3 Jaringan • Dibuat setengah semangat oleh PLP ☕ • Lab 3 Jaringan Komputer
</footer>

</body>
</html>
