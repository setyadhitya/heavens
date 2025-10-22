<?php
// heavens/iot/sensor/index.php
// Standalone page (tanpa include header/footer). Bootstrap + tema Biru Teknologi.
// Fitur: Search pintar (nama+fungsi+kategori), filter kategori, accordion 10 kategori, 60 sensor, tombol kembali.
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar Sensor IoT – LabKom 3</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  :root{
    --brand:#0d47a1;
    --brand-2:#1565c0;
    --ink:#0b1b33;
  }
  body{background:#f5f7fb;color:#1b2b44}
  .hero {
    background: linear-gradient(135deg, var(--brand), var(--brand-2));
    color:#fff;border-radius:14px;padding:28px 24px;
    box-shadow: 0 10px 30px rgba(13,71,161,.25);
  }
  .hero h1{font-size:1.8rem;margin:0 0 .25rem}
  .toolbar{
    background:#ffffff;border:1px solid #e8eef6;border-radius:12px;padding:14px;
    box-shadow:0 8px 18px rgba(13,71,161,.06);
  }
  .form-control, .form-select{border-radius:10px}
  .badge-pill{border-radius:20px;padding:.35rem .6rem}
  .list-group-item{border:0;border-bottom:1px dashed #e6ecf3;background:transparent}
  .sensor-name{font-weight:600;color:#0d47a1}
  .sensor-usage{color:#44556b}
  .accordion-button:focus{box-shadow:none}
  .accordion-button{font-weight:600}
  .count-badge{background:#e9f1ff;color:#0d47a1;border:1px solid #cfe1ff}
  .muted{color:#6b7a90}
  .footer-note{color:#6b7a90;font-size:.9rem}
  .back-btn{background:#e9efff;border:1px solid #cfe1ff;color:#0d47a1}
  .back-btn:hover{background:#dfe8ff;color:#0a3e8b}
</style>
</head>
<body>

<div class="container py-4">

  <div class="hero mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h1 class="mb-1">Daftar Sensor IoT</h1>
        <p class="mb-0">60 sensor dalam 10 kategori, lengkap dengan fungsi & contoh penggunaan.</p>
      </div>
      <div>
        <a href="/heavens/iot/" class="btn back-btn">← Kembali ke Menu IoT</a>
      </div>
    </div>
  </div>

  <!-- Toolbar: Search + Filter -->
  <div class="toolbar mb-3">
    <div class="row g-2 align-items-center">
      <div class="col-md-7">
        <input id="searchInput" type="search" class="form-control" placeholder="Cari sensor... (contoh: 'air', 'kelembapan', 'MQ', 'tegangan', 'GPS')">
      </div>
      <div class="col-md-3">
        <select id="categoryFilter" class="form-select">
          <option value="ALL" selected>Semua Kategori</option>
          <option value="Suhu & Kelembapan">Suhu & Kelembapan</option>
          <option value="Lingkungan">Lingkungan</option>
          <option value="Air">Air</option>
          <option value="Gas & Kualitas Udara">Gas & Kualitas Udara</option>
          <option value="Cahaya">Cahaya</option>
          <option value="Gerak & Keamanan">Gerak & Keamanan</option>
          <option value="Jarak & Navigasi">Jarak & Navigasi</option>
          <option value="Berat & Tekanan">Berat & Tekanan</option>
          <option value="Arus & Tegangan">Arus & Tegangan</option>
          <option value="Modul IoT (Pendukung)">Modul IoT (Pendukung)</option>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button id="clearBtn" class="btn btn-outline-secondary">Reset</button>
      </div>
    </div>
    <div class="mt-2 small muted">Tip: ketik beberapa kata sekaligus, mis. <em>“air pH tds”</em> atau <em>“arus DC”</em>.</div>
  </div>

  <!-- ACCORDION -->
  <div class="accordion" id="sensorAccordion">

    <!-- 1. Suhu & Kelembapan -->
    <div class="accordion-item" data-category="Suhu & Kelembapan">
      <h2 class="accordion-header" id="h-1">
        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#c-1" aria-expanded="true" aria-controls="c-1">
          1) Suhu & Kelembapan <span class="badge count-badge ms-2" data-cat-count="Suhu & Kelembapan">6</span>
        </button>
      </h2>
      <div id="c-1" class="accordion-collapse collapse show" aria-labelledby="h-1" data-bs-parent="#sensorAccordion">
        <div class="accordion-body">
          <ul class="list-group list-group-flush">
            <?php
            $items1 = [
              ["DHT11","Sensor suhu & kelembapan dasar.","Monitoring lingkungan sederhana (ruang kelas, box alat)."],
              ["DHT22 (AM2302)","Akurasi lebih baik dari DHT11.","Cuaca mini station, inkubator, budidaya."],
              ["DS18B20","Sensor suhu digital (bisa waterproof).","Suhu air kolam, hidroponik, kulkas, outdoor."],
              ["LM35","Sensor suhu analog linear.","Proyek pemula, pengukuran suhu ruangan/perangkat."],
              ["SHT31-D","Suhu & kelembapan akurat (I2C).","Ruangan terkontrol, smart greenhouse."],
              ["TMP36","Suhu analog, mudah dipakai.","Monitoring suhu perangkat elektronik."]
            ];
            foreach($items1 as $it){
              echo '<li class="list-group-item sensor-item" data-name="'.htmlspecialchars($it[0]).'" data-cat="Suhu & Kelembapan" data-desc="'.htmlspecialchars($it[1].' '.$it[2]).'">
                <div class="sensor-name">'.$it[0].'</div>
                <div class="small">'.$it[1].'</div>
                <div class="sensor-usage small">Contoh: '.$it[2].'</div>
              </li>';
            }
            ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- 2. Lingkungan -->
    <div class="accordion-item" data-category="Lingkungan">
      <h2 class="accordion-header" id="h-2">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-2" aria-expanded="false" aria-controls="c-2">
          2) Lingkungan (tekanan, ketinggian, multi-sensor) <span class="badge count-badge ms-2" data-cat-count="Lingkungan">6</span>
        </button>
      </h2>
      <div id="c-2" class="accordion-collapse collapse" aria-labelledby="h-2" data-bs-parent="#sensorAccordion">
        <div class="accordion-body">
          <ul class="list-group list-group-flush">
            <?php
            $items2 = [
              ["BMP280","Tekanan udara & ketinggian.","Cuaca sederhana, logger ketinggian."],
              ["BMP388","Tekanan presisi lebih tinggi.","Drone altimeter, riset atmosfer ringan."],
              ["BME280","Suhu, kelembapan, tekanan (3-in-1).","Weather node kompak, IoT lingkungan."],
              ["BME680","Tambah gas/VOC selain T/H/P.","Indeks kualitas udara dalam ruangan."],
              ["HTU21D","Suhu & kelembapan akurat (I2C).","Ruangan laboratorium, kalibrasi referensi."],
              ["LPS22HB","Sensor tekanan barometrik modern.","Altimeter wearable, logger lingkungan."]
            ];
            foreach($items2 as $it){
              echo '<li class="list-group-item sensor-item" data-name="'.$it[0].'" data-cat="Lingkungan" data-desc="'.$it[1].' '.$it[2].'">
                <div class="sensor-name">'.$it[0].'</div>
                <div class="small">'.$it[1].'</div>
                <div class="sensor-usage small">Contoh: '.$it[2].'</div>
              </li>';
            }
            ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- 3. Air -->
    <div class="accordion-item" data-category="Air">
      <h2 class="accordion-header" id="h-3">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-3" aria-expanded="false" aria-controls="c-3">
          3) Air (kualitas & level) <span class="badge count-badge ms-2" data-cat-count="Air">6</span>
        </button>
      </h2>
      <div id="c-3" class="accordion-collapse collapse" aria-labelledby="h-3" data-bs-parent="#sensorAccordion">
        <div class="accordion-body">
          <ul class="list-group list-group-flush">
            <?php
            $items3 = [
              ["pH Meter (analog/BNC)","Mengukur keasaman air.","Budidaya ikan/udang, hidroponik, laboratorium sederhana."],
              ["TDS/EC Meter","Total dissolved solids / konduktivitas.","Kualitas air minum, hidroponik, aquarium."],
              ["Turbidity Sensor","Kekeruhan air.","Filtrasi, kualitas kolam, limbah sederhana."],
              ["Water Level (kapasitif)","Level air non-kontak langsung.","Tandon, hujan, sumur, kolam terpal."],
              ["Float Switch","Saklar pelampung level air.","Pompa otomatis, proteksi dry run."],
              ["Water Flow YF-S201","Debit aliran (pulse).","Hitung liter/menit, sistem irigasi, dispenser."]
            ];
            foreach($items3 as $it){
              echo '<li class="list-group-item sensor-item" data-name="'.$it[0].'" data-cat="Air" data-desc="'.$it[1].' '.$it[2].'">
                <div class="sensor-name">'.$it[0].'</div>
                <div class="small">'.$it[1].'</div>
                <div class="sensor-usage small">Contoh: '.$it[2].'</div>
              </li>';
            }
            ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- 4. Gas & Kualitas Udara -->
    <div class="accordion-item" data-category="Gas & Kualitas Udara">
      <h2 class="accordion-header" id="h-4">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-4" aria-expanded="false" aria-controls="c-4">
          4) Gas & Kualitas Udara <span class="badge count-badge ms-2" data-cat-count="Gas & Kualitas Udara">6</span>
        </button>
      </h2>
      <div id="c-4" class="accordion-collapse collapse" aria-labelledby="h-4" data-bs-parent="#sensorAccordion">
        <div class="accordion-body">
          <ul class="list-group list-group-flush">
            <?php
            $items4 = [
              ["MQ-2","Asap, LPG, hidrogen.","Alarm kebakaran/gas dapur."],
              ["MQ-3","Alkohol.","Deteksi uap alkohol, breath analyzer sederhana."],
              ["MQ-7","Karbon monoksida (CO).","Keamanan garasi, ruang genset."],
              ["MQ-135","Gas beracun & kualitas udara umum.","Monitoring indoor air quality."],
              ["CCS811","eCO2 & TVOC digital.","Smart home, indikator ventilasi."],
              ["SGP30","eCO2 & TVOC generasi baru.","Node IAQ hemat daya."]
            ];
            foreach($items4 as $it){
              echo '<li class="list-group-item sensor-item" data-name="'.$it[0].'" data-cat="Gas & Kualitas Udara" data-desc="'.$it[1].' '.$it[2].'">
                <div class="sensor-name">'.$it[0].'</div>
                <div class="small">'.$it[1].'</div>
                <div class="sensor-usage small">Contoh: '.$it[2].'</div>
              </li>';
            }
            ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- 5. Cahaya -->
    <div class="accordion-item" data-category="Cahaya">
      <h2 class="accordion-header" id="h-5">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-5" aria-expanded="false" aria-controls="c-5">
          5) Cahaya <span class="badge count-badge ms-2" data-cat-count="Cahaya">6</span>
        </button>
      </h2>
      <div id="c-5" class="accordion-collapse collapse" aria-labelledby="h-5" data-bs-parent="#sensorAccordion">
        <div class="accordion-body">
          <ul class="list-group list-group-flush">
            <?php
            $items5 = [
              ["LDR (Photoresistor)","Intensitas cahaya sederhana.","Lampu otomatis, logger siang/malam."],
              ["BH1750","Lux meter digital (I2C).","Kontrol pencahayaan ruang/greenhouse."],
              ["TSL2561","Lux + IR channel.","Kalibrasi lampu, studi spektral sederhana."],
              ["MAX44009","Lux rendah daya.","Perangkat baterai, wearables."],
              ["GUVA-S12SD","Sensor UV.","Pemantau radiasi matahari, keselamatan UV."],
              ["APDS-9960","Ambient light + gesture.","UI gesture, penyesuaian brightness otomatis."]
            ];
            foreach($items5 as $it){
              echo '<li class="list-group-item sensor-item" data-name="'.$it[0].'" data-cat="Cahaya" data-desc="'.$it[1].' '.$it[2].'">
                <div class="sensor-name">'.$it[0].'</div>
                <div class="small">'.$it[1].'</div>
                <div class="sensor-usage small">Contoh: '.$it[2].'</div>
              </li>';
            }
            ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- 6. Gerak & Keamanan -->
    <div class="accordion-item" data-category="Gerak & Keamanan">
      <h2 class="accordion-header" id="h-6">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-6" aria-expanded="false" aria-controls="c-6">
          6) Gerak & Keamanan <span class="badge count-badge ms-2" data-cat-count="Gerak & Keamanan">6</span>
        </button>
      </h2>
      <div id="c-6" class="accordion-collapse collapse" aria-labelledby="h-6" data-bs-parent="#sensorAccordion">
        <div class="accordion-body">
          <ul class="list-group list-group-flush">
            <?php
            $items6 = [
              ["PIR HC-SR501","Deteksi gerakan manusia (infrared).","Lampu otomatis, alarm ruangan."],
              ["RCWL-0516","Radar/microwave motion.","Deteksi gerak tembus bahan tipis."],
              ["SW-420","Getaran.","Alarm getar, mesin, deteksi gempa sederhana."],
              ["Reed Switch","Magnetik pintu/jendela.","Sistem keamanan rumah."],
              ["Sound Sensor","Level suara mikrofon.","Deteksi kebisingan, trigger event."],
              ["Flame Sensor","Api (spektrum UV).","Peringatan kebakaran, deteksi nyala."]
            ];
            foreach($items6 as $it){
              echo '<li class="list-group-item sensor-item" data-name="'.$it[0].'" data-cat="Gerak & Keamanan" data-desc="'.$it[1].' '.$it[2].'">
                <div class="sensor-name">'.$it[0].'</div>
                <div class="small">'.$it[1].'</div>
                <div class="sensor-usage small">Contoh: '.$it[2].'</div>
              </li>';
            }
            ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- 7. Jarak & Navigasi -->
    <div class="accordion-item" data-category="Jarak & Navigasi">
      <h2 class="accordion-header" id="h-7">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-7" aria-expanded="false" aria-controls="c-7">
          7) Jarak & Navigasi <span class="badge count-badge ms-2" data-cat-count="Jarak & Navigasi">6</span>
        </button>
      </h2>
      <div id="c-7" class="accordion-collapse collapse" aria-labelledby="h-7" data-bs-parent="#sensorAccordion">
        <div class="accordion-body">
          <ul class="list-group list-group-flush">
            <?php
            $items7 = [
              ["HC-SR04","Ultrasonik jarak.","Level air, parkir, deteksi objek."],
              ["VL53L0X","Time-of-Flight (ToF) jarak pendek.","Robotik presisi, counter jarak."],
              ["TOF10120","ToF alternatif kecil.","Deteksi objek kompak."],
              ["Sharp GP2Y0A21","IR distance analog.","Robot line-of-sight, anti tabrak."],
              ["GPS NEO-6M","Lokasi (GNSS).","Pelacakan aset, logger rute."],
              ["Kompas HMC5883L/QMC5883L","Magnetometer heading.","Navigasi robot, orientasi arah."]
            ];
            foreach($items7 as $it){
              echo '<li class="list-group-item sensor-item" data-name="'.$it[0].'" data-cat="Jarak & Navigasi" data-desc="'.$it[1].' '.$it[2].'">
                <div class="sensor-name">'.$it[0].'</div>
                <div class="small">'.$it[1].'</div>
                <div class="sensor-usage small">Contoh: '.$it[2].'</div>
              </li>';
            }
            ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- 8. Berat & Tekanan -->
    <div class="accordion-item" data-category="Berat & Tekanan">
      <h2 class="accordion-header" id="h-8">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-8" aria-expanded="false" aria-controls="c-8">
          8) Berat & Tekanan <span class="badge count-badge ms-2" data-cat-count="Berat & Tekanan">6</span>
        </button>
      </h2>
      <div id="c-8" class="accordion-collapse collapse" aria-labelledby="h-8" data-bs-parent="#sensorAccordion">
        <div class="accordion-body">
          <ul class="list-group list-group-flush">
            <?php
            $items8 = [
              ["Load Cell + HX711","Berat/timbangan digital.","Pakan otomatis, timbangan panen."],
              ["FSR402","Gaya tekan (force).","Deteksi sentuh/tekan, monitoring beban kecil."],
              ["MPX5700AP","Tekanan fluida (0–700 kPa).","Monitoring pipa, pompa, manifold."],
              ["MPS20N0040D","Tekanan diferensial murah.","Filter udara, level tangki via tekanan."],
              ["MS5611","Tekanan barometrik presisi.","Altimeter presisi, logging lingkungan."],
              ["Transduser 0–1.2MPa (0–5V)","Tekanan industri (analog).","Sistem hidrolik/air bertekanan."]
            ];
            foreach($items8 as $it){
              echo '<li class="list-group-item sensor-item" data-name="'.$it[0].'" data-cat="Berat & Tekanan" data-desc="'.$it[1].' '.$it[2].'">
                <div class="sensor-name">'.$it[0].'</div>
                <div class="small">'.$it[1].'</div>
                <div class="sensor-usage small">Contoh: '.$it[2].'</div>
              </li>';
            }
            ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- 9. Arus & Tegangan -->
    <div class="accordion-item" data-category="Arus & Tegangan">
      <h2 class="accordion-header" id="h-9">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-9" aria-expanded="false" aria-controls="c-9">
          9) Arus & Tegangan <span class="badge count-badge ms-2" data-cat-count="Arus & Tegangan">6</span>
        </button>
      </h2>
      <div id="c-9" class="accordion-collapse collapse" aria-labelledby="h-9" data-bs-parent="#sensorAccordion">
        <div class="accordion-body">
          <ul class="list-group list-group-flush">
            <?php
            $items9 = [
              ["ACS712","Sensor arus AC/DC (Hall).","Monitoring beban, proteksi arus."],
              ["INA219","Arus & tegangan DC presisi (I2C).","Power monitoring IoT, baterai."],
              ["INA226","Shunt power monitor presisi.","Pengukuran arus besar, logging daya."],
              ["ZMPT101B","Tegangan AC (isolasi).","Meteran tegangan PLN mini."],
              ["SCT-013 CT","Trafo arus non-intrusif.","Monitoring arus AC tanpa potong kabel."],
              ["HLW8012/BL0937","Chip metering energi.","KWh meter DIY, smart plug."]
            ];
            foreach($items9 as $it){
              echo '<li class="list-group-item sensor-item" data-name="'.$it[0].'" data-cat="Arus & Tegangan" data-desc="'.$it[1].' '.$it[2].'">
                <div class="sensor-name">'.$it[0].'</div>
                <div class="small">'.$it[1].'</div>
                <div class="sensor-usage small">Contoh: '.$it[2].'</div>
              </li>';
            }
            ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- 10. Modul IoT (Pendukung) -->
    <div class="accordion-item" data-category="Modul IoT (Pendukung)">
      <h2 class="accordion-header" id="h-10">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-10" aria-expanded="false" aria-controls="c-10">
          10) Modul IoT (Pendukung Proyek) <span class="badge count-badge ms-2" data-cat-count="Modul IoT (Pendukung)">6</span>
        </button>
      </h2>
      <div id="c-10" class="accordion-collapse collapse" aria-labelledby="h-10" data-bs-parent="#sensorAccordion">
        <div class="accordion-body">
          <ul class="list-group list-group-flush">
            <?php
            $items10 = [
              ["Relay 1/4 Channel","Saklar beban AC/DC dikontrol mikrokontroler.","Pompa, lampu, aktuator."],
              ["MOSFET Driver (IRF520/ALD)","Penguat beban DC.","Motor, solenoid, LED strip."],
              ["RFID RC522 (13.56MHz)","Pembaca kartu RFID.","Akses pintu, absensi sederhana."],
              ["OLED 0.96\" SSD1306","Layar mini I2C.","Menampilkan status sensor & menu."],
              ["RTC DS3231","Jam real-time akurat.","Penjadwalan, logging bertimestamp."],
              ["MicroSD Module","Penyimpanan data.","Datalogger, backup offline."]
            ];
            foreach($items10 as $it){
              echo '<li class="list-group-item sensor-item" data-name="'.$it[0].'" data-cat="Modul IoT (Pendukung)" data-desc="'.$it[1].' '.$it[2].'">
                <div class="sensor-name">'.$it[0].'</div>
                <div class="small">'.$it[1].'</div>
                <div class="sensor-usage small">Contoh: '.$it[2].'</div>
              </li>';
            }
            ?>
          </ul>
        </div>
      </div>
    </div>

  </div><!-- /accordion -->

  <div class="mt-4 footer-note">
    Catatan: daftar di atas berorientasi edukasi. Spesifikasi teknis (range, akurasi, tegangan kerja) bisa berbeda antar pabrikan—cek datasheet sebelum implementasi.
  </div>

  <div class="mt-3">
    <a href="/heavens/iot/" class="btn back-btn">← Kembali ke Menu IoT</a>
  </div>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ====== Search Pintar + Filter Kategori ======
