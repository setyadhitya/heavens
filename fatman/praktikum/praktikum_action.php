<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../functions.php';
global $mysqli;

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
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $mysqli->prepare("
            SELECT p.*, m.mata_kuliah AS nama_matkul,
                   DATE_FORMAT(p.jam_mulai, '%H:%i') AS jam_mulai
            FROM tb_praktikum p
            JOIN tb_matkul m ON p.mata_kuliah = m.id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc() ?: [];
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;

    // =========================
    // TAMBAH praktikum
    // =========================
    case 'tambah':
        $mata_kuliah_id = (int)($_POST['mata_kuliah'] ?? 0);
        $jurusan        = trim($_POST['jurusan'] ?? '');
        $kelas          = trim($_POST['kelas'] ?? '');
        $hari           = trim($_POST['hari'] ?? '');
        $jam_mulai      = $_POST['jam_mulai'] ?? '';
        $catatan        = trim($_POST['catatan'] ?? '');
        $assisten_ids   = array_filter(array_map('intval', explode(',', $_POST['assisten_ids'] ?? '')));

        // Validasi matkul -> semester
        $stmt = $mysqli->prepare("SELECT semester FROM tb_matkul WHERE id = ?");
        $stmt->bind_param("i", $mata_kuliah_id);
        $stmt->execute();
        $matkul = $stmt->get_result()->fetch_assoc();
        if (!$matkul) {
            echo '<div class="alert alert-danger">❌ Mata kuliah tidak ditemukan.</div>';
            exit;
        }
        $semester = $matkul['semester'];

        // Hitung jam_ahir & shift
        $jam_ahir = date("H:i", strtotime("$jam_mulai +3 hours"));
        if     ($jam_mulai <= '10:00') $shift = 'I';
        elseif ($jam_mulai <= '12:30') $shift = 'II';
        elseif ($jam_mulai <= '15:00') $shift = 'III';
        elseif ($jam_mulai <= '17:30') $shift = 'IV';
        else                           $shift = 'V';

        // Cek bentrok hari + shift
        $cek = $mysqli->prepare("SELECT COUNT(*) AS total FROM tb_praktikum WHERE hari = ? AND shift = ?");
        $cek->bind_param("ss", $hari, $shift);
        $cek->execute();
        $ex = $cek->get_result()->fetch_assoc();
        if (($ex['total'] ?? 0) > 0) {
            echo '<div class="alert alert-danger">⚠️ Jadwal bentrok! Hari <b>' . e(ucfirst($hari)) . '</b> dan <b>' . e($shift) . '</b> sudah digunakan.</div>';
            exit;
        }

        // Insert praktikum
        $stmt = $mysqli->prepare("
            INSERT INTO tb_praktikum
            (mata_kuliah, jurusan, kelas, semester, hari, jam_mulai, jam_ahir, shift, catatan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issssssss",
            $mata_kuliah_id, $jurusan, $kelas, $semester, $hari, $jam_mulai, $jam_ahir, $shift, $catatan
        );

        if ($stmt->execute()) {
            $praktikum_id = $mysqli->insert_id;

            // Insert relasi assisten (urut berdasarkan waktu insert)
            if (!empty($assisten_ids)) {
                $ins = $mysqli->prepare("INSERT IGNORE INTO tb_assisten_praktikum (praktikum_id, assisten_id) VALUES (?, ?)");
                foreach ($assisten_ids as $aid) {
                    $ins->bind_param("ii", $praktikum_id, $aid);
                    $ins->execute();
                }
            }

            echo '<div class="alert alert-success">✅ Data berhasil ditambahkan.</div>';
        } else {
            echo '<div class="alert alert-danger">❌ Gagal menyimpan data: ' . e($stmt->error) . '</div>';
        }
        break;

    // =========================
    // LIST Assisten pada praktikum (untuk modal Edit)
    // =========================
    case 'get_assisten_praktikum':
        $id = (int)($_GET['id'] ?? 0);
        $q = $mysqli->prepare("
            SELECT ap.id AS map_id, a.id AS assisten_id, a.nama, a.nim
            FROM tb_assisten_praktikum ap
            JOIN tb_assisten a ON ap.assisten_id = a.id
            WHERE ap.praktikum_id = ?
            ORDER BY ap.id ASC
        ");
        $q->bind_param("i", $id);
        $q->execute();
        $rs = $q->get_result();
        $out = [];
        while ($r = $rs->fetch_assoc()) $out[] = $r;
        header('Content-Type: application/json');
        echo json_encode($out);
        exit;

    // =========================
    // Tambah relasi assisten (modal Edit)
    // =========================
    case 'add_praktikum_assisten':
        $praktikum_id = (int)($_POST['praktikum_id'] ?? 0);
        $assisten_id  = (int)($_POST['assisten_id'] ?? 0);
        if ($praktikum_id <= 0 || $assisten_id <= 0) {
            echo '<div class="alert alert-danger">Data relasi tidak lengkap.</div>'; exit;
        }
        $stmt = $mysqli->prepare("INSERT IGNORE INTO tb_assisten_praktikum (praktikum_id, assisten_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $praktikum_id, $assisten_id);
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">✅ Assisten ditambahkan.</div>';
        } else {
            echo '<div class="alert alert-danger">❌ Gagal menambah assisten: ' . e($stmt->error) . '</div>';
        }
        exit;

    // =========================
    // Hapus relasi assisten (modal Edit)
    // =========================
    case 'remove_praktikum_assisten':
        $map_id = (int)($_POST['map_id'] ?? 0);
        if ($map_id <= 0) { echo '<div class="alert alert-danger">Data relasi tidak lengkap.</div>'; exit; }
        $del = $mysqli->prepare("DELETE FROM tb_assisten_praktikum WHERE id = ?");
        $del->bind_param("i", $map_id);
        if ($del->execute()) {
            echo '<div class="alert alert-warning">Assisten dihapus dari praktikum.</div>';
        } else {
            echo '<div class="alert alert-danger">❌ Gagal hapus relasi: ' . e($del->error) . '</div>';
        }
        exit;

    // =========================
    // EDIT praktikum (tidak menyentuh relasi assisten)
    // =========================
    case 'edit':
        $id             = (int)($_POST['id'] ?? 0);
        $mata_kuliah_id = (int)($_POST['mata_kuliah'] ?? 0);
        $jurusan        = trim($_POST['jurusan'] ?? '');
        $kelas          = trim($_POST['kelas'] ?? '');
        $hari           = trim($_POST['hari'] ?? '');
        $jam_mulai      = $_POST['jam_mulai'] ?? '';
        $catatan        = trim($_POST['catatan'] ?? '');

        // Ambil semester dari matkul
        $stmt = $mysqli->prepare("SELECT semester FROM tb_matkul WHERE id = ?");
        $stmt->bind_param("i", $mata_kuliah_id);
        $stmt->execute();
        $mk = $stmt->get_result()->fetch_assoc();
        if (!$mk) { echo '<div class="alert alert-danger">❌ Mata kuliah tidak ditemukan.</div>'; exit; }
        $semester = $mk['semester'];

        // Hitung jam_ahir & shift
        $jam_ahir = date("H:i", strtotime("$jam_mulai +3 hours"));
        if     ($jam_mulai <= '10:00') $shift = 'I';
        elseif ($jam_mulai <= '12:30') $shift = 'II';
        elseif ($jam_mulai <= '15:00') $shift = 'III';
        elseif ($jam_mulai <= '17:30') $shift = 'IV';
        else                           $shift = 'V';

        // Cek bentrok hari + shift (kecuali dirinya)
        $cek = $mysqli->prepare("SELECT COUNT(*) AS total FROM tb_praktikum WHERE hari = ? AND shift = ? AND id != ?");
        $cek->bind_param("ssi", $hari, $shift, $id);
        $cek->execute();
        $ex = $cek->get_result()->fetch_assoc();
        if (($ex['total'] ?? 0) > 0) {
            echo '<div class="alert alert-danger">⚠️ Jadwal bentrok! Hari <b>' . e(ucfirst($hari)) . '</b> dan <b>' . e($shift) . '</b> sudah digunakan.</div>';
            exit;
        }

        // Update data praktikum (relasi assisten tidak diubah di sini)
        $stmt = $mysqli->prepare("
            UPDATE tb_praktikum
            SET mata_kuliah=?, jurusan=?, kelas=?, semester=?, hari=?, jam_mulai=?, jam_ahir=?, shift=?, catatan=?
            WHERE id=?
        ");
        $stmt->bind_param(
            "issssssssi",
            $mata_kuliah_id, $jurusan, $kelas, $semester, $hari, $jam_mulai, $jam_ahir, $shift, $catatan, $id
        );
        if ($stmt->execute()) {
            echo '<div class="alert alert-warning">✅ Data berhasil diperbarui.</div>';
        } else {
            echo '<div class="alert alert-danger">❌ Gagal memperbarui data: ' . e($stmt->error) . '</div>';
        }
        break;

    // =========================
    // HAPUS praktikum (+ relasi ikut dihapus)
    // =========================
    case 'hapus':
        $id = (int)($_POST['id'] ?? 0);

        // Hapus relasi (aman jika belum pakai FK CASCADE)
        $d1 = $mysqli->prepare("DELETE FROM tb_assisten_praktikum WHERE praktikum_id = ?");
        $d1->bind_param("i", $id);
        $d1->execute();

        // Hapus praktikum
        $stmt = $mysqli->prepare("DELETE FROM tb_praktikum WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo '<div class="alert alert-danger">Data telah dihapus.</div>';
        } else {
            echo '<div class="alert alert-danger">❌ Gagal menghapus: ' . e($stmt->error) . '</div>';
        }
        break;

    // =========================
    // LIST tabel (untuk reload via AJAX)
    // =========================
    case 'list':
        $sql = "
            SELECT p.*, m.mata_kuliah AS nama_matkul,
                   (
                     SELECT GROUP_CONCAT(a.nama ORDER BY ap.id ASC SEPARATOR ', ')
                     FROM tb_assisten_praktikum ap
                     JOIN tb_assisten a ON ap.assisten_id = a.id
                     WHERE ap.praktikum_id = p.id
                   ) AS daftar_assisten
            FROM tb_praktikum p
            JOIN tb_matkul m ON p.mata_kuliah = m.id
            ORDER BY p.id DESC
        ";
        $result = $mysqli->query($sql);

        if ($result && $result->num_rows > 0) {
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
                    <td>' . e($row['daftar_assisten'] ?? '-') . '</td>
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

    // =========================
    // Default
    // =========================
    default:
        echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
}
