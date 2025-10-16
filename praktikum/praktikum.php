<?php
require_once __DIR__ . '/../functions.php';
require_login_and_redirect();
include '../navbar.php';
?>

<?php
// Ambil data dropdown
$matkul = $mysqli->query("SELECT * FROM tb_matkul ORDER BY mata_kuliah ASC");
$jurusan = $mysqli->query("SELECT * FROM tb_jurusan ORDER BY jurusan ASC");
$asisten = $mysqli->query("SELECT * FROM tb_assisten WHERE status='aktif' ORDER BY nama ASC");

$matkulData = $matkul->fetch_all(MYSQLI_ASSOC);
$jurusanData = $jurusan->fetch_all(MYSQLI_ASSOC);
$asistenData = $asisten->fetch_all(MYSQLI_ASSOC);


// Waktu dropdown 07:30 - 19:30 (selisih 30 menit)
$jam_list = [];
for ($h = 7; $h <= 19; $h++) {
  foreach ([0, 30] as $m) {
    $jam_list[] = sprintf("%02d:%02d", $h, $m);
  }
}

// Ambil semua data praktikum
$result = $mysqli->query("
  SELECT p.*, m.mata_kuliah AS nama_matkul
  FROM tb_praktikum p
  JOIN tb_matkul m ON p.mata_kuliah = m.id
  ORDER BY p.id DESC
");
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Data Praktikum</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
.modal {
  z-index: 1050 !important;
}
.modal-backdrop {
  z-index: 1040 !important;
}
</style>

</head>

<body class="bg-light">

  <div class="container mt-4">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Praktikum</h5>
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
                <th>Jurusan</th>
                <th>Kelas</th>
                <th>Semester</th>
                <th>Hari</th>
                <th>Jam</th>
                <th>Shift</th>
                <th>Asisten</th>
                <th>Catatan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="praktikumData">
              <?php if ($result->num_rows > 0):
                $no = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr data-id="<?= $row['id']; ?>">
                    <td><?= $no++; ?></td>
                    <td><?= e($row['nama_matkul']); ?></td>
                    <td><?= e($row['jurusan']); ?></td>
                    <td><?= e($row['kelas']); ?></td>
                    <td><?= e($row['semester']); ?></td>
                    <td><?= e($row['hari']); ?></td>
                    <td><?= e($row['jam_mulai']); ?> - <?= e($row['jam_ahir']); ?></td>
                    <td><?= e($row['shift']); ?></td>
                    <td><?= e($row['assisten']); ?></td>
                    <td><?= e($row['catatan']); ?></td>
                    <td class="text-center">
                      <button class="btn btn-warning btn-sm btnEdit" data-id="<?= $row['id']; ?>">Edit</button>
                      <button class="btn btn-danger btn-sm btnHapus" data-id="<?= $row['id']; ?>">Hapus</button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="11" class="text-center">Belum ada data.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- 🔹 Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
  <form id="formTambah" method="post" class="row g-2 mb-4">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
          <input type="hidden" name="action" value="tambah">

          <!-- Mata Kuliah -->
          <div class="col-md-3">
            <select name="mata_kuliah" id="mata_kuliah" class="form-select" required>
              <option value="">-- Pilih Mata Kuliah --</option>
              <?php foreach ($matkulData as $m): ?>
                <option value="<?= $m['id']; ?>" data-semester="<?= e($m['semester']); ?>">
                  <?= e($m['mata_kuliah']); ?> (Smt <?= e($m['semester']); ?>)
                </option>

              <?php endforeach; ?>
            </select>
          </div>

          <!-- Jurusan -->
          <div class="col-md-2">
            <select name="jurusan" class="form-select" required>
              <option value="">-- Jurusan --</option>
              <?php foreach ($jurusanData as $j): ?>
                <option value="<?= e($j['jurusan']); ?>"><?= e($j['jurusan']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Kelas -->
          <div class="col-md-2">
            <select name="kelas" class="form-select" required>
              <option value="">-- Kelas --</option>
              <option value="Reguler">Reguler</option>
              <option value="Karyawan">Karyawan</option>
            </select>
          </div>

          <!-- Hari -->
          <div class="col-md-2">
            <select name="hari" class="form-select" required>
              <option value="">-- Hari --</option>
              <option value="senin">Senin</option>
              <option value="selasa">Selasa</option>
              <option value="rabu">Rabu</option>
              <option value="kamis">Kamis</option>
              <option value="jumat">Jumat</option>
            </select>
          </div>

          <!-- Jam Mulai -->
          <div class="col-md-2">
            <select name="jam_mulai" class="form-select" required>
              <option value="">-- Jam Mulai --</option>
              <?php foreach ($jam_list as $jam): ?>
                <option value="<?= $jam; ?>"><?= $jam; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Asisten -->
          <div class="col-md-3 mt-2">
            <select name="assisten" class="form-select" required>
              <option value="">-- Pilih Asisten --</option>
              <?php foreach ($asistenData as $a): ?>
                <option value="<?= e($a['nama']); ?>"><?= e($a['nama']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Catatan -->
          <div class="col-md-4 mt-2">
            <input type="text" name="catatan" class="form-control" placeholder="Catatan (Opsional)">
          </div>

          <!-- Tombol Simpan -->
          <div class="col-md-2 mt-2 d-grid">
            <button type="submit" class="btn btn-primary">Tambah</button>
          </div>
        </form>
      </div>
    </div>
  </div>


  <!-- 🔹 Modal Edit -->
  <div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form id="formEdit" method="post" class="row g-2 mb-4">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit_id">

          <!-- Mata Kuliah -->
          <div class="col-md-3">
            <select name="mata_kuliah" id="edit_mata_kuliah" class="form-select" required>
              <option value="">-- Pilih Mata Kuliah --</option>
              <?php
              $matkul_edit = $mysqli->query("SELECT * FROM tb_matkul ORDER BY id ASC");
              while ($m = $matkul_edit->fetch_assoc()):
                ?>
                <option value="<?php echo $m['id']; ?>" data-semester="<?php echo e($m['semester']); ?>">
                  <?php echo e($m['mata_kuliah']); ?> (Smt <?php echo e($m['semester']); ?>)
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Jurusan -->
          <div class="col-md-2">
            <select name="jurusan" id="edit_jurusan" class="form-select" required>
              <option value="">-- Pilih Jurusan --</option>
              <?php
              $jurusan_edit = $mysqli->query("SELECT * FROM tb_jurusan ORDER BY jurusan ASC");
              while ($j = $jurusan_edit->fetch_assoc()):
                ?>
                <option value="<?php echo e($j['jurusan']); ?>"><?php echo e($j['jurusan']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Kelas -->
          <div class="col-md-2">
            <select name="kelas" id="edit_kelas" class="form-select" required>
              <option value="">-- Kelas --</option>
              <option value="Reguler">Reguler</option>
              <option value="Karyawan">Karyawan</option>
            </select>
          </div>

          <!-- Hari -->
          <div class="col-md-2">
            <select name="hari" id="edit_hari" class="form-select" required>
              <option value="">-- Hari --</option>
              <option value="senin">Senin</option>
              <option value="selasa">Selasa</option>
              <option value="rabu">Rabu</option>
              <option value="kamis">Kamis</option>
              <option value="jumat">Jumat</option>
            </select>
          </div>

          <!-- Jam Mulai -->
          <div class="col-md-2">
            <select name="jam_mulai" id="edit_jam_mulai" class="form-select" required>
              <option value="">-- Jam Mulai --</option>
              <?php
              for ($h = 7; $h <= 19; $h++) {
                foreach ([0, 30] as $m) {
                  $jam = sprintf("%02d:%02d", $h, $m);
                  echo "<option value='$jam'>$jam</option>";
                }
              }
              ?>
            </select>
          </div>

          <!-- Asisten -->
          <div class="col-md-3 mt-2">
            <select name="assisten" id="edit_assisten" class="form-select" required>
              <option value="">-- Pilih Asisten --</option>
              <?php
              $asisten_edit = $mysqli->query("SELECT * FROM tb_assisten WHERE status='aktif' ORDER BY nama ASC");
              while ($a = $asisten_edit->fetch_assoc()):
                ?>
                <option value="<?php echo e($a['nama']); ?>"><?php echo e($a['nama']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Catatan -->
          <div class="col-md-4 mt-2">
            <input type="text" name="catatan" id="edit_catatan" class="form-control" placeholder="Catatan (Opsional)">
          </div>

          <!-- Tombol Simpan -->
          <div class="col-md-2 mt-2 d-grid">
            <button type="submit" class="btn btn-warning">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', () => {

      // 🔹 Tambah Data (AJAX)
      const formTambah = document.getElementById('formTambah');
      if (formTambah) {
        formTambah.addEventListener('submit', async e => {
          e.preventDefault();
          console.log("🟡 [DEBUG] Form tambah dikirim!");

          try {
            const res = await fetch('praktikum_action.php', {
              method: 'POST',
              body: new FormData(e.target)
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const html = await res.text();
            console.log("🔵 [DEBUG] Response dari server:", html);

            // Tampilkan alert di atas tabel
            document.getElementById('alertArea').innerHTML = html;

            // Tutup modal & hilangkan fokus untuk cegah warning
            const modalEl = document.getElementById('modalTambah');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
              modal.hide();

              // Tunggu 200ms agar Bootstrap hapus backdrop dulu sebelum reload tabel
              setTimeout(async () => {
                document.activeElement.blur();
                await loadPraktikumTable();

                // 🔹 Hapus backdrop sisa kalau masih ada
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
              }, 200);
            }


            // Reset form
            e.target.reset();

          } catch (err) {
            console.error("❌ [DEBUG] Fetch error:", err);
          }
        });
      }

      // 🔹 Hapus Data
      document.querySelectorAll('.btnHapus').forEach(btn => {
        btn.addEventListener('click', async () => {
          if (confirm('Yakin hapus data ini?')) {
            const form = new FormData();
            form.append('action', 'hapus');
            form.append('id', btn.dataset.id);
            form.append('csrf_token', '<?= e(csrf_token()); ?>');
            const res = await fetch('praktikum_action.php', { method: 'POST', body: form });
            const html = await res.text();
            document.getElementById('alertArea').innerHTML = html;
            await loadPraktikumTable();
          }
        });
      });

      // 🔹 Edit Data
      // 🔹 Event klik tombol Edit
      document.querySelectorAll('.btnEdit').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id = btn.dataset.id;
          console.log("🟡 [DEBUG] Klik edit ID:", id);

          try {
            const res = await fetch('praktikum_action.php?action=get&id=' + id);
            if (!res.ok) throw new Error("Gagal ambil data dari server.");
            const data = await res.json();

            console.log("🔵 [DEBUG] Data dari server:", data);

            // 🧩 Isi semua field dari hasil query
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_mata_kuliah').value = data.mata_kuliah || "";
            document.getElementById('edit_jurusan').value = data.jurusan || "";
            document.getElementById('edit_kelas').value = data.kelas || "";
            document.getElementById('edit_hari').value = data.hari || "";
            document.getElementById('edit_assisten').value = data.assisten || "";
            document.getElementById('edit_catatan').value = data.catatan || "";

            // 🔹 Format jam supaya cocok (07:30 bukan 07:30:00)
            const jamMulai = (data.jam_mulai || '').substring(0, 5);
            const jamMulaiField = document.getElementById('edit_jam_mulai');

            // Jika dropdown belum punya jam ini, tambahkan sementara (agar muncul)
            if (![...jamMulaiField.options].some(opt => opt.value === jamMulai)) {
              const opt = document.createElement('option');
              opt.value = jamMulai;
              opt.textContent = jamMulai;
              jamMulaiField.appendChild(opt);
            }
            jamMulaiField.value = jamMulai;

            // ✅ Tampilkan modal edit
            new bootstrap.Modal(document.getElementById('modalEdit')).show();

          } catch (err) {
            console.error("❌ [DEBUG] Error ambil data:", err);
            alert("Terjadi kesalahan saat mengambil data untuk edit.");
          }
        });
      });

      // 🔹 Reset form setiap kali modal Edit ditutup (agar tidak simpan nilai lama)
      document.getElementById('modalEdit').addEventListener('hidden.bs.modal', () => {
        const formEdit = document.getElementById('formEdit');
        if (formEdit) formEdit.reset();
      });




      // 🔹 Submit Edit
      const formEdit = document.getElementById('formEdit');
      if (formEdit) {
        formEdit.addEventListener('submit', async e => {
          e.preventDefault();
          const res = await fetch('praktikum_action.php', {
            method: 'POST',
            body: new FormData(e.target)
          });
          const html = await res.text();
          document.getElementById('alertArea').innerHTML = html;

          const modalEl = document.getElementById('modalEdit');
          const modal = bootstrap.Modal.getInstance(modalEl);
          if (modal) {
            modal.hide();

            // Tunggu 200ms agar Bootstrap hapus backdrop dulu sebelum reload tabel
            setTimeout(async () => {
              document.activeElement.blur();
              await loadPraktikumTable();

              // 🔹 Hapus backdrop sisa kalau masih ada
              document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
              document.body.classList.remove('modal-open');
              document.body.style.overflow = '';
            }, 200);
          }

        });
      }

      // 🔹 Fungsi untuk reload tabel tanpa reload halaman
      async function loadPraktikumTable() {
        try {
          const res = await fetch('praktikum_action.php?action=list');
          const html = await res.text();
          document.getElementById('praktikumData').innerHTML = html;

          // Re-attach listener ke tombol baru (karena tabel diperbarui)
          attachTableListeners();
        } catch (err) {
          console.error("❌ [DEBUG] Gagal memuat tabel:", err);
        }
      }

      // 🔹 Pasang ulang event listener setelah tabel diperbarui
      function attachTableListeners() {
        document.querySelectorAll('.btnEdit').forEach(btn => {
          btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            const res = await fetch('praktikum_action.php?action=get&id=' + id);
            const data = await res.json();
            for (let key in data) {
              const el = document.getElementById('edit_' + key);
              if (el) el.value = data[key];
            }
            new bootstrap.Modal(document.getElementById('modalEdit')).show();
          });
        });

        document.querySelectorAll('.btnHapus').forEach(btn => {
          btn.addEventListener('click', async () => {
            if (confirm('Yakin hapus data ini?')) {
              const form = new FormData();
              form.append('action', 'hapus');
              form.append('id', btn.dataset.id);
              form.append('csrf_token', '<?= e(csrf_token()); ?>');
              const res = await fetch('praktikum_action.php', { method: 'POST', body: form });
              const html = await res.text();
              document.getElementById('alertArea').innerHTML = html;
              await loadPraktikumTable();
            }
          });
        });
      }

      // 🔧 Bersihkan backdrop sisa setiap kali modal ditutup
      document.addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
      });
    });


    // 🔹 Fungsi Pasang Event Edit dan Hapus (agar bisa dipanggil ulang setelah reload)
