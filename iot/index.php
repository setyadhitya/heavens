<?php
// Halaman Informasi Layanan IoT
// Public page, tanpa login
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Layanan IoT - LabKom 3</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color:#f5f6fa; }
    .hero {
      background: #0d47a1;
      color: white;
      padding: 30px;
      border-radius: 8px;
    }
    .price-box {
      border:1px solid #ddd;
      background:white;
      border-radius:8px;
      padding:15px;
      transition:.3s;
    }
    .price-box:hover {
      box-shadow:0 4px 15px rgba(0,0,0,.15);
      transform:translateY(-3px);
    }
  </style>
</head>
<body>

<div class="container py-4">

  <div class="hero mb-4">
    <h2>Layanan IoT LabKom 3</h2>
    <p class="mb-0">Layanan resmi untuk mahasiswa dalam pengembangan Internet of Things (IoT), sistem monitoring, kontrol jarak jauh, ESP8266/ESP32 dan NodeMCU.</p>
</div>

  <div class="row g-4">

    <div class="col-md-6">
      <div class="price-box">
        <h5>📱 Download Aplikasi IoT</h5>
        <p>Aplikasi Android untuk monitoring & kontrol alat berbasis ESP8266/ESP32.</p>
        <a href="#" class="btn btn-primary btn-sm disabled">Coming Soon</a>
      </div>
    </div>

    <div class="col-md-6">
      <div class="price-box">
        <h5>📚 Tutorial Pembuatan Alat</h5>
        <p>Panduan lengkap IoT: wiring sensor, upload kode, API, dashboard, database.</p>
        <a href="#" class="btn btn-primary btn-sm disabled">Coming Soon</a>
      </div>
    </div>

    <div class="col-md-6">
      <div class="price-box">
        <h5>🛠️ Jasa Konsultasi Project IoT</h5>
        <p>Melayani konsultasi project IoT seperti monitoring suhu, kelembaban, pakan otomatis, smart home, sistem pendeteksi, dan sensor lainnya.</p>
        <ul>
          <li>Bimbingan Sistem & Flow Data</li>
          <li>Pengecekan Kode ESP8266/ESP32</li>
          <li>Perbaikan Error & Debugging</li>
          <li>Koneksi IoT Realtime</li>
        </ul>
      </div>
    </div>

    <div class="col-md-6">
      <div class="price-box">
        <h5>🌎 Sewa Server Live IoT (Hosted)</h5>
        <p>Simpan data sensor online + kontrol alat jarak jauh tanpa harus repot setting server sendiri.</p>
        <p class="text-danger"><strong>⚠ Sistem tarif membuat mahasiswa cepat selesai.</strong><br>
        Semakin lama project tidak selesai → biaya semakin mahal.</p>
      </div>
    </div>
  </div>

  <h4 class="mt-5">💰 Biaya Layanan</h4>
  <table class="table table-bordered bg-white">
    <thead class="table-dark">
      <tr><th>Layanan</th><th>Biaya</th><th>Keterangan</th></tr>
    </thead>
    <tbody>
      <tr>
        <td>Konsultasi IoT</td>
        <td>Fleksibel sesuai kehadiran saat perkuliahan</td>
        <td>Via chat/meet, troubleshooting, debugging</td>
      </tr>
      <tr>
        <td>Sewa Server IoT</td>
        <td>Rp 25.000 / minggu</td>
        <td>Data realtime, API online + dashboard</td>
      </tr>
      <tr>
        <td>Denda Project Lambat</td>
        <td>+ Rp 10.000 / minggu</td>
        <td>Biaya tambah kalau project tidak selesai-selesai</td>
      </tr>
    </tbody>
  </table>

  <div class="alert alert-warning">
    ✅ Sistem ini sengaja dibuat agar mahasiswa <strong>tidak menunda project IoT sampai berbulan-bulan</strong>.
    Semakin cepat selesai → semakin murah biaya yang dikeluarkan.
  </div>

  <a href="/heavens/" class="btn btn-secondary mt-3">← Kembali</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
