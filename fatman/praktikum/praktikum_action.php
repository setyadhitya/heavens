<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../functions.php';



$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!verify_csrf($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SESSION['csrf_token'])) {
  die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
}

switch ($action) {
  case 'tambah':
  $mata_kuliah_id = (int)$_POST['mata_kuliah'];
  $jurusan = trim($_POST['jurusan']);
  $kelas = trim($_POST['kelas']);
  $hari = trim($_POST['hari']);
  $jam_mulai = $_POST['jam_mulai'];
  $assisten = trim($_POST['assisten']);
  $catatan = trim($_POST['catatan']);

  // 🔹 Ambil semester dari tb_matkul
  $stmt = $mysqli->prepare("SELECT semester FROM tb_matkul WHERE id = ?");
  $stmt->bind_param("i", $mata_kuliah_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $matkul = $result->fetch_assoc();

  if (!$matkul) {
    echo '<div class="alert alert-danger">❌ Mata kuliah tidak ditemukan.</div>';
    exit;
  }

  $semester = $matkul['semester'];

  // 🔹 Hitung jam akhir = jam mulai + 3 jam
  $jam_ahir = date("H:i", strtotime("$jam_mulai +3 hours"));

  // 🔹 Tentukan shift otomatis
  if ($jam_mulai <= '10:00') $shift = 'I';
  elseif ($jam_mulai <= '12:30') $shift = 'II';
  elseif ($jam_mulai <= '15:00') $shift = 'III';
  elseif ($jam_mulai <= '17:30') $shift = 'IV';
  else $shift = 'V';

  // 🚫 Cek duplikasi (hari + shift)
  $cek = $mysqli->prepare("SELECT COUNT(*) AS total FROM tb_praktikum WHERE hari = ? AND shift = ?");
  $cek->bind_param("ss", $hari, $shift);
  $cek->execute();
  $res = $cek->get_result()->fetch_assoc();

  if ($res['total'] > 0) {
    echo '<div class="alert alert-danger">⚠️ Jadwal bentrok! Hari <b>' . e(ucfirst($hari)) . '</b> dan <b>' . e($shift) . '</b> sudah digunakan.</div>';
    exit;
  }

  // 🔹 Simpan ke tabel tb_praktikum
  $stmt = $mysqli->prepare("INSERT INTO tb_praktikum 
    (mata_kuliah, jurusan, kelas, semester, hari, jam_mulai, jam_ahir, shift, assisten, catatan)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

  if (!$stmt) {
    echo '<div class="alert alert-danger">❌ Prepare gagal: ' . $mysqli->error . '</div>';
    exit;
  }

  $stmt->bind_param(
    "isssssssss",
    $mata_kuliah_id,
    $jurusan,
    $kelas,
    $semester,
    $hari,
    $jam_mulai,
    $jam_ahir,
    $shift,
    $assisten,
    $catatan
  );

  if ($stmt->execute()) {
    echo '<div class="alert alert-success">✅ Data berhasil ditambahkan.</div>';
  } else {
    echo '<div class="alert alert-danger">❌ Gagal menyimpan data: ' . $stmt->error . '</div>';
  }
  break;






  case 'get':
  $id = (int)$_GET['id'];
  $res = $mysqli->query("
    SELECT p.*, m.mata_kuliah AS nama_matkul,DATE_FORMAT(p.jam_mulai, '%H:%i') AS jam_mulai
    FROM tb_praktikum p
    
    JOIN tb_matkul m ON p.mata_kuliah = m.id
    WHERE p.id=$id
  ");
  echo json_encode($res->fetch_assoc());
  break;


  case 'edit':
  $id = (int)$_POST['id'];
  $mata_kuliah_id = (int)$_POST['mata_kuliah'];
  $jurusan = trim($_POST['jurusan']);
  $kelas = trim($_POST['kelas']);
  $hari = trim($_POST['hari']);
  $jam_mulai = $_POST['jam_mulai'];
  $assisten = trim($_POST['assisten']);
  $catatan = trim($_POST['catatan']);

  // 🔹 Ambil semester dari tb_matkul
  $stmt = $mysqli->prepare("SELECT semester FROM tb_matkul WHERE id = ?");
  $stmt->bind_param("i", $mata_kuliah_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $matkul = $result->fetch_assoc();

  if (!$matkul) {
    echo '<div class="alert alert-danger">❌ Mata kuliah tidak ditemukan.</div>';
    exit;
  }

  $semester = $matkul['semester'];

  // 🔹 Hitung jam akhir +3 jam
  $jam_ahir = date("H:i", strtotime("$jam_mulai +3 hours"));

  // 🔹 Tentukan shift otomatis
  if ($jam_mulai <= '10:00') $shift = 'I';
  elseif ($jam_mulai <= '12:30') $shift = 'II';
  elseif ($jam_mulai <= '15:00') $shift = 'III';
  elseif ($jam_mulai <= '17:30') $shift = 'IV';
  else $shift = 'V';

  // 🚫 Cek bentrok (hari + shift) kecuali data ini sendiri
  $cek = $mysqli->prepare("SELECT COUNT(*) AS total FROM tb_praktikum WHERE hari = ? AND shift = ? AND id != ?");
  $cek->bind_param("ssi", $hari, $shift, $id);
  $cek->execute();
  $res = $cek->get_result()->fetch_assoc();

  if ($res['total'] > 0) {
    echo '<div class="alert alert-danger">⚠️ Jadwal bentrok! Hari <b>' . e(ucfirst($hari)) . '</b> dan <b>' . e($shift) . '</b> sudah digunakan.</div>';
    exit;
  }

  // 🔹 Update data
  $stmt = $mysqli->prepare("UPDATE tb_praktikum 
    SET mata_kuliah=?, jurusan=?, kelas=?, semester=?, hari=?, jam_mulai=?, jam_ahir=?, shift=?, assisten=?, catatan=? 
    WHERE id=?");

  if (!$stmt) {
    echo '<div class="alert alert-danger">❌ Prepare gagal: ' . $mysqli->error . '</div>';
    exit;
  }

  $stmt->bind_param(
    "isssssssssi",
    $mata_kuliah_id,
    $jurusan,
    $kelas,
    $semester,
    $hari,
    $jam_mulai,
    $jam_ahir,
    $shift,
    $assisten,
    $catatan,
    $id
  );

  if ($stmt->execute()) {
    echo '<div class="alert alert-warning">✅ Data berhasil diperbarui.</div>';
  } else {
    echo '<div class="alert alert-danger">❌ Gagal memperbarui data: ' . $stmt->error . '</div>';
  }
  break;





  case 'hapus':
    $id = (int) $_POST['id'];
    $stmt = $mysqli->prepare("DELETE FROM tb_praktikum WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo '<div class="alert alert-danger">Data telah dihapus.</div>';
    break;

  case 'list':
    $result = $mysqli->query("
    SELECT p.*, m.mata_kuliah AS nama_matkul 
    FROM tb_praktikum p
    JOIN tb_matkul m ON p.mata_kuliah = m.id
    ORDER BY p.id DESC
  ");

    if ($result->num_rows > 0) {
      $no = 1;
      while ($row = $result->fetch_assoc()) {
        echo '<tr data-id="' . $row['id'] . '">
          <td>' . $no++ . '</td>
<td>' . e($row['nama_matkul']) . '</td>
          <td>' . e($row['jurusan']) . '</td>
          <td>' . e($row['kelas']) . '</td>
          <td>' . e($row['semester']) . '</td>
          <td>' . e($row['hari']) . '</td>
          <td>' . e($row['jam_mulai']) . ' - ' . e($row['jam_ahir']) . '</td>
          <td>' . e($row['shift']) . '</td>
          <td>' . e($row['assisten']) . '</td>
          <td>' . e($row['catatan']) . '</td>
          <td class="text-center">
            <button class="btn btn-warning btn-sm btnEdit" data-id="' . $row['id'] . '">Edit</button>
            <button class="btn btn-danger btn-sm btnHapus" data-id="' . $row['id'] . '">Hapus</button>
          </td>
        </tr>';
      }
    } else {
      echo '<tr><td colspan="11" class="text-center">Belum ada data.</td></tr>';
    }
    break;

  default:
    echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
}