(function(){
  const q = document.getElementById('searchInput');
  const sel = document.getElementById('categoryFilter');
  const clearBtn = document.getElementById('clearBtn');
  const items = Array.from(document.querySelectorAll('.sensor-item'));
  const accItems = Array.from(document.querySelectorAll('.accordion-item'));
  const countBadges = document.querySelectorAll('[data-cat-count]');

  function norm(s){ return (s||'').toString().toLowerCase(); }

  function apply(){
    const query = norm(q.value).trim();
    const tokens = query.length ? query.split(/\s+/).filter(Boolean) : [];
    const cat = sel.value;

    // filter each sensor item
    items.forEach(li=>{
      const name = norm(li.getAttribute('data-name'));
      const desc = norm(li.getAttribute('data-desc'));
      const c = li.getAttribute('data-cat');
      let okCat = (cat === 'ALL' || c === cat);
      let okText = true;
      if(tokens.length){
        const hay = name + ' ' + desc + ' ' + norm(c);
        okText = tokens.every(t => hay.includes(t));
      }
      const show = okCat && okText;
      li.style.display = show ? '' : 'none';
    });

    // update per-accordion visibility & badge counts
    accItems.forEach(box=>{
      const lis = box.querySelectorAll('.sensor-item');
      let visibleCount = 0;
      lis.forEach(li=>{ if(li.style.display !== 'none') visibleCount++; });
      // hide accordion item if filter by category excludes it entirely
      const thisCat = box.getAttribute('data-category');
      const visibleByCat = (cat === 'ALL' || cat === thisCat);
      box.style.display = (visibleCount>0 && visibleByCat) ? '' : (visibleByCat ? '' : 'none');

      const badge = box.querySelector(`[data-cat-count="${thisCat}"]`);
      if(badge){ badge.textContent = visibleCount; }
    });
  }

  function resetAll(){
    q.value = '';
    sel.value = 'ALL';
    apply();
  }

  q.addEventListener('input', apply);
  sel.addEventListener('change', apply);
  clearBtn.addEventListener('click', resetAll);

  // initial
  apply();
})();
</script>
</body>
</html>
