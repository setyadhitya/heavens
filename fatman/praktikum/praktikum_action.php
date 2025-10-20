<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../functions.php';

$pdo = db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * Aksi yang wajib CSRF
 */
$needsCsrf = in_array($action, [
  'tambah',
  'edit',
  'hapus',
  'add_praktikum_assisten',
  'remove_praktikum_assisten',
], true);

if ($needsCsrf) {
  $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
  if (!verify_csrf($token)) {
    die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
  }
}

switch ($action) {

  // =========================
  // GET satu praktikum (untuk modal Edit)
  // =========================
  case 'get': {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("
      SELECT p.*, 
             DATE_FORMAT(p.jam_mulai, '%H:%i') AS jam_mulai
      FROM tb_praktikum p
      WHERE p.id = ?
      LIMIT 1
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch() ?: [];
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
  }

  // =========================
  // TAMBAH praktikum
  // =========================
  case 'tambah': {
    $mata_kuliah_id = (int)($_POST['mata_kuliah'] ?? 0);
    $jurusan_id     = (int)($_POST['jurusan_id'] ?? 0);
    $kelas          = trim($_POST['kelas'] ?? '');
    $hari           = trim($_POST['hari'] ?? '');
    $jam_mulai      = $_POST['jam_mulai'] ?? '';
    $catatan        = trim($_POST['catatan'] ?? '');
    $assisten_ids   = array_values(array_filter(array_map('intval', explode(',', $_POST['assisten_ids'] ?? ''))));

    // Validasi matkul -> ambil semester
    $stmt = $pdo->prepare("SELECT semester FROM tb_matkul WHERE id = ?");
    $stmt->execute([$mata_kuliah_id]);
    $matkul = $stmt->fetch();
    if (!$matkul) { echo '<div class="alert alert-danger">❌ Mata kuliah tidak ditemukan.</div>'; exit; }
    $semester = (int)$matkul['semester'];

    // Validasi jurusan
    $stmt = $pdo->prepare("SELECT id FROM tb_jurusan WHERE id = ?");
    $stmt->execute([$jurusan_id]);
    if (!$stmt->fetch()) { echo '<div class="alert alert-danger">❌ Jurusan tidak ditemukan.</div>'; exit; }

    // Hitung jam_ahir & shift
    $jam_ahir = date("H:i", strtotime($jam_mulai . " +3 hours"));
    if ($jam_mulai <= '10:00')      $shift = 'I';
    elseif ($jam_mulai <= '12:30')  $shift = 'II';
    elseif ($jam_mulai <= '15:00')  $shift = 'III';
    elseif ($jam_mulai <= '17:30')  $shift = 'IV';
    else                            $shift = 'V';

    // Cek bentrok hari + shift
    $cek = $pdo->prepare("SELECT COUNT(*) AS total FROM tb_praktikum WHERE hari = ? AND shift = ?");
    $cek->execute([$hari, $shift]);
    $ex = $cek->fetch();
    if ((int)($ex['total'] ?? 0) > 0) {
      echo '<div class="alert alert-danger">⚠️ Jadwal bentrok! Hari <b>' . e(ucfirst($hari)) . '</b> dan <b>' . e($shift) . '</b> sudah digunakan.</div>';
      exit;
    }

    // Insert praktikum
    $stmt = $pdo->prepare("
      INSERT INTO tb_praktikum
      (mata_kuliah, jurusan_id, kelas, semester, hari, jam_mulai, jam_ahir, shift, catatan)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ok = $stmt->execute([
      $mata_kuliah_id, $jurusan_id, $kelas, $semester, $hari, $jam_mulai, $jam_ahir, $shift, $catatan
    ]);

    if ($ok) {
      $praktikum_id = (int)$pdo->lastInsertId();

      // Insert relasi assisten (urut berdasarkan waktu insert)
      if (!empty($assisten_ids)) {
        $ins = $pdo->prepare("INSERT IGNORE INTO tb_assisten_praktikum (praktikum_id, assisten_id) VALUES (?, ?)");
        foreach ($assisten_ids as $aid) {
          $ins->execute([$praktikum_id, $aid]);
        }
      }

      echo '<div class="alert alert-success">✅ Data berhasil ditambahkan.</div>';
    } else {
      echo '<div class="alert alert-danger">❌ Gagal menyimpan data.</div>';
    }
    break;
  }

  // =========================
  // LIST Assisten pada praktikum (untuk modal Edit)
  // =========================
  case 'get_assisten_praktikum': {
    $id = (int)($_GET['id'] ?? 0);
    $q = $pdo->prepare("
      SELECT ap.id AS map_id, a.id AS assisten_id, a.nama, a.nim
      FROM tb_assisten_praktikum ap
      JOIN tb_assisten a ON ap.assisten_id = a.id
      WHERE ap.praktikum_id = ?
      ORDER BY ap.id ASC
    ");
    $q->execute([$id]);
    $out = $q->fetchAll() ?: [];
    header('Content-Type: application/json');
    echo json_encode($out);
    exit;
  }

  // =========================
  // Tambah relasi assisten (modal Edit)
  // =========================
  case 'add_praktikum_assisten': {
    $praktikum_id = (int)($_POST['praktikum_id'] ?? 0);
    $assisten_id  = (int)($_POST['assisten_id'] ?? 0);
    if ($praktikum_id <= 0 || $assisten_id <= 0) {
      echo '<div class="alert alert-danger">Data relasi tidak lengkap.</div>'; exit;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO tb_assisten_praktikum (praktikum_id, assisten_id) VALUES (?, ?)");
    if ($stmt->execute([$praktikum_id, $assisten_id])) {
      echo '<div class="alert alert-success">✅ Assisten ditambahkan.</div>';
    } else {
      echo '<div class="alert alert-danger">❌ Gagal menambah assisten.</div>';
    }
    exit;
  }

  // =========================
  // Hapus relasi assisten (modal Edit)
  // =========================
  case 'remove_praktikum_assisten': {
    $map_id = (int)($_POST['map_id'] ?? 0);
    if ($map_id <= 0) { echo '<div class="alert alert-danger">Data relasi tidak lengkap.</div>'; exit; }
    $del = $pdo->prepare("DELETE FROM tb_assisten_praktikum WHERE id = ?");
    if ($del->execute([$map_id])) {
      echo '<div class="alert alert-warning">Assisten dihapus dari praktikum.</div>';
    } else {
      echo '<div class="alert alert-danger">❌ Gagal hapus relasi.</div>';
    }
    exit;
  }

  // =========================
  // EDIT praktikum (tidak menyentuh relasi assisten)
  // =========================
  case 'edit': {
    $id             = (int)($_POST['id'] ?? 0);
    $mata_kuliah_id = (int)($_POST['mata_kuliah'] ?? 0);
    $jurusan_id     = (int)($_POST['jurusan_id'] ?? 0);
    $kelas          = trim($_POST['kelas'] ?? '');
    $hari           = trim($_POST['hari'] ?? '');
    $jam_mulai      = $_POST['jam_mulai'] ?? '';
    $catatan        = trim($_POST['catatan'] ?? '');

    // Validasi matkul -> ambil semester
    $stmt = $pdo->prepare("SELECT semester FROM tb_matkul WHERE id = ?");
    $stmt->execute([$mata_kuliah_id]);
    $mk = $stmt->fetch();
    if (!$mk) { echo '<div class="alert alert-danger">❌ Mata kuliah tidak ditemukan.</div>'; exit; }
    $semester = (int)$mk['semester'];

    // Validasi jurusan
    $stmt = $pdo->prepare("SELECT id FROM tb_jurusan WHERE id = ?");
    $stmt->execute([$jurusan_id]);
    if (!$stmt->fetch()) { echo '<div class="alert alert-danger">❌ Jurusan tidak ditemukan.</div>'; exit; }

    // Hitung jam_ahir & shift
    $jam_ahir = date("H:i", strtotime($jam_mulai . " +3 hours"));
    if ($jam_mulai <= '10:00')      $shift = 'I';
    elseif ($jam_mulai <= '12:30')  $shift = 'II';
    elseif ($jam_mulai <= '15:00')  $shift = 'III';
    elseif ($jam_mulai <= '17:30')  $shift = 'IV';
    else                            $shift = 'V';

    // Cek bentrok hari + shift (kecuali dirinya)
    $cek = $pdo->prepare("SELECT COUNT(*) AS total FROM tb_praktikum WHERE hari = ? AND shift = ? AND id != ?");
    $cek->execute([$hari, $shift, $id]);
    $ex = $cek->fetch();
    if ((int)($ex['total'] ?? 0) > 0) {
      echo '<div class="alert alert-danger">⚠️ Jadwal bentrok! Hari <b>' . e(ucfirst($hari)) . '</b> dan <b>' . e($shift) . '</b> sudah digunakan.</div>';
      exit;
    }

    // Update data praktikum
    $stmt = $pdo->prepare("
      UPDATE tb_praktikum
      SET mata_kuliah=?, jurusan_id=?, kelas=?, semester=?, hari=?, jam_mulai=?, jam_ahir=?, shift=?, catatan=?
      WHERE id=?
    ");
    $ok = $stmt->execute([
      $mata_kuliah_id, $jurusan_id, $kelas, $semester, $hari, $jam_mulai, $jam_ahir, $shift, $catatan, $id
    ]);

    if ($ok) {
      echo '<div class="alert alert-warning">✅ Data berhasil diperbarui.</div>';
    } else {
      echo '<div class="alert alert-danger">❌ Gagal memperbarui data.</div>';
    }
    break;
  }

  // =========================
  // HAPUS praktikum (+ relasi ikut dihapus)
  // =========================
  case 'hapus': {
    $id = (int)($_POST['id'] ?? 0);

    // Hapus relasi (aman jika belum pakai FK CASCADE)
    $d1 = $pdo->prepare("DELETE FROM tb_assisten_praktikum WHERE praktikum_id = ?");
    $d1->execute([$id]);

    // Hapus praktikum
    $stmt = $pdo->prepare("DELETE FROM tb_praktikum WHERE id = ?");
    if ($stmt->execute([$id])) {
      echo '<div class="alert alert-danger">Data telah dihapus.</div>';
    } else {
      echo '<div class="alert alert-danger">❌ Gagal menghapus.</div>';
    }
    break;
  }

  // =========================
  // LIST tabel (untuk reload via AJAX)
  // =========================
  case 'list': {
    $sql = "
      SELECT p.*, m.mata_kuliah AS nama_matkul, j.jurusan AS nama_jurusan,
      (
        SELECT GROUP_CONCAT(a.nama ORDER BY ap.id ASC SEPARATOR ', ')
        FROM tb_assisten_praktikum ap
        JOIN tb_assisten a ON ap.assisten_id = a.id
        WHERE ap.praktikum_id = p.id
      ) AS daftar_assisten
      FROM tb_praktikum p
      JOIN tb_matkul m ON p.mata_kuliah = m.id
      JOIN tb_jurusan j ON p.jurusan_id = j.id
      ORDER BY p.id DESC
    ";
    $result = $pdo->query($sql);
    $rows = $result->fetchAll();

    if (!empty($rows)) {
      $no = 1;
      foreach ($rows as $row) {
        echo '<tr data-id="' . (int)$row['id'] . '">
          <td>' . $no++ . '</td>
          <td>' . e($row['nama_matkul']) . '</td>
          <td>' . e($row['nama_jurusan']) . '</td>
          <td>' . e($row['kelas']) . '</td>
          <td>' . e($row['semester']) . '</td>
          <td>' . e($row['hari']) . '</td>
          <td>' . e($row['jam_mulai']) . ' - ' . e($row['jam_ahir']) . '</td>
          <td>' . e($row['shift']) . '</td>
          <td>' . e($row['daftar_assisten'] ?? '-') . '</td>
          <td>' . e($row['catatan']) . '</td>
          <td class="text-center">
            <button class="btn btn-warning btn-sm btnEdit" data-id="' . (int)$row['id'] . '">Edit</button>
            <button class="btn btn-danger btn-sm btnHapus" data-id="' . (int)$row['id'] . '">Hapus</button>
          </td>
        </tr>';
      }
    } else {
      echo '<tr><td colspan="11" class="text-center">Belum ada data.</td></tr>';
    }
    break;
  }

  // =========================
  // Default
  // =========================
  default:
    echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
}