function attachTableListeners() {
  document.querySelectorAll('.btnEdit').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      console.log("🟡 [DEBUG] Klik Edit ID:", id);

      try {
        const res = await fetch('praktikum_action.php?action=get&id=' + id);
        if (!res.ok) throw new Error("Gagal ambil data dari server.");
        const data = await res.json();
        console.log("🔵 [DEBUG] Data dari server:", data);

        // 🧩 Isi semua field dari hasil query
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_jurusan').value = data.jurusan || "";
        document.getElementById('edit_kelas').value = data.kelas || "";
        document.getElementById('edit_hari').value = data.hari || "";
        document.getElementById('edit_assisten').value = data.assisten || "";
        document.getElementById('edit_catatan').value = data.catatan || "";

        // 🔹 Format jam supaya cocok (07:30 bukan 07:30:00)
        const jamMulai = (data.jam_mulai || '').substring(0, 5);
        const jamMulaiField = document.getElementById('edit_jam_mulai');
        if (![...jamMulaiField.options].some(opt => opt.value === jamMulai)) {
          const opt = document.createElement('option');
          opt.value = jamMulai;
          opt.textContent = jamMulai;
          jamMulaiField.appendChild(opt);
        }
        jamMulaiField.value = jamMulai;

        // 🔹 Set dropdown mata kuliah sesuai data lama
        const mataKuliahField = document.getElementById('edit_mata_kuliah');
        if (mataKuliahField) {
          mataKuliahField.value = data.mata_kuliah; // id dari tb_matkul
        }

        // ✅ Tampilkan modal edit
        const modalEdit = new bootstrap.Modal(document.getElementById('modalEdit'));
        modalEdit.show();

      } catch (err) {
        console.error("❌ [DEBUG] Error ambil data:", err);
        alert("Terjadi kesalahan saat mengambil data untuk edit.");
      }
    });
  });

  // 🔹 Event Hapus juga dipasang ulang
  document.querySelectorAll('.btnHapus').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (confirm('Yakin hapus data ini?')) {
        const form = new FormData();
        form.append('action', 'hapus');
        form.append('id', btn.dataset.id);
        form.append('csrf_token', '<?= e(csrf_token()); ?>');
        const res = await fetch('praktikum_action.php', { method: 'POST', body: form });
        const html = await res.text();
        document.getElementById('alertArea').innerHTML = html;
        await loadPraktikumTable();
        attachTableListeners(); // pasang ulang event setiap kali tabel dimuat ulang

      }
    });
  });
}



// 🧹 Bersihkan sisa modal dan backdrop setelah modal ditutup
document.addEventListener('hidden.bs.modal', function (event) {
  document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
  document.body.classList.remove('modal-open');
  document.body.style.overflow = '';
  document.body.style.paddingRight = '';
});

// 🧩 Pastikan elemen input bisa difokus lagi setelah reload tabel
document.addEventListener('shown.bs.modal', function (event) {
  const input = event.target.querySelector('input, textarea, select');
  if (input) input.focus();
});

  </script>


</body>

</html>